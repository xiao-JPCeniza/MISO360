<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_enrollments', function (Blueprint $table) {
            $table->json('equipment_images')->nullable()->after('equipment_image');
        });

        Schema::table('ticket_archives', function (Blueprint $table) {
            $table->json('equipment_images')->nullable()->after('equipment_image');
        });

        $this->backfillImages('ticket_enrollments');
        $this->backfillImages('ticket_archives');
    }

    public function down(): void
    {
        Schema::table('ticket_enrollments', function (Blueprint $table) {
            $table->dropColumn('equipment_images');
        });

        Schema::table('ticket_archives', function (Blueprint $table) {
            $table->dropColumn('equipment_images');
        });
    }

    private function backfillImages(string $table): void
    {
        $rows = DB::table($table)
            ->select('id', 'equipment_image', 'equipment_images')
            ->get();

        foreach ($rows as $row) {
            if (! empty($row->equipment_images)) {
                continue;
            }

            if (empty($row->equipment_image)) {
                continue;
            }

            DB::table($table)
                ->where('id', $row->id)
                ->update([
                    'equipment_images' => json_encode([$row->equipment_image]),
                ]);
        }
    }
};
