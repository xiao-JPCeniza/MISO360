<?php

namespace Database\Seeders;

use App\Models\NatureOfRequest;
use Illuminate\Database\Seeder;

class NatureOfRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $requests = [
            'Application software installation and driver updates',
            'Operating system installation or reformatting (with data backup)',
            'Operating system installation or reformatting (without data backup)',
            'Biometric system installation',
            'Data and system file backup',
            'Annual SSD health monitoring',
            'Data recovery services',
            'Market system support',
            'Office 365 and MS Office/Windows license management',
            'End-user equipment setup and relocation',
            'ICT hardware and software issue assessment',
            'UTP and fiber optic cable installation and inspection',
            'SSD/HDD and RAM/power supply replacement',
            'Laptop and printer repair',
            'Printer sharing setup',
            'Server and network switch repair or VLAN configuration',
            'CCTV and wireless network maintenance',
            'Network troubleshooting',
            'ICT asset inspection and recommendation reports',
            'Borrowed equipment issuance',
            'Government email account recovery',
            'Internet support for events',
            'CCTV project design, costing, and supervision',
        ];

        foreach ($requests as $request) {
            NatureOfRequest::updateOrCreate(
                ['name' => $request],
                ['is_active' => true],
            );
        }
    }
}
