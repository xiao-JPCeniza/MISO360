<?php

namespace Tests\Feature;

use App\Models\TicketEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScanAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware();
    }

    public function test_user_can_view_scan_result_but_cannot_review_or_assign(): void
    {
        $user = User::factory()->create();
        $enrollment = TicketEnrollment::create([
            'unique_id' => 'MIS-UID-12345',
            'equipment_name' => 'Test Laptop',
        ]);

        $this->actingAs($user)
            ->get('/scan')
            ->assertOk();

        $this->actingAs($user)
            ->get("/scan/{$enrollment->unique_id}")
            ->assertOk();

        $this->actingAs($user)
            ->post("/scan/{$enrollment->unique_id}/review", [
                'acceptRepair' => true,
                'comments' => 'Needs repair',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->put("/scan/{$enrollment->unique_id}/assign", [
                'assignedAdminId' => $user->id,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_review_but_cannot_assign(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = TicketEnrollment::create([
            'unique_id' => 'MIS-UID-54321',
            'equipment_name' => 'Test Printer',
        ]);

        $this->actingAs($admin)
            ->post("/scan/{$enrollment->unique_id}/review", [
                'acceptRepair' => true,
                'comments' => 'Accepted for repair',
            ])
            ->assertRedirect("/scan/{$enrollment->unique_id}");

        $this->assertDatabaseHas('ticket_enrollments', [
            'unique_id' => $enrollment->unique_id,
            'repair_status' => 'accepted',
            'repair_comments' => 'Accepted for repair',
        ]);

        $this->actingAs($admin)
            ->put("/scan/{$enrollment->unique_id}/assign", [
                'assignedAdminId' => $admin->id,
            ])
            ->assertForbidden();
    }

    public function test_super_admin_can_assign(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $assignee = User::factory()->admin()->create();
        $enrollment = TicketEnrollment::create([
            'unique_id' => 'MIS-UID-67890',
            'equipment_name' => 'Test Router',
        ]);

        $this->actingAs($superAdmin)
            ->put("/scan/{$enrollment->unique_id}/assign", [
                'assignedAdminId' => $assignee->id,
            ])
            ->assertRedirect("/scan/{$enrollment->unique_id}");

        $this->assertDatabaseHas('ticket_enrollments', [
            'unique_id' => $enrollment->unique_id,
            'assigned_admin_id' => $assignee->id,
        ]);
    }

    public function test_admin_scan_auto_assigns_unassigned_ticket_and_logs(): void
    {
        $admin = User::factory()->admin()->create();
        $enrollment = TicketEnrollment::create([
            'unique_id' => 'MIS-UID-11111',
            'equipment_name' => 'Test Device',
        ]);
        $this->assertNull($enrollment->assigned_admin_id);

        $this->actingAs($admin)
            ->get("/scan/{$enrollment->unique_id}")
            ->assertOk();

        $enrollment->refresh();
        $this->assertSame($admin->id, $enrollment->assigned_admin_id);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id' => $admin->id,
            'action' => 'scan.ticket.auto_assigned',
            'target_type' => 'App\Models\TicketEnrollment',
            'target_id' => $enrollment->id,
        ]);
    }
}
