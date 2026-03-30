<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InertiaFlashSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_inertia_shares_warning_and_info_flash_keys(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->withSession([
                'warning' => 'Check your entries.',
                'info' => 'Your draft was saved.',
            ])
            ->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->has('flash')
            ->where('flash.warning', 'Check your entries.')
            ->where('flash.info', 'Your draft was saved.')
        );
    }

    public function test_dashboard_still_loads_when_notifications_table_is_missing(): void
    {
        $user = User::factory()->create();
        Schema::dropIfExists('notifications');

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->where('notifications.unreadCount', 0)
        );
    }
}
