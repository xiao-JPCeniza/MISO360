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
        $misoOffice = ReferenceValue::updateOrCreate(
            [
                'group_key' => ReferenceValueGroup::OfficeDesignation->value,
                'name' => 'Management Information Systems Office (MISO)',
            ],
            [
                'system_seeded' => true,
                'is_active' => true,
            ]
        );
        if (app()->environment(['local', 'testing'])) {
            // Seeded users are email-verified and can log in directly. Admin/super_admin skip 2FA.
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
                'email' => 'mis',
                'password' => Hash::make('password321'),
                'email_verified_at' => now(),
                'role' => 'user',
                'two_factor_enabled' => false,
                'workos_id' => 'local-user-'.Str::uuid(),
                'avatar' => '',
            ]);

            User::factory()->create([
                'name' => 'Limwell Laid',
                'email' => 'limwell.laid@manolofortich.gov.ph',
                'password' => Hash::make('limwell.laid'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'John Paul Ceniza',
                'email' => 'johnpaul.ceniza@manolofortich.gov.ph',
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
                'email' => 'ronald.meniano@manolofortich.gov.ph',
                'password' => Hash::make('ronald.meniano'),
                'email_verified_at' => now(),
                'role' => 'super_admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Randy Chavez',
                'email' => 'randy.chavez@manolofortich.gov.ph',
                'password' => Hash::make('randy.chavez'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Emmanuel Baluma',
                'email' => 'emmanuel.baluma@manolofortich.gov.ph',
                'password' => Hash::make('emmanuel.baluma'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Mary Antonette Rambonanza',
                'email' => 'mary.rambonanza@manolofortich.gov.ph',
                'password' => Hash::make('mary.rambonanza'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Rex Amiel Balendez',
                'email' => 'rex.balendez@manolofortich.gov.ph',
                'password' => Hash::make('rex.balendez'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Ivan Dasilao',
                'email' => 'ivan.dasilao@manolofortich.gov.ph',
                'password' => Hash::make('ivan.dasilao'),
                'email_verified_at' => now(),
                'role' => 'super_admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Jig Jimenez ',
                'email' => 'jig.jimenez@manolofortich.gov.ph',
                'password' => Hash::make('jig.jimenez'),
                'email_verified_at' => now(),
                'role' => 'super_admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
            User::factory()->create([
                'name' => 'Kenn Cedric Jala',
                'email' => 'kenncedricjalatcc@gmail.com',
                'password' => Hash::make('password321'),
                'email_verified_at' => now(),
                'role' => 'admin',
                'two_factor_enabled' => false,
                'workos_id' => 'local-admin-'.Str::uuid(),
                'avatar' => '',
                'office_designation_id' => $misoOffice->id,
            ]);
        }

        $this->call(NatureOfRequestSeeder::class);
        $this->call(ReferenceValueSeeder::class);
        $this->call(OfficeDesignationSeeder::class);
        if (app()->environment(['local', 'testing'])) {
            $this->call(SubmitOnlyRequestUserSeeder::class);
        }
    }
}
