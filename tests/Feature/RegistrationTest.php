<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_can_register_and_receive_verification_email(): void
    {
        Notification::fake();
        $this->withoutMiddleware();

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
        ]);

        $payload = [
            'name' => 'Juan Dela Cruz',
            'position_title' => 'Administrative Officer',
            'office_designation_id' => $office->id,
            'email' => 'juan@example.com',
            'password' => 'SecurePass1!',
            'password_confirmation' => 'SecurePass1!',
        ];

        $response = $this->post('/register', $payload);

        $response->assertRedirect(route('verification.notice'));

        $user = User::where('email', $payload['email'])->first();

        $this->assertNotNull($user);
        $this->assertSame($office->id, $user->office_designation_id);
        $this->assertTrue(Hash::check($payload['password'], $user->password));
        $this->assertNull($user->email_verified_at);

        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
