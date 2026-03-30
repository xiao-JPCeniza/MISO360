<?php

namespace App\Notifications\Admin;

use App\Models\TicketRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewTicketRequestSubmittedNotification extends Notification
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
        $requestedBy = $this->ticketRequest->requestedForUser?->name ?? $this->ticketRequest->user?->name ?? 'Someone';

        return [
            'kind' => 'new_ticket_request_submitted',
            'title' => 'New ticket request submitted',
            'message' => $requestedBy.' submitted a new request ('.$this->ticketRequest->control_ticket_number.').',
            'url' => route('requests.show', $this->ticketRequest),
            'ticketRequestId' => $this->ticketRequest->id,
            'controlTicketNumber' => $this->ticketRequest->control_ticket_number,
        ];
    }
}
