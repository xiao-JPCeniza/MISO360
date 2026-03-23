<?php

use App\Enums\ReferenceValueGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOfficeIdColumn('ticket_enrollments');
        $this->addOfficeIdColumn('ticket_archives');

        $this->backfillOfficeIds('ticket_enrollments');
        $this->backfillOfficeIds('ticket_archives');
    }

    public function down(): void
    {
        $this->dropOfficeIdColumn('ticket_enrollments');
        $this->dropOfficeIdColumn('ticket_archives');
    }

    private function addOfficeIdColumn(string $table): void
    {
        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'office_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->foreignId('office_id')
                ->nullable()
                ->after('location_office_division')
                ->constrained('reference_values')
                ->nullOnDelete();
        });
    }

    private function dropOfficeIdColumn(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'office_id')) {
            return;
        }

        Schema::table($table, function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn('office_id');
        });
    }

    private function backfillOfficeIds(string $table): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'office_id')) {
            return;
        }

        $officeLookup = DB::table('reference_values')
            ->where('group_key', ReferenceValueGroup::OfficeDesignation->value)
            ->pluck('id', 'name');

        if ($officeLookup->isEmpty()) {
            return;
        }

        DB::table($table)
            ->whereNull('office_id')
            ->whereNotNull('location_office_division')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use ($table, $officeLookup): void {
                foreach ($rows as $row) {
                    $officeId = $officeLookup->get($row->location_office_division);
                    if (! $officeId) {
                        continue;
                    }

                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['office_id' => $officeId]);
                }
            });
    }
};
