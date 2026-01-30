<?php

namespace Tests\Feature;

use App\Models\NatureOfRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NatureOfRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_nature_of_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $csrfToken = 'test-token';

        $this->actingAs($admin)
            ->get('/admin/nature-of-request')
            ->assertOk();

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->post('/admin/nature-of-request', [
                '_token' => $csrfToken,
                'name' => 'Application software installation',
            ])
            ->assertRedirect('/admin/nature-of-request');

        $requestType = NatureOfRequest::firstOrFail();

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/nature-of-request/{$requestType->id}", [
                '_token' => $csrfToken,
                'name' => 'Application software installation and updates',
            ])
            ->assertRedirect('/admin/nature-of-request');

        $this->assertDatabaseHas('nature_of_requests', [
            'id' => $requestType->id,
            'name' => 'Application software installation and updates',
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->delete("/admin/nature-of-request/{$requestType->id}", [
                '_token' => $csrfToken,
            ])
            ->assertRedirect('/admin/nature-of-request');

        $this->assertDatabaseHas('nature_of_requests', [
            'id' => $requestType->id,
            'is_active' => false,
        ]);
    }

    public function test_regular_user_cannot_manage_nature_of_requests(): void
    {
        $user = User::factory()->create();
        $csrfToken = 'test-token';
        $requestType = NatureOfRequest::create([
            'name' => 'Network troubleshooting',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/nature-of-request')
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/admin/nature-of-request', [
                '_token' => $csrfToken,
                'name' => 'Printer repair',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/nature-of-request/{$requestType->id}", [
                '_token' => $csrfToken,
                'name' => 'Printer repair and setup',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->delete("/admin/nature-of-request/{$requestType->id}", [
                '_token' => $csrfToken,
            ])
            ->assertForbidden();
    }

    public function test_options_endpoint_returns_active_requests(): void
    {
        $user = User::factory()->create();

        NatureOfRequest::create([
            'name' => 'CCTV maintenance',
            'is_active' => true,
        ]);
        NatureOfRequest::create([
            'name' => 'Inactive request',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/nature-of-request/options')
            ->assertOk()
            ->assertJsonFragment(['name' => 'CCTV maintenance'])
            ->assertJsonMissing(['name' => 'Inactive request']);
    }
}
