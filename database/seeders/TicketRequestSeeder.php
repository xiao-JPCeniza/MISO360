<?php

namespace Database\Seeders;

use App\Models\TicketRequest;
use Illuminate\Database\Seeder;

class TicketRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        TicketRequest::factory()->count(3)->create();
    }
}
