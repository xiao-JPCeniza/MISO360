<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NotificationControllerResilienceTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_data_returns_empty_payload_when_notifications_table_missing(): void
    {
        $user = User::factory()->create();
        Schema::dropIfExists('notifications');

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $response->assertJson([
            'unreadCount' => 0,
            'items' => [],
        ]);
    }

    public function test_mark_all_read_returns_ok_when_notifications_table_missing(): void
    {
        $user = User::factory()->create();
        Schema::dropIfExists('notifications');

        $response = $this->actingAs($user)->postJson(route('notifications.mark-all-read'));

        $response->assertOk();
        $response->assertJson(['ok' => true]);
    }
}
