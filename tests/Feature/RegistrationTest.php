<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_endpoint_is_disabled(): void
    {
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

        $this->post('/register', $payload)->assertNotFound();
        $this->assertDatabaseMissing('users', ['email' => $payload['email']]);
    }
}
