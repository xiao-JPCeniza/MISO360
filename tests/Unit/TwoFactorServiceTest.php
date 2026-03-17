<?php

namespace Tests\Unit;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use App\Services\TwoFactorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_challenge_sends_otp_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $service = app(TwoFactorService::class);

        $challenge = $service->createChallenge($user, 'login', '127.0.0.1', 'Test');

        $this->assertNotNull($challenge->id);
        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_create_challenge_within_cooldown_does_not_send_second_otp(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $service = app(TwoFactorService::class);

        $service->createChallenge($user, 'login', null, null);
        $service->createChallenge($user, 'login', null, null);

        Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);
    }
}
