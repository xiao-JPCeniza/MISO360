<?php

namespace Database\Seeders;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use Illuminate\Database\Seeder;

class ReferenceValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $defaults = [
            ReferenceValueGroup::Category->value => [
                'Simple',
                'Complex',
                'Urgent',
            ],
            ReferenceValueGroup::Status->value => [
                'Pending',
                'Ongoing',
                'Completed',
            ],
            ReferenceValueGroup::Remarks->value => [
                'For Pickup',
                'To Deliver',
                'Remote',
            ],
        ];

        foreach ($defaults as $groupKey => $names) {
            foreach ($names as $name) {
                ReferenceValue::updateOrCreate(
                    [
                        'group_key' => $groupKey,
                        'name' => $name,
                    ],
                    [
                        'system_seeded' => true,
                        'is_active' => true,
                    ],
                );
            }
        }
    }
}
