<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use App\Services\ServiceTimerService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceTimerTest extends TestCase
{
    use RefreshDatabase;

    private function ensureStatuses(): void
    {
        foreach (['Pending', 'Ongoing', 'Paused', 'Put on Hold', 'Completed'] as $name) {
            ReferenceValue::updateOrCreate(
                [
                    'group_key' => ReferenceValueGroup::Status->value,
                    'name' => $name,
                ],
                ['is_active' => true, 'system_seeded' => false],
            );
        }
    }

    public function test_timer_starts_when_status_changes_from_pending_to_ongoing(): void
    {
        $this->ensureStatuses();
        $pending = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Pending')->firstOrFail();
        $ongoing = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Ongoing')->firstOrFail();

        $ticket = TicketRequest::factory()->create([
            'status_id' => $pending->id,
            'service_timer_started_at' => null,
            'service_timer_paused_at' => null,
            'service_timer_total_elapsed_seconds' => 0,
        ]);

        $service = app(ServiceTimerService::class);
        $updates = $service->computeTimerUpdates('Ongoing', 'Pending', [
            'service_timer_started_at' => $ticket->service_timer_started_at,
            'service_timer_paused_at' => $ticket->service_timer_paused_at,
            'service_timer_total_elapsed_seconds' => $ticket->service_timer_total_elapsed_seconds ?? 0,
        ]);

        $this->assertArrayHasKey('service_timer_started_at', $updates);
        $this->assertNotNull($updates['service_timer_started_at']);
        $this->assertArrayHasKey('service_timer_paused_at', $updates);
        $this->assertNull($updates['service_timer_paused_at']);
        $this->assertSame(0, $updates['service_timer_total_elapsed_seconds']);
    }

    public function test_timer_remains_zero_for_pending_status(): void
    {
        $this->ensureStatuses();
        $pending = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Pending')->firstOrFail();

        $ticket = TicketRequest::factory()->create([
            'status_id' => $pending->id,
            'service_timer_started_at' => null,
            'service_timer_paused_at' => null,
            'service_timer_total_elapsed_seconds' => 0,
        ]);
        $ticket->load('status');

        $service = app(ServiceTimerService::class);
        $elapsed = $service->computeElapsedSeconds($ticket);

        $this->assertSame(0, $elapsed);
    }

    public function test_timer_pauses_when_status_changes_from_ongoing_to_paused(): void
    {
        $this->ensureStatuses();
        $ongoing = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Ongoing')->firstOrFail();
        $paused = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Paused')->firstOrFail();

        $startedAt = Carbon::now('Asia/Manila')->subMinutes(5);
        $ticket = TicketRequest::factory()->create([
            'status_id' => $ongoing->id,
            'service_timer_started_at' => $startedAt,
            'service_timer_paused_at' => null,
            'service_timer_total_elapsed_seconds' => 0,
        ]);

        $service = app(ServiceTimerService::class);
        $updates = $service->computeTimerUpdates('Paused', 'Ongoing', [
            'service_timer_started_at' => $ticket->service_timer_started_at,
            'service_timer_paused_at' => $ticket->service_timer_paused_at,
            'service_timer_total_elapsed_seconds' => $ticket->service_timer_total_elapsed_seconds ?? 0,
        ]);

        $this->assertArrayHasKey('service_timer_paused_at', $updates);
        $this->assertNotNull($updates['service_timer_paused_at']);
        $this->assertArrayHasKey('service_timer_total_elapsed_seconds', $updates);
        $this->assertGreaterThanOrEqual(299, $updates['service_timer_total_elapsed_seconds']);
    }

    public function test_timer_resets_for_pending_status(): void
    {
        $this->ensureStatuses();
        $ongoing = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Ongoing')->firstOrFail();
        $pending = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Pending')->firstOrFail();

        $ticket = TicketRequest::factory()->create([
            'status_id' => $ongoing->id,
            'service_timer_started_at' => Carbon::now('Asia/Manila'),
            'service_timer_paused_at' => null,
            'service_timer_total_elapsed_seconds' => 120,
        ]);

        $service = app(ServiceTimerService::class);
        $updates = $service->computeTimerUpdates('Pending', 'Ongoing', [
            'service_timer_started_at' => $ticket->service_timer_started_at,
            'service_timer_paused_at' => $ticket->service_timer_paused_at,
            'service_timer_total_elapsed_seconds' => $ticket->service_timer_total_elapsed_seconds ?? 0,
        ]);

        $this->assertNull($updates['service_timer_started_at']);
        $this->assertNull($updates['service_timer_paused_at']);
        $this->assertSame(0, $updates['service_timer_total_elapsed_seconds']);
    }

    public function test_admin_update_to_ongoing_sets_service_timer_started_at(): void
    {
        $this->ensureStatuses();
        $admin = User::factory()->admin()->create();
        $pending = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Pending')->firstOrFail();
        $ongoing = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Ongoing')->firstOrFail();
        $remarks = ReferenceValue::query()->forGroup(ReferenceValueGroup::Remarks)->first()
            ?? ReferenceValue::create(['group_key' => 'remarks', 'name' => 'For Pickup', 'is_active' => true]);
        $category = ReferenceValue::query()->forGroup(ReferenceValueGroup::Category)->first()
            ?? ReferenceValue::create(['group_key' => 'category', 'name' => 'Simple', 'is_active' => true]);

        $ticket = TicketRequest::factory()->create([
            'status_id' => $pending->id,
            'remarks_id' => $remarks->id,
            'category_id' => $category->id,
            'assigned_staff_id' => $admin->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->withSession(['_token' => 'test-token', 'two_factor.verified_at' => now()->timestamp])
            ->patch(route('requests.it-governance.update', $ticket), [
                '_token' => 'test-token',
                'remarksId' => (string) $remarks->id,
                'assignedStaffId' => (string) $admin->id,
                'dateReceived' => now()->toDateString(),
                'dateStarted' => '',
                'estimatedCompletionDate' => '',
                'actionTaken' => '',
                'categoryId' => (string) $category->id,
                'statusId' => (string) $ongoing->id,
            ]);

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertNotNull($ticket->service_timer_started_at);
        $this->assertNull($ticket->service_timer_paused_at);
        $this->assertSame(0, $ticket->service_timer_total_elapsed_seconds);
    }

    public function test_request_show_includes_service_timer_payload(): void
    {
        $this->ensureStatuses();
        $admin = User::factory()->admin()->create();
        $pending = ReferenceValue::query()->forGroup(ReferenceValueGroup::Status)->where('name', 'Pending')->firstOrFail();

        $ticket = TicketRequest::factory()->create([
            'status_id' => $pending->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('requests.show', $ticket));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('requests/ItGovernanceRequest')
            ->has('ticket')
            ->has('ticket.serviceTimer')
            ->where('ticket.serviceTimer.statusName', 'Pending')
            ->where('ticket.serviceTimer.elapsedSeconds', 0)
            ->where('ticket.serviceTimer.isActive', false)
        );
    }
}
