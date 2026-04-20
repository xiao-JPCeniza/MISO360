<?php

namespace Tests\Feature;

use App\Models\TicketRequest;
use App\Models\User;
use App\Notifications\Admin\NewTicketRequestSubmittedNotification;
use App\Notifications\TicketCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminInAppNotificationFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_and_super_admin_notifications_index_excludes_end_user_only_kinds(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->superAdmin()->create()] as $admin) {
            $ticket = TicketRequest::factory()->create([
                'user_id' => $admin->id,
                'requested_for_user_id' => $admin->id,
            ]);

            $admin->notify(new TicketCompletedNotification($ticket));
            $admin->notify(new NewTicketRequestSubmittedNotification($ticket));

            $response = $this->actingAs($admin)->getJson(route('notifications.index'));

            $response->assertOk();
            $response->assertJsonPath('unreadCount', 1);
            $items = $response->json('items');
            $this->assertCount(1, $items);
            $this->assertSame('new_ticket_request_submitted', $items[0]['data']['kind'] ?? null);
        }
    }

    public function test_regular_user_sees_ticket_completed_notifications(): void
    {
        $user = User::factory()->create();
        $ticket = TicketRequest::factory()->create([
            'user_id' => $user->id,
            'requested_for_user_id' => $user->id,
        ]);

        $user->notify(new TicketCompletedNotification($ticket));

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJsonPath('unreadCount', 1);
        $items = $response->json('items');
        $this->assertCount(1, $items);
        $this->assertSame('ticket_completed', $items[0]['data']['kind'] ?? null);
    }

    public function test_admin_cannot_mark_read_hidden_notification_via_api(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = TicketRequest::factory()->create([
            'user_id' => $admin->id,
            'requested_for_user_id' => $admin->id,
        ]);

        $admin->notify(new TicketCompletedNotification($ticket));

        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)
            ->postJson(route('notifications.mark-read', $notification))
            ->assertNotFound();
    }

    public function test_admin_mark_all_read_only_marks_admin_visible_notifications(): void
    {
        $admin = User::factory()->admin()->create();
        $ticket = TicketRequest::factory()->create([
            'user_id' => $admin->id,
            'requested_for_user_id' => $admin->id,
        ]);

        $admin->notify(new TicketCompletedNotification($ticket));
        $admin->notify(new NewTicketRequestSubmittedNotification($ticket));

        $this->assertSame(2, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->postJson(route('notifications.mark-all-read'))->assertOk();

        $ticketCompleted = $admin->notifications()->where('data->kind', 'ticket_completed')->firstOrFail();
        $newTicket = $admin->notifications()->where('data->kind', 'new_ticket_request_submitted')->firstOrFail();

        $this->assertNull($ticketCompleted->fresh()->read_at);
        $this->assertNotNull($newTicket->fresh()->read_at);
    }
}
