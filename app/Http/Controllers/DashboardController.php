<?php

namespace App\Http\Controllers;

use App\Models\TicketRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $queuedCountForUser = TicketRequest::query()
            ->where('user_id', $user->id)
            ->active()
            ->count();

        $totalRequestsForUser = TicketRequest::query()
            ->where('user_id', $user->id)
            ->count();

        $activeQuery = TicketRequest::query()
            ->active()
            ->with([
                'natureOfRequest:id,name',
                'officeDesignation:id,name',
                'status:id,name',
                'category:id,name',
            ])
            ->orderBy('created_at');

        $activeQueueTotal = $activeQuery->count();
        $currentQueue = $activeQuery->limit(15)
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
}
