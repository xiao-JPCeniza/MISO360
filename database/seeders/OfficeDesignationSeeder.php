<?php

namespace Database\Seeders;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
use Illuminate\Database\Seeder;

class OfficeDesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $offices = [
            'Office of the Municipal Mayor',
            'Office of the Municipal Vice Mayor',
            'Sangguniang Bayan (Municipal Council)',
            'Municipal Administrator\'s Office',
            'Municipal Planning and Development Office (MPDO)',
            'Municipal Engineering Office (MEO)',
            'Office of the Building Official (OBO)',
            'Municipal Assessor’s Office',
            'Municipal Treasurer’s Office',
            'Municipal Budget Office',
            'Municipal Accounting Office',
            'Municipal Human Resource Management Office (MHRMO)',
            'Municipal Disaster Risk Reduction and Management Office (MDRRMO)',
            'Municipal Health Office (MHO)',
            'Municipal Social Welfare and Development Office (MSWDO)',
            'Municipal Agriculture Office (MAO)',
            'Municipal Environment and Natural Resources Office (MENRO)',
            'Municipal Information and Communications Technology Office (MICTO/ICT Unit)',
            'Local Civil Registry Office',
            'Business Permits and Licensing Office (BPLO)',
            'Tourism Office',
            'Public Market Office',
            'General Services Office (GSO)',
            'Municipal Library',
            'Municipal Police Station (PNP Liaison)',
            'Bureau of Fire Protection (BFP) Liaison',
        ];

        foreach ($offices as $office) {
            ReferenceValue::updateOrCreate(
                [
                    'group_key' => ReferenceValueGroup::OfficeDesignation->value,
                    'name' => $office,
                ],
                [
                    'system_seeded' => true,
                    'is_active' => true,
                ],
            );
        }
    }
}
