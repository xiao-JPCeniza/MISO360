<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SubmitOnlyRequestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'request@miso.gov.ph'],
            [
                'name' => 'Request Submission Account',
                'password' => Hash::make('request'),
                'email_verified_at' => now(),
                'role' => 'submit_only',
                'two_factor_enabled' => false,
                'workos_id' => 'local-submit-only-'.Str::uuid(),
                'avatar' => '',
                'is_active' => true,
            ]
        );
    }
}
