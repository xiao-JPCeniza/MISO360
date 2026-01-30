<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_cannot_update_super_admin_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->withSession([
                '_token' => 'test-token',
                'two_factor.verified_at' => Carbon::now()->timestamp,
            ])
            ->patch("/admin/users/{$superAdmin->id}/role", [
                '_token' => 'test-token',
                'role' => Role::ADMIN->value,
            ]);

        $response->assertStatus(403);
        $this->assertSame(Role::SUPER_ADMIN, $superAdmin->refresh()->role);
    }

    public function test_super_admin_can_update_user_role_and_status(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create([
            'role' => Role::USER,
            'is_active' => true,
        ]);

        $this->assertTrue($superAdmin->can('updateRole', $user));

        $this->actingAs($superAdmin)
            ->withSession([
                '_token' => 'test-token',
                'two_factor.verified_at' => Carbon::now()->timestamp,
            ])
            ->patch("/admin/users/{$user->id}/role", [
                '_token' => 'test-token',
                'role' => Role::ADMIN->value,
            ])
            ->assertRedirect();

        $this->actingAs($superAdmin)
            ->withSession([
                '_token' => 'test-token',
                'two_factor.verified_at' => Carbon::now()->timestamp,
            ])
            ->patch("/admin/users/{$user->id}/status", [
                '_token' => 'test-token',
                'is_active' => false,
            ])
            ->assertRedirect();

        $user->refresh();
        $this->assertSame(Role::ADMIN, $user->role);
        $this->assertFalse($user->is_active);
    }
}
