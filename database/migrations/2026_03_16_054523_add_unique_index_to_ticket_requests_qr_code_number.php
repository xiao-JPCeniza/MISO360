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
        if (! Schema::hasTable('ticket_requests')) {
            return;
        }

        $duplicateUids = DB::table('ticket_requests')
            ->whereNotNull('qr_code_number')
            ->groupBy('qr_code_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('qr_code_number');

        foreach ($duplicateUids as $uid) {
            $duplicateIds = DB::table('ticket_requests')
                ->where('qr_code_number', $uid)
                ->orderByDesc('id')
                ->skip(1)
                ->pluck('id');

            if ($duplicateIds->isNotEmpty()) {
                DB::table('ticket_requests')
                    ->whereIn('id', $duplicateIds->all())
                    ->update([
                        'has_qr_code' => false,
                        'qr_code_number' => null,
                    ]);
            }
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            try {
                $table->unique('qr_code_number', 'ticket_requests_qr_code_number_unique');
            } catch (\Throwable) {
                // Index already exists; keep migration idempotent.
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('ticket_requests')) {
            return;
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            try {
                $table->dropUnique('ticket_requests_qr_code_number_unique');
            } catch (\Throwable) {
                // Index was already removed or never created.
            }
        });
    }
};
