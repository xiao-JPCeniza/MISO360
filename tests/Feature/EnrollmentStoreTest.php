<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\IssuedUid;
use App\Models\NatureOfRequest;
use App\Models\ReferenceValue;
use App\Models\TicketEnrollment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EnrollmentStoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_enrollment_store_persists_all_sections(): void
    {
        $admin = User::factory()->admin()->create();

        IssuedUid::create(['uid' => 'MIS-UID-00002', 'sequence' => 2]);

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'Main Office',
            'is_active' => true,
        ]);
        $equipmentType = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::EquipmentType->value,
            'name' => 'Laptop',
            'is_active' => true,
        ]);
        $status = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::Status->value,
            'name' => 'Under Warranty',
            'is_active' => true,
        ]);
        $remark = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::Remarks->value,
            'name' => 'Checked OK',
            'is_active' => true,
        ]);

        NatureOfRequest::create([
            'name' => 'Repair',
            'is_active' => true,
        ]);

        $payload = [
            'uniqueId' => 'MIS-UID-00002',
            'equipmentName' => 'Main Office',
            'officeId' => $office->id,
            'equipmentType' => $equipmentType->name,
            'brand' => 'Dell',
            'model' => 'Latitude 5420',
            'serialNumber' => 'SN-12345',
            'assetTag' => 'TAG-99',
            'supplier' => 'Vendor Co',
            'purchaseDate' => '2025-01-15',
            'expiryDate' => '2028-01-15',
            'warrantyStatus' => $status->name,
            'equipmentImageUrls' => [],
            'specification' => [
                'memory' => '16 GB',
                'storage' => '512 GB SSD',
                'operatingSystem' => 'Windows 11',
                'networkAddress' => '10.0.0.5',
                'accessories' => 'Dock, charger',
            ],
            'locationAssignment' => [
                'assignedTo' => 'Jane Doe',
                'officeDivision' => 'Main Office',
                'dateIssued' => '2025-02-01',
            ],
            'requestHistory' => [
                'natureOfRequest' => 'Repair',
                'date' => '2025-03-01',
                'actionTaken' => 'Replaced SSD',
                'assignedStaff' => 'Tech Team',
                'remarks' => $remark->name,
            ],
            'scheduledMaintenance' => [
                'date' => '2026-04-01',
                'remarks' => 'Annual check',
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), $payload)
            ->assertRedirect(route('inventory.show', ['uniqueId' => 'MIS-UID-00002']));

        $this->assertDatabaseHas('ticket_enrollments', [
            'unique_id' => 'MIS-UID-00002',
            'equipment_name' => 'Main Office',
            'office_id' => $office->id,
            'equipment_type' => 'Laptop',
            'brand' => 'Dell',
            'model' => 'Latitude 5420',
            'serial_number' => 'SN-12345',
            'asset_tag' => 'TAG-99',
            'supplier' => 'Vendor Co',
            'warranty_status' => 'Under Warranty',
            'spec_memory' => '16 GB',
            'spec_storage' => '512 GB SSD',
            'spec_operating_system' => 'Windows 11',
            'spec_network_address' => '10.0.0.5',
            'spec_accessories' => 'Dock, charger',
            'location_assigned_to' => 'Jane Doe',
            'location_office_division' => 'Main Office',
            'request_nature' => 'Repair',
            'request_action_taken' => 'Replaced SSD',
            'request_assigned_staff' => 'Tech Team',
            'request_remarks' => 'Checked OK',
            'maintenance_remarks' => 'Annual check',
        ]);

        $enrollment = TicketEnrollment::where('unique_id', 'MIS-UID-00002')->first();
        $this->assertNotNull($enrollment);
        $this->assertSame('2025-01-15', $enrollment->purchase_date->format('Y-m-d'));
        $this->assertSame('2028-01-15', $enrollment->expiry_date->format('Y-m-d'));
        $this->assertSame('2025-02-01', $enrollment->location_date_issued->format('Y-m-d'));
        $this->assertSame('2025-03-01', $enrollment->request_date->format('Y-m-d'));
        $this->assertSame('2026-04-01', $enrollment->maintenance_date->format('Y-m-d'));
    }

    public function test_admin_enrollment_store_persists_identification_and_specification_when_other_sections_empty(): void
    {
        $admin = User::factory()->admin()->create();

        IssuedUid::create(['uid' => 'MIS-UID-00088', 'sequence' => 88]);

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'HQ',
            'is_active' => true,
        ]);
        $equipmentType = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::EquipmentType->value,
            'name' => 'Desktop',
            'is_active' => true,
        ]);

        $payload = [
            'uniqueId' => 'MIS-UID-00088',
            'equipmentName' => 'HQ',
            'officeId' => $office->id,
            'equipmentType' => $equipmentType->name,
            'brand' => 'Lenovo',
            'model' => 'ThinkCentre',
            'serialNumber' => 'SN-888',
            'assetTag' => '',
            'supplier' => '',
            'purchaseDate' => '',
            'expiryDate' => '',
            'warrantyStatus' => '',
            'equipmentImageUrls' => [],
            'specification' => [
                'memory' => '32 GB',
                'storage' => '1 TB',
                'operatingSystem' => 'Linux',
                'networkAddress' => '',
                'accessories' => '',
            ],
            'locationAssignment' => [
                'assignedTo' => '',
                'officeDivision' => 'HQ',
                'dateIssued' => '',
            ],
            'requestHistory' => [
                'natureOfRequest' => '',
                'date' => '',
                'actionTaken' => '',
                'assignedStaff' => '',
                'remarks' => '',
            ],
            'scheduledMaintenance' => [
                'date' => '',
                'remarks' => '',
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.enrollments.store'), $payload)
            ->assertRedirect(route('inventory.show', ['uniqueId' => 'MIS-UID-00088']));

        $enrollment = TicketEnrollment::where('unique_id', 'MIS-UID-00088')->first();
        $this->assertNotNull($enrollment);
        $this->assertSame('32 GB', $enrollment->spec_memory);
        $this->assertSame('1 TB', $enrollment->spec_storage);
        $this->assertSame('Linux', $enrollment->spec_operating_system);
        $this->assertNull($enrollment->location_assigned_to);
        $this->assertNull($enrollment->request_nature);
        $this->assertNull($enrollment->maintenance_date);
    }

    public function test_admin_enrollment_update_clears_optional_fields_when_payload_sends_empty_strings(): void
    {
        $admin = User::factory()->admin()->create();

        IssuedUid::create(['uid' => 'MIS-UID-00077', 'sequence' => 77]);

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'Branch A',
            'is_active' => true,
        ]);
        $equipmentType = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::EquipmentType->value,
            'name' => 'Printer',
            'is_active' => true,
        ]);

        $enrollment = TicketEnrollment::create([
            'unique_id' => 'MIS-UID-00077',
            'equipment_name' => 'Branch A',
            'office_id' => $office->id,
            'equipment_type' => $equipmentType->name,
            'brand' => 'HP',
            'model' => 'LaserJet',
            'spec_memory' => '16 GB',
            'location_assigned_to' => 'John',
            'location_office_division' => 'Branch A',
            'request_nature' => 'Repair',
            'maintenance_remarks' => 'Soon',
        ]);

        $payload = [
            'uniqueId' => $enrollment->unique_id,
            'equipmentName' => 'Branch A',
            'officeId' => $office->id,
            'equipmentType' => $equipmentType->name,
            'brand' => 'HP',
            'model' => 'LaserJet',
            'serialNumber' => '',
            'assetTag' => '',
            'supplier' => '',
            'purchaseDate' => '',
            'expiryDate' => '',
            'warrantyStatus' => '',
            'equipmentImageUrls' => [],
            'specification' => [
                'memory' => '',
                'storage' => '',
                'operatingSystem' => '',
                'networkAddress' => '',
                'accessories' => '',
            ],
            'locationAssignment' => [
                'assignedTo' => '',
                'officeDivision' => 'Branch A',
                'dateIssued' => '',
            ],
            'requestHistory' => [
                'natureOfRequest' => '',
                'date' => '',
                'actionTaken' => '',
                'assignedStaff' => '',
                'remarks' => '',
            ],
            'scheduledMaintenance' => [
                'date' => '',
                'remarks' => '',
            ],
        ];

        $this->actingAs($admin)
            ->post(route('admin.enrollments.update', ['uniqueId' => $enrollment->unique_id]), $payload)
            ->assertRedirect(route('inventory.show', ['uniqueId' => $enrollment->unique_id]));

        $enrollment->refresh();
        $this->assertNull($enrollment->spec_memory);
        $this->assertNull($enrollment->location_assigned_to);
        $this->assertNull($enrollment->request_nature);
        $this->assertNull($enrollment->maintenance_remarks);
    }

    public function test_admin_enrollment_update_accepts_legacy_path_without_update_segment(): void
    {
        $admin = User::factory()->admin()->create();

        IssuedUid::create(['uid' => 'MIS-UID-00021', 'sequence' => 21]);

        $office = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::OfficeDesignation->value,
            'name' => 'Main Office',
            'is_active' => true,
        ]);
        $equipmentType = ReferenceValue::factory()->create([
            'group_key' => ReferenceValueGroup::EquipmentType->value,
            'name' => 'Laptop',
            'is_active' => true,
        ]);

        $enrollment = TicketEnrollment::create([
            'unique_id' => 'MIS-UID-00021',
            'equipment_name' => 'Asset',
            'office_id' => $office->id,
            'equipment_type' => $equipmentType->name,
            'brand' => 'Dell',
            'model' => 'X1',
        ]);

        $payload = [
            'uniqueId' => $enrollment->unique_id,
            'equipmentName' => 'Main Office',
            'officeId' => $office->id,
            'equipmentType' => $equipmentType->name,
            'brand' => 'Dell',
            'model' => 'X2',
            'serialNumber' => '',
            'assetTag' => '',
            'supplier' => '',
            'purchaseDate' => '',
            'expiryDate' => '',
            'warrantyStatus' => '',
            'equipmentImageUrls' => [],
            'specification' => [
                'memory' => '',
                'storage' => '',
                'operatingSystem' => '',
                'networkAddress' => '',
                'accessories' => '',
            ],
            'locationAssignment' => [
                'assignedTo' => '',
                'officeDivision' => 'Main Office',
                'dateIssued' => '',
            ],
            'requestHistory' => [
                'natureOfRequest' => '',
                'date' => '',
                'actionTaken' => '',
                'assignedStaff' => '',
                'remarks' => '',
            ],
            'scheduledMaintenance' => [
                'date' => '',
                'remarks' => '',
            ],
        ];

        $this->actingAs($admin)
            ->post('/admin/enrollments/'.$enrollment->unique_id, $payload)
            ->assertRedirect(route('inventory.show', ['uniqueId' => $enrollment->unique_id]));

        $enrollment->refresh();
        $this->assertSame('X2', $enrollment->model);
    }
}
