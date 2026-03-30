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
            'Barangay (BRGY)',
            'Bureau of Internal Revenue (BIR)',
            'Bureau of Fire Protection (BFP) Liaison (BFP)',
            'Business Permits and Licensing Office (BPLO)',
            'Commission of Elections (COMELEC)',
            'Department of Health - Region X (DOH-X)',
            'General Services Office (GSO)',
            'Local Civil Registry Office (LCR)',
            'Management Information Systems Office (MISO)',
            'Municipal Accounting Office (MACCO)',
            'Municipal Administrator\'s Office (OMA)',
            'Municipal Agriculture Office (MAO)',
            'Municipal Assessor\'s Office (MASSO)',
            'Municipal Budget Office (MBO)',
            'Municipal Disaster Risk Reduction and Management Office (MDRRMO)',
            'Municipal Engineering Office (MEO)',
            'Municipal Environment and Natural Resources Office (MENRO)',
            'Municipal Health Office (MHO)',
            'Municipal Human Resource Management Office (MHRMO)',
            'Municipal Legal Office (MLO)',
            'Municipal Planning and Development Office (MPDO)',
            'Municipal Police Station (MPS)',
            'Municipal Slaughter Division (SLH)',
            'Municipal Social Welfare and Development Office (MSWDO)',
            'Municipal Treasurer\'s Office (MTO)',
            'Negosyo Center (NC)',
            'Office of Agricultural and Biosystems Division (ABED)',
            'Office of Manolo Fortich Roads and Traffic Administration Division (MFRTA)',
            'Office of Municipal Information (MIO)',
            'Office of Municipal Local Youth and Development (LYDO)',
            'Office of Municipal Manolo Fortich Memorial Park (MFMP)',
            'Office of Municipal Motorpool Division (Motorpool)',
            'Office of Municipal Nutrition Action Division (MNAO)',
            'Office of Municipal Population Development (POPDEV)',
            'Office of Municipal Tourism (TO)',
            'Office of the Building Official (OBO)',
            'Office of the Municipal Cooperative Development (MCDO)',
            'Office of the Municipal Department of Interior and Local Government (DILG)',
            'Office of the Municipal Internal Audit Service (IAS)',
            'Office of the Municipal Local Economic Investment Promotion (LEDIPO)',
            'Office of the Municipal Manolo Fortich Technical Skills and Development Center (TSDC)',
            'Office of the Municipal Market (MKT)',
            'Office of the Municipal Mayor (MMO)',
            'Office of the Municipal National Commission on Indigenous People (MO-IP)',
            'Office of the Municipal Persons with Disability Affairs (PDAO)',
            'Office of the Municipal Procurement Division (PROC)',
            'Office of the Municipal Registrar (MCR)',
            'Office of the Municipal Senior Citizens Association (OSCA)',
            'Office of the Municipal Vice Mayor (VMO)',
            'Office of the Public Employment Service (PESO)',
            'Office of the Sangguniang Bayan (SB)',
            'Parole and Probation (PAP)',
            'Sports Development Division (SDD)',
            'Others',
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
