<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Updated Name',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
    }

    public function test_user_can_delete_their_account()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete(route('profile.destroy'), [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_admin_can_update_own_role()
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->patch('/settings/profile', [
                'name' => $admin->name,
                'role' => 'super_admin',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $admin->refresh();

        $this->assertSame('super_admin', $admin->role->value);
    }

    public function test_regular_user_cannot_update_role()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/settings/profile', [
                'name' => 'Updated Name',
                'role' => 'admin',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        // Role should remain unchanged
        $this->assertSame('user', $user->role->value);
    }

    public function test_admin_can_see_role_field_in_profile()
    {
        $admin = User::factory()->admin()->create();

        $response = $this
            ->actingAs($admin)
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('canManageRoles')
            ->where('canManageRoles', true)
            ->has('roleOptions')
        );
    }

    public function test_regular_user_cannot_see_role_field_in_profile()
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('canManageRoles')
            ->where('canManageRoles', false)
        );
    }
}
