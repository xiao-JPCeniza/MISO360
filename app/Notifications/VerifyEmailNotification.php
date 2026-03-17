<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);
        $expiresAt = Carbon::now()->addMinutes((int) Config::get('auth.verification.expire', 60));
        $sentAt = Carbon::now();
        $timezone = 'Asia/Manila';

        $replyToAddress = (string) config('mail.reply_to.address');
        $replyToName = (string) config('mail.reply_to.name');

        $mailMessage = (new MailMessage)
            ->subject('Verify your MISO 360 email address')
            ->view('emails.auth.verify-email', [
                'appName' => (string) config('app.name', 'MISO 360'),
                'logoUrl' => rtrim(config('app.url'), '/').'/storage/logos/'.rawurlencode('IT Logo.png'),
                'verificationUrl' => $verificationUrl,
                'sentAtPht' => $sentAt->timezone($timezone)->format('M d, Y h:i A'),
                'expiresAtPht' => $expiresAt->timezone($timezone)->format('M d, Y h:i A'),
                'timezoneLabel' => 'PHT (UTC+8)',
                'validMinutes' => (int) Config::get('auth.verification.expire', 60),
                'replyToAddress' => $replyToAddress,
            ]);

        if ($replyToAddress !== '') {
            $mailMessage->replyTo($replyToAddress, $replyToName !== '' ? $replyToName : null);
        }

        return $mailMessage;
    }
}
