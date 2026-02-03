<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code,
        public \DateTimeInterface $expiresAt,
        public string $purpose,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $purposeLabel = $this->purpose === 'login'
            ? 'login'
            : 'verify a sensitive action';

        return (new MailMessage)
            ->subject('Your MISO 360 verification code')
            ->line("Use this code to {$purposeLabel}:")
            ->line($this->code)
            ->line('This code expires at '.$this->expiresAt->format('g:i A T').'.')
            ->line('If you did not request this, you can ignore this email.');
    }
}
