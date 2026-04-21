<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('ticket_request_assigned_staff')) {
            return;
        }

        Schema::create('ticket_request_assigned_staff', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_request_id')->constrained('ticket_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['ticket_request_id', 'user_id']);
        });

        $now = now();
        DB::table('ticket_requests')
            ->whereNotNull('assigned_staff_id')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use ($now): void {
                foreach ($rows as $row) {
                    DB::table('ticket_request_assigned_staff')->insertOrIgnore([
                        'ticket_request_id' => $row->id,
                        'user_id' => $row->assigned_staff_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_request_assigned_staff');
    }
};
