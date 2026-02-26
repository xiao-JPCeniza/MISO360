<?php

namespace Tests\Feature\Admin;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_admin_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_admin_can_access_dashboard_and_sees_stats_and_queue(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/AdminDashboard')
            ->has('stats')
            ->where('stats.activeInQueue', 0)
            ->where('stats.assignedToMe', 0)
            ->where('stats.totalReceived', 0)
            ->has('activeQueue')
            ->has('archive')
            ->has('filters')
            ->has('sort')
            ->has('staffOptions')
        );
    }

    public function test_guest_cannot_access_archive_export(): void
    {
        $this->get(route('admin.dashboard.archive-export'))
            ->assertRedirect('/login');
    }

    public function test_regular_user_cannot_access_archive_export(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.dashboard.archive-export'))
            ->assertForbidden();
    }

    public function test_admin_can_download_archive_export(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard.archive-export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_archive_export_accepts_assigned_staff_id_filter(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->admin()->create(['name' => 'MIS Staff One']);

        $response = $this->actingAs($admin)
            ->get(route('admin.dashboard.archive-export', ['assigned_staff_id' => $staff->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_assigned_to_me_count_reflects_active_requests_assigned_to_logged_in_admin(): void
    {
        foreach (['Pending', 'Ongoing', 'Completed'] as $name) {
            ReferenceValue::updateOrCreate(
                [
                    'group_key' => ReferenceValueGroup::Status->value,
                    'name' => $name,
                ],
                ['is_active' => true, 'system_seeded' => false],
            );
        }
        $pendingStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Pending')
            ->firstOrFail();
        $completedStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Completed')
            ->firstOrFail();

        $admin = User::factory()->admin()->create();
        $otherAdmin = User::factory()->admin()->create(['name' => 'Other Admin']);

        TicketRequest::factory()->create([
            'assigned_staff_id' => $admin->id,
            'status_id' => $pendingStatus->id,
        ]);
        TicketRequest::factory()->create([
            'assigned_staff_id' => $admin->id,
            'status_id' => $completedStatus->id,
        ]);
        TicketRequest::factory()->create([
            'assigned_staff_id' => $otherAdmin->id,
            'status_id' => $pendingStatus->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/AdminDashboard')
            ->has('stats')
            ->where('stats.assignedToMe', 1)
        );
    }
}
