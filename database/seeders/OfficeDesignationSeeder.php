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
            // Existing
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

            // Added (from your list - unique)
            'Association of Barangay Council',
            'BIR',
            'Barangay Agriculture Technicians',
            'Commission of Elections',
            'Day Care Operational Expense',
            'Department of Health - Region X',
            'Early Childhood Care Development',
            'Management Information Systems Office',
            'Municipal Accounting Office - COA',
            'Municipal Legal Office',
            'Municipal Slaughter Division',
            'National Office',
            'Negosyo Center',
            'Office of Agricultural and Biosystems Division',
            'Office of Manolo Fortich Roads and Traffic Administration Division',
            'Office of Municipal Information',
            'Office of Municipal Local Disaster Risk Reduction and Management',
            'Office of Municipal Local Youth and Development',
            'Office of Municipal Motorpool Division',
            'Office of Municipal Nutrition Action Division',
            'Office of Municipal Population Development Division',
            'Office of Municipal Tourism',
            'Office of Municipal Manolo Fortich Memorial Park',
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
            'Office of the Municipal Market',
            'Office of the Municipal National Commission on Indigenous People',
            'Office of the Municipal Persons with Disability Affairs',
            'Office of the Municipal Planning and Development Coordinator',
            'Office of the Municipal Population and Development',
            'Office of the Municipal Procurement Division',
            'Office of the Municipal Registrar',
            'Office of the Municipal Senior Citizens Association',
            'Office of the Municipal Social Welfare and Development',
            'Office of the Municipal Manolo Fortich Technical Skills and Development Center',
            'Office of the Public Employment Service',
            'Office of the Sangguniang Bayan',
            'Parole and Probation',
            'Provincial Capitol',
            'Special Education Fund Division',
            'Sports Development Division',
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
