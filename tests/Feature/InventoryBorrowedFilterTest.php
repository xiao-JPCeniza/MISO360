<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryBorrowedFilterTest extends TestCase
{
    use RefreshDatabase;

    private function createStatusValues(): void
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
    }

    public function test_borrowed_filter_excludes_completed_requests(): void
    {
        $this->createStatusValues();
        $borrowNature = NatureOfRequest::create(['name' => 'Borrow Unit', 'is_active' => true]);
        $pendingStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Pending')
            ->firstOrFail();
        $completedStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Completed')
            ->firstOrFail();

        $activeBorrow = TicketRequest::factory()->create([
            'nature_of_request_id' => $borrowNature->id,
            'status_id' => $pendingStatus->id,
            'control_ticket_number' => 'CTN-20260219-0001',
        ]);
        $completedBorrow = TicketRequest::factory()->create([
            'nature_of_request_id' => $borrowNature->id,
            'status_id' => $completedStatus->id,
            'control_ticket_number' => 'CTN-20260219-0002',
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('inventory', ['status' => 'borrowed']));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('inventory/Inventory')
            ->has('borrowedRequests', 1)
            ->where('borrowedRequests.0.id', $activeBorrow->id)
            ->where('borrowedRequests.0.controlTicketNumber', 'CTN-20260219-0001')
            ->where('counts.borrowed', 1)
        );
    }

    public function test_marking_borrow_unit_completed_redirects_to_inventory_borrowed(): void
    {
        $this->createStatusValues();
        $borrowNature = NatureOfRequest::create(['name' => 'Borrow Unit', 'is_active' => true]);
        $completedStatus = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::Status)
            ->where('name', 'Completed')
            ->firstOrFail();

        $ticket = TicketRequest::factory()->create([
            'nature_of_request_id' => $borrowNature->id,
            'status_id' => ReferenceValue::query()
                ->forGroup(ReferenceValueGroup::Status)
                ->where('name', 'Pending')
                ->value('id'),
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->patch(route('requests.equipment-network.update', $ticket), [
                'statusId' => $completedStatus->id,
            ]);

        $response->assertRedirect(route('inventory', ['status' => 'borrowed']));
        $response->assertSessionHas('success');
    }

    public function test_regular_user_cannot_access_inventory(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('inventory', ['status' => 'borrowed']));

        $response->assertForbidden();
    }
}
