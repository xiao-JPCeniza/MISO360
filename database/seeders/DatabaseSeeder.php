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
            'role' => 'admin',
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
        ]);

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@miso.gov.ph',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
            'workos_id' => 'local-super-admin-'.Str::uuid(),
            'avatar' => '',
        ]);

        User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@miso.gov.ph',
            'password' => Hash::make('password'),
            'role' => 'user',
            'workos_id' => 'local-user-'.Str::uuid(),
            'avatar' => '',
        ]);
    }
}
