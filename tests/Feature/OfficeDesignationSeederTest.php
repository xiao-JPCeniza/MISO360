<?php

namespace Tests\Feature;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use Database\Seeders\OfficeDesignationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfficeDesignationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_expected_office_designations(): void
    {
        $this->seed(OfficeDesignationSeeder::class);

        $offices = ReferenceValue::query()
            ->forGroup(ReferenceValueGroup::OfficeDesignation)
            ->where('system_seeded', true)
            ->where('is_active', true)
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertCount(54, $offices);

        $this->assertContains('Barangay (BRGY)', $offices);
        $this->assertContains('Bureau of Internal Revenue (BIR)', $offices);
        $this->assertContains('Department of Health - Region X (DOH-X)', $offices);
        $this->assertContains('Management Information Systems Office (MISO)', $offices);
        $this->assertContains('Parole and Probation (PAP)', $offices);
        $this->assertContains('Sports Development Division (SDD)', $offices);
        $this->assertContains('Others', $offices);
    }
}
