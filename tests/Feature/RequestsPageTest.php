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

    public function test_admin_sees_all_requests(): void
    {
        $admin = User::factory()->admin()->create();

        TicketRequest::factory()->create([
            'created_at' => now()->subMinute(),
        ]);
        $latestRequest = TicketRequest::factory()->create([
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
            ->where('requests.0.controlTicketNumber', $latestRequest->control_ticket_number)
            ->where('requests.0.positionTitle', $latestRequest->user->position_title)
            ->where('requests.0.requestDescription', 'Network connection issue.')
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

    public function test_regular_user_can_access_own_ticket_on_it_governance(): void
    {
        $user = User::factory()->create();
        $ticket = TicketRequest::factory()->create(['user_id' => $user->id]);

        $response = $this
            ->actingAs($user)
            ->get(route('requests.it-governance.show', $ticket));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('requests/ItGovernanceRequest')
            ->has('ticket')
            ->where('canEdit', false)
        );
    }
}
