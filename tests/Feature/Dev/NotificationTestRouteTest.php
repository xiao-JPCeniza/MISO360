<?php

namespace Tests\Feature\Dev;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NotificationTestRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_view_notification_test_page(): void
    {
        $this->get(route('dev.notifications-test'))
            ->assertRedirect();
    }

    public function test_authenticated_user_can_view_notification_test_page_in_testing(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dev.notifications-test'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('dev/NotificationTest')
        );
    }

    public function test_flash_trigger_redirects_with_expected_session_key(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dev.notifications-test.flash', ['type' => 'warning']))
            ->assertRedirect(route('dev.notifications-test'))
            ->assertSessionHas('warning');
    }
}
