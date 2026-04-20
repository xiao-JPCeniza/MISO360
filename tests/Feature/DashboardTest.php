<?php

namespace Tests\Feature;

use App\Models\NatureOfRequest;
use App\Models\TicketRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page()
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_visit_the_dashboard()
    {
        /** @var User $user */
        $user = User::factory()->create();

        $this->actingAs($user);

        $this->get('/dashboard')->assertOk();
    }

    public function test_unverified_users_are_redirected_to_verification_notice()
    {
        $this->actingAs(User::factory()->unverified()->create());

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_dashboard_does_not_show_borrow_unit_requests_in_queue_or_counts(): void
    {
        $user = User::factory()->create();

        $borrowNature = NatureOfRequest::create([
            'name' => 'Borrow Unit',
            'is_active' => true,
        ]);

        TicketRequest::factory()->create([
            'user_id' => $user->id,
            'nature_of_request_id' => $borrowNature->id,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('dashboard/Dashboard')
            ->where('queuedCountForUser', 0)
            ->where('totalRequestsForUser', 0)
            ->where('activeQueueTotal', 0)
            ->has('currentQueue', 0)
        );
    }
}
