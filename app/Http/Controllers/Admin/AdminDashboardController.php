<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Exports\ConsolidatedStaffWorkloadExport;
use App\Exports\NatureOfRequestsMonthlySummaryExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportArchivedRequestsRequest;
use App\Http\Requests\Admin\ExportNatureOfRequestsMonthlySummaryRequest;
use App\Models\IssuedUid;
use App\Models\TicketRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $activeCountQuery = TicketRequest::query()
            ->pending()
            ->forQueueViewer($user);
        $activeCount = $activeCountQuery->count();
        $totalReceived = TicketRequest::query()->count();
        $assignedToMe = TicketRequest::query()
            ->where('assigned_staff_id', $user->id)
            ->where(function (Builder $q) {
                $q->whereHas('status', fn (Builder $sq) => $sq->where('name', '!=', 'Completed'))
                    ->orWhereNull('status_id');
            })
            ->count();

        $activeQuery = TicketRequest::query()
            ->pending()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'user:id,name',
                'requestedForUser:id,name',
            ])
            ->forQueueViewer($user);

        $this->applyQueueFilters($activeQuery, $request);

        $activeQueueTotal = $activeQuery->count();
        $activeQueue = $activeQuery
            ->orderBy('ticket_requests.created_at', 'asc')
            ->limit(15)
            ->get()
            ->map(fn (TicketRequest $t) => [
                'id' => $t->id,
                'controlTicketNumber' => $t->control_ticket_number,
                'requesterName' => $t->requestedForUser?->name ?? $t->user?->name,
                'office' => $t->officeDesignation?->name,
                'category' => $t->category?->name,
                'status' => $t->status?->name,
                'remarks' => $t->description,
                'natureOfRequest' => $t->natureOfRequest?->name,
                'dateFiled' => $t->created_at?->toDateString(),
                'showUrl' => route('requests.show', $t),
            ]);

        $archiveQuery = TicketRequest::query()
            ->completed()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'user:id,name',
                'requestedForUser:id,name',
            ]);

        if ($request->filled('archive_search')) {
            $term = '%'.$request->string('archive_search')->trim().'%';
            $archiveQuery->where(function ($q) use ($term) {
                $q->where('control_ticket_number', 'like', $term)
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $term))
                    ->orWhereHas('requestedForUser', fn ($q) => $q->where('name', 'like', $term));
            });
        }

        $archiveQuery->orderBy('created_at', 'desc');
        $archived = $archiveQuery->paginate(15, ['*'], 'archive_page')
            ->withQueryString()
            ->through(fn (TicketRequest $t) => [
                'id' => $t->id,
                'controlTicketNumber' => $t->control_ticket_number,
                'requesterName' => $t->requestedForUser?->name ?? $t->user?->name,
                'office' => $t->officeDesignation?->name,
                'category' => $t->category?->name,
                'status' => $t->status?->name,
                'remarks' => $t->description,
                'dateFiled' => $t->created_at?->toDateString(),
                'showUrl' => route('requests.show', $t),
            ]);

        $staffOptions = User::query()
            ->where('is_active', true)
            ->whereIn('role', [Role::ADMIN, Role::SUPER_ADMIN])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (User $u) => ['id' => $u->id, 'name' => $u->name])
            ->values()
            ->all();

        return Inertia::render('admin/AdminDashboard', [
            'totalGenerated' => IssuedUid::count(),
            'activeQueueTotal' => $activeQueueTotal,
            'stats' => [
                'activeInQueue' => $activeCount,
                'assignedToMe' => $assignedToMe,
                'totalReceived' => $totalReceived,
            ],
            'activeQueue' => $activeQueue,
            'archive' => $archived,
            'filters' => [
                'control_ticket_number' => $request->string('control_ticket_number')->trim()->toString() ?: null,
            ],
            'sort' => [
                'by' => 'created_at',
                'dir' => 'asc',
            ],
            'archiveSearch' => $request->string('archive_search')->trim()->toString() ?: null,
            'archivePanelOpen' => $request->filled('archive_page') || $request->filled('archive_search'),
            'staffOptions' => $staffOptions,
        ]);
    }

    /**
     * Export archived (completed) requests to Excel for IPCR/OPCR.
     * Admin and Super Admin only; audited; respects archive filters and optional date range.
     */
    public function exportArchived(ExportArchivedRequestsRequest $request, AuditLogger $auditLogger): BinaryFileResponse
    {
        $archiveQuery = $this->buildArchiveExportQuery($request);
        $completedTickets = $archiveQuery->get();

        $activeTickets = TicketRequest::query()
            ->active()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'user:id,name',
                'requestedForUser:id,name',
                'enrollment',
                'enrollment.assignedAdmin:id,name',
            ])
            ->when(
                $request->filled('assigned_staff_id'),
                fn (Builder $q) => $q->where('ticket_requests.assigned_staff_id', (int) $request->input('assigned_staff_id'))
            )
            ->get();

        $tickets = $completedTickets
            ->concat($activeTickets)
            ->unique('id')
            ->values();

        $auditLogger->log($request, 'admin.archive_export.download', null, [
            'archive_search' => $request->string('archive_search')->trim()->toString() ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
            'assigned_staff_id' => $request->filled('assigned_staff_id') ? (int) $request->input('assigned_staff_id') : null,
            'rows_exported' => $tickets->count(),
        ]);

        $filename = 'consolidated-staff-workload-'.now()->format('Y-m-d-His').'.xlsx';

        return (new ConsolidatedStaffWorkloadExport($tickets))->download($filename);
    }

    /**
     * Export a monthly summary (by Nature of Request) to Excel.
     * Super Admin only; audited; defaults to current year.
     */
    public function exportNatureOfRequestsMonthlySummary(
        ExportNatureOfRequestsMonthlySummaryRequest $request,
        AuditLogger $auditLogger
    ): BinaryFileResponse {
        $year = (int) ($request->input('year') ?: now()->year);

        $from = CarbonImmutable::create($year, 1, 1, 0, 0, 0);
        $to = CarbonImmutable::create($year, 12, 31, 23, 59, 59);

        $auditLogger->log($request, 'admin.nature_monthly_summary_export.download', null, [
            'year' => $year,
        ]);

        $filename = 'nature-of-requests-summary-'.$year.'-'.now()->format('Y-m-d-His').'.xlsx';

        $user = $request->user();

        return (new NatureOfRequestsMonthlySummaryExport($from, $to, $user, $user))->download($filename);
    }

    /**
     * Build the same filtered query as the archive view (completed only) plus optional date range.
     *
     * @param  \Illuminate\Http\Request  $request  validated export request
     */
    private function buildArchiveExportQuery(Request $request): \Illuminate\Database\Eloquent\Builder
    {
        $query = TicketRequest::query()
            ->completed()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'user:id,name',
                'requestedForUser:id,name',
                'enrollment',
                'enrollment.assignedAdmin:id,name',
            ]);

        if ($request->filled('archive_search')) {
            $term = '%'.$request->string('archive_search')->trim().'%';
            $query->where(function ($q) use ($term) {
                $q->where('control_ticket_number', 'like', $term)
                    ->orWhereHas('user', fn ($q) => $q->where('name', 'like', $term))
                    ->orWhereHas('requestedForUser', fn ($q) => $q->where('name', 'like', $term));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('ticket_requests.updated_at', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('ticket_requests.updated_at', '<=', $request->date('date_to'));
        }

        if ($request->filled('assigned_staff_id')) {
            $query->where('ticket_requests.assigned_staff_id', (int) $request->input('assigned_staff_id'));
        }

        $query->latest('ticket_requests.updated_at');

        $query->limit(10000);

        return $query;
    }

    private function applyQueueFilters($query, Request $request): void
    {
        if ($request->filled('control_ticket_number')) {
            $query->where('control_ticket_number', 'like', '%'.$request->string('control_ticket_number')->trim().'%');
        }
    }
}
