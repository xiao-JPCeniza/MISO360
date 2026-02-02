<?php

namespace Tests\Feature;

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
            ->component('Requests')
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
            ->component('Requests')
            ->where('isAdmin', true)
            ->has('requests', 2)
            ->where('requests.0.controlTicketNumber', $latestRequest->control_ticket_number)
            ->where('requests.0.positionTitle', $latestRequest->user->position_title)
            ->where('requests.0.requestDescription', 'Network connection issue.')
        );
    }
}
