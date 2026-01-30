<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_two_factor_verification(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'maria@example.com',
            'password' => bcrypt('password'),
            'two_factor_enabled' => true,
            'is_active' => true,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])->post('/login', [
            '_token' => 'test-token',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/two-factor/challenge');
        $this->assertGuest();

        Notification::assertSentTo($user, TwoFactorCodeNotification::class);
    }

    public function test_admin_login_skips_two_factor_verification(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'two_factor_enabled' => true,
            'is_active' => true,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])->post('/login', [
            '_token' => 'test-token',
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);

        Notification::assertNothingSent();
    }
}
