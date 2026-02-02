<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@miso.gov.ph',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
        ]);

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@miso.gov.ph',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-super-admin-'.Str::uuid(),
            'avatar' => '',
        ]);

        User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@miso.gov.ph',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'user',
            'two_factor_enabled' => false,
            'workos_id' => 'local-user-'.Str::uuid(),
            'avatar' => '',
        ]);

        $this->call(NatureOfRequestSeeder::class);
        $this->call(ReferenceValueSeeder::class);
        $this->call(OfficeDesignationSeeder::class);
    }
}
