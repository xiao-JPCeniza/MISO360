<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\ReferenceValue;
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

    public function test_admin_can_update_work_details_and_audit_change(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $currentOffice = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'Current Office',
        ]);
        $nextOffice = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'Next Office',
        ]);

        $user = User::factory()->create([
            'role' => Role::USER,
            'office_designation_id' => $currentOffice->id,
            'position_title' => 'Old Position',
        ]);

        $this->actingAs($superAdmin)
            ->withSession([
                '_token' => 'test-token',
                'two_factor.verified_at' => Carbon::now()->timestamp,
            ])
            ->patch("/admin/users/{$user->id}", [
                '_token' => 'test-token',
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'position_title' => 'Updated Position',
                'office_designation_id' => $nextOffice->id,
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame($nextOffice->id, $user->office_designation_id);
        $this->assertSame('Updated Position', $user->position_title);

        $auditLog = AuditLog::query()->latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertSame('user.profile.updated', $auditLog->action);
        $this->assertSame($user->id, $auditLog->target_id);
        $this->assertSame($superAdmin->id, $auditLog->actor_id);

        $changes = $auditLog->metadata['changes'];

        $this->assertSame($currentOffice->id, $changes['office_designation']['from']['id']);
        $this->assertSame($nextOffice->id, $changes['office_designation']['to']['id']);
        $this->assertSame('Old Position', $changes['position_title']['from']);
        $this->assertSame('Updated Position', $changes['position_title']['to']);
    }
}
