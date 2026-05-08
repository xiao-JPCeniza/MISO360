<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Tests\TestCase;

class NotificationPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_data_is_paginated_and_includes_pagination_meta(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        for ($i = 1; $i <= 9; $i++) {
            $user->notify(new class($i) extends Notification
            {
                public function __construct(private readonly int $i) {}

                public function via(object $notifiable): array
                {
                    return ['database'];
                }

                public function toArray(object $notifiable): array
                {
                    return [
                        'kind' => 'test_notification',
                        'title' => 'Test',
                        'message' => 'Notification '.$this->i,
                        'url' => '/dashboard',
                    ];
                }
            });
        }

        $page1 = $this->actingAs($user)->getJson(route('notifications.index', ['page' => 1]));
        $page1->assertOk();
        $page1->assertJsonPath('unreadCount', 9);
        $this->assertCount(8, $page1->json('items'));
        $page1->assertJsonPath('pagination.currentPage', 1);
        $page1->assertJsonPath('pagination.lastPage', 2);
        $page1->assertJsonPath('pagination.perPage', 8);
        $page1->assertJsonPath('pagination.total', 9);

        $page2 = $this->actingAs($user)->getJson(route('notifications.index', ['page' => 2]));
        $page2->assertOk();
        $this->assertCount(1, $page2->json('items'));
        $page2->assertJsonPath('pagination.currentPage', 2);
    }

    public function test_notifications_payload_includes_read_at_state(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $user->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return [
                    'kind' => 'test_notification',
                    'title' => 'Test',
                    'message' => 'Unread',
                    'url' => '/dashboard',
                ];
            }
        });

        $user->notify(new class extends Notification
        {
            public function via(object $notifiable): array
            {
                return ['database'];
            }

            public function toArray(object $notifiable): array
            {
                return [
                    'kind' => 'test_notification',
                    'title' => 'Test',
                    'message' => 'Read',
                    'url' => '/dashboard',
                ];
            }
        });

        $read = $user->notifications()->where('data->message', 'Read')->firstOrFail();
        $read->markAsRead();

        $response = $this->actingAs($user)->getJson(route('notifications.index'));
        $response->assertOk();

        $items = $response->json('items');
        $this->assertCount(2, $items);

        $readItem = collect($items)->firstWhere('id', (string) $read->id);
        $this->assertNotNull($readItem['readAt'] ?? null);
    }
}
