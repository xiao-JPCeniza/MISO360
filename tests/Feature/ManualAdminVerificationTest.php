<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ManualAdminVerificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function twoFactorVerifiedSession(): array
    {
        return [
            '_token' => 'test-token',
            'two_factor.verified_at' => Carbon::now()->timestamp,
        ];
    }

    public function test_standard_user_without_admin_approval_is_redirected_from_dashboard_to_pending_page(): void
    {
        $user = User::factory()->pendingAdminVerification()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('pending-approval'));
    }

    public function test_pending_approval_page_renders_for_pending_user(): void
    {
        $user = User::factory()->pendingAdminVerification()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('pending-approval'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('auth/PendingApproval'));
    }

    public function test_approved_standard_user_can_view_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'admin_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk();
    }

    public function test_user_with_admin_approval_redirected_from_pending_route_to_dashboard(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'admin_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('pending-approval'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_with_null_admin_verified_at_still_accesses_admin_dashboard(): void
    {
        $admin = User::factory()->admin()->create([
            'email_verified_at' => now(),
            'admin_verified_at' => null,
            'two_factor_enabled' => false,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_super_admin_can_grant_manual_verification(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->pendingAdminVerification()->create([
            'role' => Role::USER,
            'email_verified_at' => now(),
        ]);

        $this->assertNull($user->admin_verified_at);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.toggle-admin-verification', $user), [
                '_token' => 'test-token',
            ])
            ->assertRedirect();

        $this->assertNotNull($user->refresh()->admin_verified_at);
    }

    public function test_super_admin_can_revoke_manual_verification(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $user = User::factory()->create([
            'role' => Role::USER,
            'email_verified_at' => now(),
            'admin_verified_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->post(route('admin.users.toggle-admin-verification', $user), [
                '_token' => 'test-token',
            ])
            ->assertRedirect();

        $this->assertNull($user->refresh()->admin_verified_at);
    }

    public function test_cannot_toggle_manual_verification_for_admin_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.toggle-admin-verification', $admin), [
                '_token' => 'test-token',
            ])
            ->assertForbidden();
    }

    public function test_regular_admin_can_toggle_when_two_factor_session_valid(): void
    {
        $admin = User::factory()->admin()->create(['two_factor_enabled' => true]);
        $subject = User::factory()->pendingAdminVerification()->create([
            'role' => Role::USER,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin)
            ->withSession($this->twoFactorVerifiedSession())
            ->post(route('admin.users.toggle-admin-verification', $subject), [
                '_token' => 'test-token',
            ])
            ->assertRedirect();

        $this->assertNotNull($subject->refresh()->admin_verified_at);
    }
}
