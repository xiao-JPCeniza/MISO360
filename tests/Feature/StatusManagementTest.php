<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatusManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_reference_values(): void
    {
        $admin = User::factory()->admin()->create();
        $csrfToken = 'test-token';

        $this->actingAs($admin)
            ->get('/admin/status')
            ->assertOk();

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->post('/admin/status', [
                '_token' => $csrfToken,
                'group_key' => ReferenceValueGroup::Status->value,
                'name' => 'Pending',
            ])
            ->assertRedirect('/admin/status');

        $referenceValue = ReferenceValue::firstOrFail();

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
                'name' => 'Ongoing',
            ])
            ->assertRedirect('/admin/status');

        $this->assertDatabaseHas('reference_values', [
            'id' => $referenceValue->id,
            'name' => 'Ongoing',
            'group_key' => ReferenceValueGroup::Status->value,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->delete("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
            ])
            ->assertRedirect('/admin/status');

        $this->assertDatabaseHas('reference_values', [
            'id' => $referenceValue->id,
            'is_active' => false,
        ]);
    }

    public function test_super_admin_can_manage_reference_values(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $csrfToken = 'test-token';

        $this->actingAs($superAdmin)
            ->get('/admin/status')
            ->assertOk();

        $this->actingAs($superAdmin)
            ->withSession(['_token' => $csrfToken])
            ->post('/admin/status', [
                '_token' => $csrfToken,
                'group_key' => ReferenceValueGroup::Remarks->value,
                'name' => 'For Pickup',
            ])
            ->assertRedirect('/admin/status');

        $referenceValue = ReferenceValue::query()
            ->where('group_key', ReferenceValueGroup::Remarks->value)
            ->firstOrFail();

        $this->actingAs($superAdmin)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
                'name' => 'To Deliver',
            ])
            ->assertRedirect('/admin/status');

        $this->actingAs($superAdmin)
            ->withSession(['_token' => $csrfToken])
            ->delete("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
            ])
            ->assertRedirect('/admin/status');

        $this->assertDatabaseHas('reference_values', [
            'id' => $referenceValue->id,
            'is_active' => false,
        ]);
    }

    public function test_regular_user_cannot_manage_reference_values(): void
    {
        $user = User::factory()->create();
        $csrfToken = 'test-token';
        $referenceValue = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::Status->value,
            'name' => 'Pending',
            'is_active' => true,
        ]);

        $this->actingAs($user)
            ->get('/admin/status')
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->post('/admin/status', [
                '_token' => $csrfToken,
                'group_key' => ReferenceValueGroup::Category->value,
                'name' => 'Complex',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
                'name' => 'Ongoing',
            ])
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession(['_token' => $csrfToken])
            ->delete("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
            ])
            ->assertForbidden();
    }

    public function test_admin_can_restore_inactive_reference_value(): void
    {
        $admin = User::factory()->admin()->create();
        $csrfToken = 'test-token';

        $referenceValue = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'HQ',
            'is_active' => false,
        ]);

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
                'name' => 'HQ',
                'is_active' => true,
            ])
            ->assertRedirect('/admin/status');

        $this->assertDatabaseHas('reference_values', [
            'id' => $referenceValue->id,
            'name' => 'HQ',
            'is_active' => true,
        ]);
    }

    public function test_restore_redirects_with_success_flash(): void
    {
        $admin = User::factory()->admin()->create();
        $csrfToken = 'test-token';

        $referenceValue = ReferenceValue::create([
            'group_key' => ReferenceValueGroup::Remarks->value,
            'name' => 'Remote',
            'is_active' => false,
        ]);

        $response = $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->patch("/admin/status/{$referenceValue->id}", [
                '_token' => $csrfToken,
                'name' => 'Remote',
                'is_active' => true,
            ]);

        $response->assertRedirect('/admin/status');
        $response->assertSessionHas('status');
        $this->assertStringContainsString('restored', (string) $response->getSession()->get('status'));
    }

    public function test_update_nonexistent_reference_value_returns_404(): void
    {
        $admin = User::factory()->admin()->create();
        $csrfToken = 'test-token';

        $this->actingAs($admin)
            ->withSession(['_token' => $csrfToken])
            ->patch('/admin/status/99999', [
                '_token' => $csrfToken,
                'name' => 'Any',
                'is_active' => true,
            ])
            ->assertNotFound();
    }

    public function test_options_endpoint_returns_active_reference_values(): void
    {
        $user = User::factory()->create();

        ReferenceValue::create([
            'group_key' => ReferenceValueGroup::Remarks->value,
            'name' => 'For Pickup',
            'is_active' => true,
        ]);

        ReferenceValue::create([
            'group_key' => ReferenceValueGroup::Remarks->value,
            'name' => 'Inactive remark',
            'is_active' => false,
        ]);

        $this->actingAs($user)
            ->get('/reference-values/options')
            ->assertOk()
            ->assertJsonFragment(['name' => 'For Pickup'])
            ->assertJsonMissing(['name' => 'Inactive remark']);
    }
}
