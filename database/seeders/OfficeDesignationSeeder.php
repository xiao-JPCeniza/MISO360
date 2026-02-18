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
            'Association of Barangay Council',
            'Barangay Agriculture Technicians',
            'BIR',
            'BRGY - Agusan Canyon',
            'BRGY - Alae',
            'BRGY - Dahilayan',
            'BRGY - Dalirig',
            'BRGY - Damilag',
            'BRGY - Diclum',
            'BRGY - Guilang-guilang',
            'BRGY - Kalugmanan',
            'BRGY - Lindaban',
            'BRGY - Lingion',
            'BRGY - Lunocan',
            'BRGY - Maluko',
            'BRGY - Mambatangan',
            'BRGY - Mampayag',
            'BRGY - Mantibugao',
            'BRGY - Minsuro',
            'BRGY - San Miguel',
            'BRGY - Sankanan',
            'BRGY - Santiago',
            'BRGY - Santo Niño',
            'BRGY - Tankulan',
            'Bureau of Fire Protection (BFP) Liaison',
            'Business Permits and Licensing Office (BPLO)',
            'Commission of Elections',
            'Day Care Operational Expense',
            'Department of Health - Region X',
            'Early Childhood Care Development',
            'General Services Office (GSO)',
            'Local Civil Registry Office',
            'Management Information Systems Office',
            'Municipal Accounting Office',
            'Municipal Accounting Office - COA',
            'Municipal Administrator\'s Office',
            'Municipal Agriculture Office (MAO)',
            'Municipal Assessor\'s Office',
            'Municipal Budget Office',
            'Municipal Disaster Risk Reduction and Management Office (MDRRMO)',
            'Municipal Engineering Office (MEO)',
            'Municipal Environment and Natural Resources Office (MENRO)',
            'Municipal Health Office (MHO)',
            'Municipal Human Resource Management Office (MHRMO)',
            'Municipal Information and Communications Technology Office (MICTO/ICT Unit)',
            'Municipal Legal Office',
            'Municipal Library',
            'Municipal Planning and Development Office (MPDO)',
            'Municipal Police Station (PNP Liaison)',
            'Municipal Slaughter Division',
            'Municipal Social Welfare and Development Office (MSWDO)',
            'Municipal Treasurer\'s Office',
            'National Office',
            'Negosyo Center',
            'Office of Agricultural and Biosystems Division',
            'Office of Manolo Fortich Roads and Traffic Administration Division',
            'Office of Municipal Information',
            'Office of Municipal Local Disaster Risk Reduction and Management',
            'Office of Municipal Local Youth and Development',
            'Office of Municipal Manolo Fortich Memorial Park',
            'Office of Municipal Motorpool Division',
            'Office of Municipal Nutrition Action Division',
            'Office of Municipal Population Development Division',
            'Office of Municipal Tourism',
            'Office of the Building Official (OBO)',
            'Office of the Municipal Accountant',
            'Office of the Municipal Agriculturist',
            'Office of the Municipal Assessor',
            'Office of the Municipal Budget',
            'Office of the Municipal Business Permit and Licensing Division',
            'Office of the Municipal Cooperative Development',
            'Office of the Municipal Department of Interior and Local Government',
            'Office of the Municipal Engineer',
            'Office of the Municipal Environment and Natural Resources',
            'Office of the Municipal General Services',
            'Office of the Municipal Health',
            'Office of the Municipal Heavy Equipment Division',
            'Office of the Municipal Human Resource and Management',
            'Office of the Municipal Internal Audit Service',
            'Office of the Municipal Local Economic Investment Promotion',
            'Office of the Municipal Manolo Fortich Technical Skills and Development Center',
            'Office of the Municipal Market',
            'Office of the Municipal Mayor',
            'Office of the Municipal National Commission on Indigenous People',
            'Office of the Municipal Persons with Disability Affairs',
            'Office of the Municipal Planning and Development Coordinator',
            'Office of the Municipal Population and Development',
            'Office of the Municipal Procurement Division',
            'Office of the Municipal Registrar',
            'Office of the Municipal Senior Citizens Association',
            'Office of the Municipal Social Welfare and Development',
            'Office of the Municipal Vice Mayor',
            'Office of the Public Employment Service',
            'Office of the Sangguniang Bayan',
            'Parole and Probation',
            'Provincial Capitol',
            'Public Market Office',
            'Sangguniang Bayan (Municipal Council)',
            'Special Education Fund Division',
            'Sports Development Division',
            'Tourism Office',
        ];

        // Optional: ensure uniqueness at runtime (extra safety)
        $offices = array_values(array_unique($offices));

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
