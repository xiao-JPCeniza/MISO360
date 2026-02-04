<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\IssuedUid;
use App\Models\TicketRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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
                'requester_name' => $request->string('requester_name')->trim()->toString() ?: null,
                'office' => $request->string('office')->trim()->toString() ?: null,
                'category' => $request->string('category')->trim()->toString() ?: null,
                'status' => $request->string('status')->trim()->toString() ?: null,
                'remarks' => $request->string('remarks')->trim()->toString() ?: null,
            ],
            'sort' => [
                'by' => $request->string('sort_by')->trim()->toString() ?: 'created_at',
                'dir' => $request->string('sort_dir')->trim()->toString() === 'desc' ? 'desc' : 'asc',
            ],
            'archiveSearch' => $request->string('archive_search')->trim()->toString() ?: null,
            'archivePanelOpen' => $request->filled('archive_page') || $request->filled('archive_search'),
        ]);
    }

    private function applyQueueFilters($query, Request $request): void
    {
        if ($request->filled('control_ticket_number')) {
            $query->where('control_ticket_number', 'like', '%'.$request->string('control_ticket_number')->trim().'%');
        }
        if ($request->filled('requester_name')) {
            $term = '%'.$request->string('requester_name')->trim().'%';
            $query->where(function ($q) use ($term) {
                $q->whereHas('user', fn ($q) => $q->where('name', 'like', $term))
                    ->orWhereHas('requestedForUser', fn ($q) => $q->where('name', 'like', $term));
            });
        }
        if ($request->filled('office')) {
            $query->whereHas('officeDesignation', fn ($q) => $q->where('name', 'like', '%'.$request->string('office')->trim().'%'));
        }
        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('name', 'like', '%'.$request->string('category')->trim().'%'));
        }
        if ($request->filled('status')) {
            $query->whereHas('status', fn ($q) => $q->where('name', 'like', '%'.$request->string('status')->trim().'%'));
        }
        if ($request->filled('remarks')) {
            $query->where('description', 'like', '%'.$request->string('remarks')->trim().'%');
        }
    }

    private function applyQueueSort($query, Request $request): void
    {
        $by = $request->string('sort_by')->trim()->toString() ?: 'created_at';
        $dir = $request->string('sort_dir')->trim()->toString() === 'desc' ? 'desc' : 'asc';
        $allowed = ['created_at', 'control_ticket_number', 'updated_at'];
        if (! in_array($by, $allowed, true)) {
            $by = 'created_at';
        }
        if ($by === 'control_ticket_number') {
            $query->orderBy('control_ticket_number', $dir);
        } else {
            $query->orderBy($by, $dir);
        }
    }
}
