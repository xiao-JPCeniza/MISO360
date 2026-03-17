<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;

class TwoFactorCodeNotification extends Notification
{
    public function __construct(
        public string $code,
        public \DateTimeInterface $sentAt,
        public \DateTimeInterface $expiresAt,
        public string $purpose,
        public int $validMinutes,
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

        $sentAtPht = Carbon::instance(\DateTimeImmutable::createFromInterface($this->sentAt))
            ->timezone('Asia/Manila')
            ->format('M d, Y h:i A');
        $expiresAtPht = Carbon::instance(\DateTimeImmutable::createFromInterface($this->expiresAt))
            ->timezone('Asia/Manila')
            ->format('M d, Y h:i A');
        $replyToAddress = (string) config('mail.reply_to.address');
        $replyToName = (string) config('mail.reply_to.name');

        $mailMessage = (new MailMessage)
            ->subject('Your MISO 360 one-time password (OTP)')
            ->view('emails.auth.otp-code', [
                'appName' => (string) config('app.name', 'MISO 360'),
                'logoUrl' => asset('storage/logos/IT Logo.png'),
                'purposeLabel' => $purposeLabel,
                'otpCode' => $this->code,
                'sentAtPht' => $sentAtPht,
                'expiresAtPht' => $expiresAtPht,
                'timezoneLabel' => 'PHT (UTC+8)',
                'validMinutes' => $this->validMinutes,
                'replyToAddress' => $replyToAddress,
            ]);

        if ($replyToAddress !== '') {
            $mailMessage->replyTo($replyToAddress, $replyToName !== '' ? $replyToName : null);
        }

        return $mailMessage;
    }
}
