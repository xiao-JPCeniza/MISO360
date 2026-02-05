<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ArchivedRequestsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportArchivedRequestsRequest;
use App\Models\IssuedUid;
use App\Models\TicketRequest;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminDashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        $activeCount = TicketRequest::query()->active()->count();
        $totalReceived = TicketRequest::query()->count();
        // Assigned-to-admin is not stored on ticket_requests; display 0 until schema supports it.
        $assignedToMe = 0;

        $activeQuery = TicketRequest::query()
            ->active()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
                'user:id,name',
                'requestedForUser:id,name',
            ]);

        $this->applyQueueFilters($activeQuery, $request);
        $this->applyQueueSort($activeQuery, $request);

        $activeQueueTotal = $activeQuery->count();
        $activeQueue = $activeQuery->limit(15)
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

        $archiveQuery->latest();
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
                'by' => $request->string('sort_by')->trim()->toString() ?: 'control_ticket_number',
                'dir' => $request->string('sort_dir')->trim()->toString() === 'desc' ? 'desc' : 'asc',
            ],
            'archiveSearch' => $request->string('archive_search')->trim()->toString() ?: null,
            'archivePanelOpen' => $request->filled('archive_page') || $request->filled('archive_search'),
        ]);
    }

    /**
     * Export archived (completed) requests to Excel for IPCR/OPCR.
     * Admin and Super Admin only; audited; respects archive filters and optional date range.
     */
    public function exportArchived(ExportArchivedRequestsRequest $request, AuditLogger $auditLogger): BinaryFileResponse
    {
        $query = $this->buildArchiveExportQuery($request);
        $count = $query->count();

        $auditLogger->log($request, 'admin.archive_export.download', null, [
            'archive_search' => $request->string('archive_search')->trim()->toString() ?: null,
            'date_from' => $request->date('date_from')?->toDateString(),
            'date_to' => $request->date('date_to')?->toDateString(),
            'rows_exported' => $count,
        ]);

        $filename = 'archived-requests-'.now()->format('Y-m-d-His').'.xlsx';

        return (new ArchivedRequestsExport($query))->download($filename);
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

    private function applyQueueSort($query, Request $request): void
    {
        $by = $request->string('sort_by')->trim()->toString() ?: 'control_ticket_number';
        $dir = $request->string('sort_dir')->trim()->toString() === 'desc' ? 'desc' : 'asc';
        $allowed = ['control_ticket_number', 'requester_name'];
        if (! in_array($by, $allowed, true)) {
            $by = 'control_ticket_number';
        }
        if ($by === 'control_ticket_number') {
            $query->orderBy('control_ticket_number', $dir);
        } else {
            $query->leftJoin('users as sort_requester', 'ticket_requests.requested_for_user_id', '=', 'sort_requester.id')
                ->leftJoin('users as sort_creator', 'ticket_requests.user_id', '=', 'sort_creator.id')
                ->orderByRaw('COALESCE(sort_requester.name, sort_creator.name) '.$dir)
                ->select('ticket_requests.*');
        }
    }
}
