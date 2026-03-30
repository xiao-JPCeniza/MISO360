<?php

namespace App\Notifications;

use App\Models\TicketRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class TicketCompletedNotification extends Notification
{
    use Queueable;

    public function __construct(public TicketRequest $ticketRequest) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => 'ticket_completed',
            'title' => 'Ticket completed',
            'message' => 'Your ticket ('.$this->ticketRequest->control_ticket_number.') has been completed.',
            'url' => route('requests.show', $this->ticketRequest),
            'ticketRequestId' => $this->ticketRequest->id,
            'controlTicketNumber' => $this->ticketRequest->control_ticket_number,
        ];
    }
}
