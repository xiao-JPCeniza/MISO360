<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use App\Models\TicketRequest;
use App\Models\User;
use App\Notifications\TicketCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class TicketCompletedNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_receives_notification_when_ticket_transitions_to_completed(): void
    {
        $completedStatus = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::Status->value,
            'name' => 'Completed',
            'system_seeded' => true,
            'is_active' => true,
        ]);

        $pendingStatus = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::Status->value,
            'name' => 'Pending',
            'system_seeded' => true,
            'is_active' => true,
        ]);

        /** @var User $admin */
        $admin = User::factory()->admin()->create();
        /** @var User $user */
        $user = User::factory()->create();

        $ticket = TicketRequest::factory()->create([
            'user_id' => $user->id,
            'requested_for_user_id' => $user->id,
            'status_id' => $pendingStatus->id,
        ]);

        $csrfToken = 'test-token';

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->patch(route('requests.it-governance.update', $ticket), [
                '_token' => $csrfToken,
                'statusId' => $completedStatus->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => TicketCompletedNotification::class,
        ]);
    }

    public function test_notification_mark_read_marks_notification_as_read(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        /** @var TicketRequest $ticket */
        $ticket = TicketRequest::factory()->create([
            'user_id' => $user->id,
            'requested_for_user_id' => $user->id,
        ]);

        $user->notify(new TicketCompletedNotification($ticket));

        $notification = DatabaseNotification::query()
            ->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id)
            ->firstOrFail();

        $csrfToken = 'test-token';

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post(route('notifications.mark-read', $notification), [
                '_token' => $csrfToken,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $notification->refresh();
        $this->assertNotNull($notification->read_at);
    }
}
