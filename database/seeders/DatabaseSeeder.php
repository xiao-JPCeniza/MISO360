<?php

namespace Database\Seeders;

use App\Enums\ReferenceValueGroup;
use App\Models\ReferenceValue;
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
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
        ]);

        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-super-admin-'.Str::uuid(),
            'avatar' => '',
        ]);

        User::factory()->create([
            'name' => 'Standard User',
            'email' => 'user@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'user',
            'two_factor_enabled' => false,
            'workos_id' => 'local-user-'.Str::uuid(),
            'avatar' => '',
        ]);

        $misoOffice = ReferenceValue::updateOrCreate(
            [
                'group_key' => ReferenceValueGroup::OfficeDesignation->value,
                'name' => 'Management Information Systems Office',
            ],
            [
                'system_seeded' => true,
                'is_active' => true,
            ]
        );

        User::factory()->create([
            'name' => 'Limwell Laid',
            'email' => 'limwelllaid@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'John Paul Ceniza',
            'email' => 'JPCeniza@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Ronald Jay Meniano',
            'email' => 'RonaldJayMeniano@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Randy Chavez',
            'email' => 'RandyChavez@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Emmanuel Baluma',
            'email' => 'EmmanuelBaluma@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Mary Antomette Rambonanza',
            'email' => 'AnnRambonanz@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Rex Amiel Balendez',
            'email' => 'RexAmielBalendez@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Ivan Dasilao',
            'email' => 'IvanDasilao@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'super_admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);
        User::factory()->create([
            'name' => 'Kenn Cedric Jala',
            'email' => 'KennCedricJala@miso.gov.ph',
            'password' => Hash::make('password321'),
            'email_verified_at' => now(),
            'role' => 'admin',
            'two_factor_enabled' => false,
            'workos_id' => 'local-admin-'.Str::uuid(),
            'avatar' => '',
            'office_designation_id' => $misoOffice->id,
        ]);

        $this->call(NatureOfRequestSeeder::class);
        $this->call(ReferenceValueSeeder::class);
        $this->call(OfficeDesignationSeeder::class);
    }
}
