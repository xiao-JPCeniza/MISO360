<?php

namespace Tests\Feature;

use App\Models\NatureOfRequest;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RequestsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_only_sees_their_requests(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $userRequest = TicketRequest::factory()->create([
            'user_id' => $user->id,
            'description' => 'Replace the office keyboard.',
        ]);
        TicketRequest::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('requests'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/Requests')
            ->where('isAdmin', false)
            ->has('requests', 1)
            ->where('requests.0.controlTicketNumber', $userRequest->control_ticket_number)
            ->where('requests.0.positionTitle', $user->position_title)
            ->where('requests.0.requestDescription', 'Replace the office keyboard.')
        );
    }

    public function test_admin_sees_all_requests_in_fifo_order(): void
    {
        $admin = User::factory()->admin()->create();

        $olderRequest = TicketRequest::factory()->create([
            'created_at' => now()->subMinute(),
        ]);
        $newerRequest = TicketRequest::factory()->create([
            'description' => 'Network connection issue.',
            'created_at' => now(),
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('requests'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/Requests')
            ->where('isAdmin', true)
            ->has('requests', 2)
            ->where('requests.0.controlTicketNumber', $olderRequest->control_ticket_number)
            ->where('requests.1.controlTicketNumber', $newerRequest->control_ticket_number)
            ->where('requests.1.positionTitle', $newerRequest->user->position_title)
            ->where('requests.1.requestDescription', 'Network connection issue.')
        );
    }

    public function test_it_governance_request_page_renders(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('requests.it-governance'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/ItGovernanceRequest')
        );
    }

    public function test_admin_can_open_it_governance_page_for_ticket(): void
    {
        $admin = User::factory()->admin()->create();
        $nature = NatureOfRequest::query()->firstWhere('name', 'System account creation')
            ?? NatureOfRequest::create(['name' => 'System account creation', 'is_active' => true]);
        $ticket = TicketRequest::factory()->create([
            'nature_of_request_id' => $nature->id,
            'description' => 'New gov mail account',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('requests.it-governance.show', $ticket));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/ItGovernanceRequest')
            ->has('ticket')
            ->where('ticket.controlTicketNumber', $ticket->control_ticket_number)
            ->where('ticket.requestDescription', 'New gov mail account')
            ->has('staffOptions')
            ->where('canEdit', true)
        );
    }

    public function test_super_admin_can_open_it_governance_page_for_ticket(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $ticket = TicketRequest::factory()->create();

        $response = $this
            ->actingAs($superAdmin)
            ->get(route('requests.it-governance.show', $ticket));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/ItGovernanceRequest')
            ->has('ticket')
            ->where('canEdit', true)
        );
    }

    public function test_equipment_and_network_request_page_renders(): void
    {
        $admin = User::factory()->admin()->create();
        $nature = NatureOfRequest::query()->firstWhere('name', 'Computer repair')
            ?? NatureOfRequest::create(['name' => 'Computer repair', 'is_active' => true]);
        $ticket = TicketRequest::factory()->create([
            'nature_of_request_id' => $nature->id,
            'description' => 'Fix keyboard issue',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('requests.equipment-network.show', $ticket));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/EquipmentAndNetwork')
            ->has('ticket')
            ->where('ticket.controlTicketNumber', $ticket->control_ticket_number)
            ->where('ticket.requestDescription', 'Fix keyboard issue')
            ->has('staffOptions')
            ->where('canEdit', true)
        );
    }

    public function test_regular_user_cannot_access_another_users_ticket_on_it_governance(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = TicketRequest::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($user)
            ->get(route('requests.it-governance.show', $ticket));

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_access_another_users_ticket_on_equipment_and_network(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $ticket = TicketRequest::factory()->create(['user_id' => $otherUser->id]);

        $response = $this
            ->actingAs($user)
            ->get(route('requests.equipment-network.show', $ticket));

        $response->assertForbidden();
    }

    public function test_regular_user_cannot_access_it_governance_even_for_own_ticket(): void
    {
        $user = User::factory()->create();
        $ticket = TicketRequest::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->get(route('requests.it-governance.show', $ticket));

        $response->assertForbidden();
    }

    public function test_active_list_shows_only_non_archived_requests_limited_to_twenty_fifo(): void
    {
        $admin = User::factory()->admin()->create();

        $archived = TicketRequest::factory()->create([
            'archived' => true,
            'description' => 'Archived request',
        ]);
        $activeOlder = TicketRequest::factory()->create([
            'archived' => false,
            'created_at' => now()->subMinutes(2),
            'description' => 'Oldest active',
        ]);
        $activeNewer = TicketRequest::factory()->create([
            'archived' => false,
            'created_at' => now()->subMinute(),
            'description' => 'Newer active',
        ]);

        $response = $this->actingAs($admin)->get(route('requests'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/Requests')
            ->has('requests', 2)
            ->where('requests.0.controlTicketNumber', $activeOlder->control_ticket_number)
            ->where('requests.0.requestDescription', 'Oldest active')
            ->where('requests.1.controlTicketNumber', $activeNewer->control_ticket_number)
            ->where('requests.1.requestDescription', 'Newer active')
        );
    }

    public function test_archive_page_displays_archived_requests(): void
    {
        $admin = User::factory()->admin()->create();
        $archivedRequest = TicketRequest::factory()->create([
            'archived' => true,
            'description' => 'Completed and archived',
        ]);

        $response = $this->actingAs($admin)->get(route('requests.archive'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/Archive')
            ->where('isAdmin', true)
            ->has('requests', 1)
            ->where('requests.0.controlTicketNumber', $archivedRequest->control_ticket_number)
            ->where('requests.0.requestDescription', 'Completed and archived')
        );
    }

    public function test_regular_user_sees_only_own_archived_requests(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        TicketRequest::factory()->create([
            'user_id' => $otherUser->id,
            'archived' => true,
            'description' => 'Other user archived',
        ]);
        $ownArchived = TicketRequest::factory()->create([
            'user_id' => $user->id,
            'archived' => true,
            'description' => 'My archived request',
        ]);

        $response = $this->actingAs($user)->get(route('requests.archive'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/Archive')
            ->where('isAdmin', false)
            ->has('requests', 1)
            ->where('requests.0.controlTicketNumber', $ownArchived->control_ticket_number)
        );
    }
}
