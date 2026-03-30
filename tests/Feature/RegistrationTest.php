<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Enums\Role;
use App\Models\ReferenceValue;
use App\Models\User;
use App\Notifications\Admin\NewUserRegisteredNotification;
use App\Notifications\TwoFactorCodeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_renders_with_register_panel(): void
    {
        $response = $this->get('/register');
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('auth/Login')
            ->where('canRegister', true)
            ->where('initialPanel', 'register')
        );
    }

    public function test_registration_creates_user_with_user_role_and_redirects_to_two_factor(): void
    {
        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
        ]);

        $payload = [
            'name' => 'Juan Dela Cruz',
            'position_title' => 'Administrative Officer',
            'office_designation_id' => $office->id,
            'email' => 'juan@example.com',
            'password' => 'SecurePass1!@#',
            'password_confirmation' => 'SecurePass1!@#',
        ];

        $response = $this->post('/register', $payload);
        $response->assertRedirect(route('two-factor.challenge'));

        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'name' => $payload['name'],
            'role' => Role::USER->value,
        ]);

        $user = User::where('email', $payload['email'])->first();
        $this->assertTrue($user->two_factor_enabled);
        $this->assertTrue($user->is_active);
        $this->assertNull($user->admin_verified_at);
    }

    public function test_registration_validation_errors_do_not_render_500_error_page(): void
    {
        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
        ]);

        $payload = [
            'name' => 'Short Password User',
            'position_title' => 'Administrative Officer',
            'office_designation_id' => $office->id,
            'email' => 'short-pass@example.com',
            'password' => '12345',
            'password_confirmation' => '12345',
        ];

        $response = $this->post('/register', $payload);

        $response->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }

    public function test_registration_sends_single_otp_and_duplicate_submit_receives_validation_error(): void
    {
        Notification::fake();

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
        ]);

        $payload = [
            'name' => 'Juan Dela Cruz',
            'position_title' => 'Administrative Officer',
            'office_designation_id' => $office->id,
            'email' => 'juan@example.com',
            'password' => 'SecurePass1!@#',
            'password_confirmation' => 'SecurePass1!@#',
        ];

        $first = $this->post('/register', $payload);
        $first->assertRedirect(route('two-factor.challenge'));

        $user = User::where('email', $payload['email'])->first();
        $this->assertNotNull($user);
        Notification::assertSentToTimes($user, TwoFactorCodeNotification::class, 1);

        $second = $this->post('/register', $payload);
        $second->assertSessionHasErrors('email');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_registration_creates_admin_notification_for_new_user(): void
    {
        $admin = User::factory()->admin()->create();

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
        ]);

        $payload = [
            'name' => 'New Registrant',
            'position_title' => 'Administrative Officer',
            'office_designation_id' => $office->id,
            'email' => 'new-registrant@example.com',
            'password' => 'SecurePass1!@#',
            'password_confirmation' => 'SecurePass1!@#',
        ];

        $this->post('/register', $payload)->assertRedirect(route('two-factor.challenge'));

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
            'type' => NewUserRegisteredNotification::class,
        ]);
    }
}
