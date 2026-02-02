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
            'System account creation',
            'System modification',
            'Password reset or account recovery (gov mail)',
            'System error / bug report',
            'Request for new system module or enhancement',
            'System Development',
            'Software license or activation request',
            'Computer repair',
            'Laptop repair',
            'Printer repair',
            'CCTV issue/repair',
            'End-User Equipment Installation, Setup, and Configuration (Connection of Computers, Monitors, Printers, Peripherals, and Workstation Relocation)',
            'Request for new IT equipment (e.g., PC, printer, UPS)',
            'Install/Reformat Operating System',
            'Installation of application software',
            'Network Connectivity Installation, Repair, and Maintenance Services (LAN and Fiber Optic Cabling, Network and Wireless Setup, Repairs, Upgrades, and Network Equipment Deployment)',
            'End-User Devices Component Replacement',
            'Assess extent of hardware/software failure',
            'System Reinstallation/Troubleshooting (TOIMS, GAAMS, ECPAC)',
            'Inspect Unit',
            'Borrow Unit',
            'Data Recovery',
        ];

        foreach ($requests as $request) {
            NatureOfRequest::updateOrCreate(
                ['name' => $request],
                ['is_active' => true],
            );
        }
    }
}
