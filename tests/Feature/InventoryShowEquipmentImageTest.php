<?php

namespace Tests\Feature;

use App\Models\TicketEnrollment;
use App\Models\User;
use App\Support\EquipmentImageUrls;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventoryShowEquipmentImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_show_includes_resolved_equipment_image_urls(): void
    {
        $admin = User::factory()->admin()->create();

        TicketEnrollment::create([
            'unique_id' => 'MIS-UID-00063',
            'equipment_name' => 'Test Device',
            'equipment_image' => 'inventory/photo.jpg',
            'equipment_images' => ['inventory/photo.jpg'],
        ]);

        $expected = EquipmentImageUrls::publicUrls(
            ['inventory/photo.jpg'],
            'inventory/photo.jpg',
        );

        $this->actingAs($admin)
            ->get(route('inventory.show', ['uniqueId' => 'MIS-UID-00063']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('inventory/InventoryItem')
                ->where('item.equipmentImageUrls', $expected)
            );
    }
}
