<?php

namespace App\Http\Controllers;

use App\Models\TicketRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const BORROW_UNIT_NATURE_NAME = 'Borrow Unit';

    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $borrowNatureId = $this->borrowUnitNatureId();

        $queuedCountForUser = TicketRequest::query()
            ->where('user_id', $user->id)
            ->pending()
            ->when(
                $borrowNatureId,
                fn ($q) => $q->where('nature_of_request_id', '!=', $borrowNatureId)
            )
            ->count();

        $totalRequestsForUser = TicketRequest::query()
            ->where('user_id', $user->id)
            ->when(
                $borrowNatureId,
                fn ($q) => $q->where('nature_of_request_id', '!=', $borrowNatureId)
            )
            ->count();

        $queueQuery = TicketRequest::query()
            ->pending()
            ->forQueueViewer($user)
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
            ])
            ->when(
                $borrowNatureId,
                fn ($q) => $q->where('nature_of_request_id', '!=', $borrowNatureId)
            )
            ->orderBy('created_at');

        $activeQueueTotal = $queueQuery->count();
        $currentQueue = $queueQuery->limit(15)
            ->get()
            ->map(fn (TicketRequest $ticket) => [
                'id' => $ticket->id,
                'controlTicketNumber' => $ticket->control_ticket_number,
                'natureOfRequest' => $ticket->natureOfRequest?->name,
                'office' => $ticket->officeDesignation?->name,
                'status' => $ticket->status?->name,
                'category' => $ticket->category?->name,
                'dateFiled' => $ticket->created_at?->toDateString(),
                'showUrl' => route('requests.show', $ticket),
            ]);

        return Inertia::render('dashboard/Dashboard', [
            'queuedCountForUser' => $queuedCountForUser,
            'totalRequestsForUser' => $totalRequestsForUser,
            'currentQueue' => $currentQueue,
            'activeQueueTotal' => $activeQueueTotal,
        ]);
    }

    private function borrowUnitNatureId(): ?int
    {
        $id = \App\Models\NatureOfRequest::query()
            ->where('name', self::BORROW_UNIT_NATURE_NAME)
            ->value('id');

        return is_numeric($id) ? (int) $id : null;
    }
}
