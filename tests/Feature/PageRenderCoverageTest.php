<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ProfileSlide;
use App\Models\TicketEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PageRenderCoverageTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_nature_of_requests_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.nature-of-requests.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/nature-of-requests/Index')
                ->has('requests')
            );
    }

    public function test_super_admin_audit_logs_page_renders(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        AuditLog::query()->create([
            'actor_id' => $superAdmin->id,
            'action' => 'test.audit.log',
            'target_type' => User::class,
            'target_id' => $superAdmin->id,
            'metadata' => ['source' => 'test'],
        ]);

        $this->actingAs($superAdmin)
            ->withSession(['two_factor.verified_at' => now()->timestamp])
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/audit-logs/Index')
                ->has('logs.data')
            );
    }

    public function test_notifications_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('notifications.page'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('notifications/Notifications')
            );
    }

    public function test_admin_qr_generator_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.qr-generator'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/AdminQrGenerator')
                ->hasAll(['nextStart', 'totalGenerated'])
            );
    }

    public function test_status_management_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.status.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/status/Index')
                ->has('groups')
            );
    }

    public function test_admin_user_show_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $managedUser = User::factory()->create();

        $this->actingAs($admin)
            ->withSession(['two_factor.verified_at' => now()->timestamp])
            ->get(route('admin.users.show', $managedUser))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/users/Show')
                ->where('user.id', $managedUser->id)
            );
    }

    public function test_scan_qr_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('scan.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('scan/ScanQr')
            );
    }

    public function test_scan_result_page_renders_for_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = TicketEnrollment::query()->create([
            'unique_id' => 'MIS-UID-88888',
            'equipment_name' => 'Test Workstation',
        ]);

        $this->actingAs($admin)
            ->get(route('scan.show', ['uniqueId' => $enrollment->unique_id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('scan/ScanResult')
                ->where('item.uniqueId', $enrollment->unique_id)
            );
    }

    public function test_settings_profile_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Profile')
            );
    }

    public function test_settings_appearance_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('appearance.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('settings/Appearance')
            );
    }

    public function test_admin_posts_create_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.posts.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/posts/Create')
                ->has('textPositionOptions')
            );
    }

    public function test_admin_posts_edit_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $slide = ProfileSlide::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.posts.edit', $slide))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('admin/posts/Edit')
                ->where('slide.id', $slide->id)
            );
    }

    public function test_email_verification_notice_page_renders(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('verification.notice'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/VerifyEmail')
            );
    }

    public function test_two_factor_challenge_page_renders_for_pending_user(): void
    {
        $user = User::factory()->pendingAdminVerification()->create();

        $this->withSession([
            'two_factor.pending_user_id' => $user->id,
            'two_factor.purpose' => 'login',
        ])
            ->get(route('two-factor.challenge'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('auth/TwoFactorChallenge')
                ->where('email', $user->email)
            );
    }
}
