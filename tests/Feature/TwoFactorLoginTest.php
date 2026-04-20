<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TwoFactorLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_requires_two_factor_verification(): void
    {
        Notification::fake();

        $user = User::factory()->pendingAdminVerification()->create([
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

    public function test_login_skips_two_factor_for_fully_verified_user_when_config_enabled(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'password' => bcrypt('password'),
            'two_factor_enabled' => true,
            'is_active' => true,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])->post('/login', [
            '_token' => 'test-token',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_login_requires_two_factor_for_fully_verified_user_when_skip_config_disabled(): void
    {
        Notification::fake();

        Config::set('security.two_factor.skip_for_fully_verified_users', false);

        $user = User::factory()->create([
            'email' => 'still-otp@example.com',
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

    public function test_inactive_user_cannot_log_in_and_sees_account_inactive_message(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        $response = $this->withSession(['_token' => 'test-token'])->post('/login', [
            '_token' => 'test-token',
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Account inactive', $response->getSession()->get('errors')->get('email')[0]);
        $this->assertGuest();
    }
}
