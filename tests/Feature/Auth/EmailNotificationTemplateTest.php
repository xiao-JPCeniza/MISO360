<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Notifications\VerifyEmailNotification;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailNotificationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_otp_email_template_renders_with_philippine_timestamps_and_support_reply_to(): void
    {
        config([
            'app.name' => 'MISO 360',
            'mail.reply_to.address' => 'support@miso360.com',
            'mail.reply_to.name' => 'MISO Support',
        ]);

        $notification = new TwoFactorCodeNotification(
            code: '123456',
            sentAt: new DateTimeImmutable('2026-03-17 04:00:00', new DateTimeZone('UTC')),
            expiresAt: new DateTimeImmutable('2026-03-17 04:10:00', new DateTimeZone('UTC')),
            purpose: 'login',
            validMinutes: 10,
        );

        $mail = $notification->toMail(User::factory()->make());

        $this->assertSame('emails.auth.otp-code', $mail->view);
        $this->assertStringContainsString('support@miso360.com', (string) json_encode($mail->replyTo));

        $html = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('123456', $html);
        $this->assertStringContainsString('Code Sent At:</strong> Mar 17, 2026 12:00 PM PHT (UTC+8)', $html);
        $this->assertStringContainsString('Expiration Time:</strong> Mar 17, 2026 12:10 PM PHT (UTC+8)', $html);
        $this->assertStringContainsString('valid for 10 minutes', $html);
    }

    public function test_verification_email_template_renders_with_cta_pht_timestamps_and_support_reply_to(): void
    {
        config([
            'app.name' => 'MISO 360',
            'mail.reply_to.address' => 'support@miso360.com',
            'mail.reply_to.name' => 'MISO Support',
        ]);

        $user = User::factory()->unverified()->create();
        $notification = new VerifyEmailNotification;

        $mail = $notification->toMail($user);

        $this->assertSame('emails.auth.verify-email', $mail->view);
        $this->assertStringContainsString('support@miso360.com', (string) json_encode($mail->replyTo));

        $html = view($mail->view, $mail->viewData)->render();

        $this->assertStringContainsString('Verify Email Address', $html);
        $this->assertStringContainsString('PHT (UTC+8)', $html);
        $this->assertStringContainsString('mailto:support@miso360.com', $html);
        $this->assertStringContainsString('This verification link is valid for', $html);
    }
}
