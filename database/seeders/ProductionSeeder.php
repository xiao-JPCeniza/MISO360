<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductionSeeder extends Seeder
{
    /**
     * Run the database seeds intended for production.
     *
     * Idempotent: safe to run multiple times.
     */
    public function run(): void
    {
        $this->call([
            NatureOfRequestSeeder::class,
            ReferenceValueSeeder::class,
            OfficeDesignationSeeder::class,
        ]);
    }
}
