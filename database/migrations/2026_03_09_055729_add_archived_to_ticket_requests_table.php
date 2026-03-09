<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_requests')) {
            return;
        }

        if (! Schema::hasColumn('ticket_requests', 'archived')) {
            Schema::table('ticket_requests', function (Blueprint $table) {
                $table->boolean('archived')->default(false);
            });
        }

        if (! Schema::hasTable('reference_values') || ! Schema::hasColumn('ticket_requests', 'status_id')) {
            return;
        }

        $completedStatusId = DB::table('reference_values')
            ->where('group_key', 'status')
            ->where('name', 'Completed')
            ->value('id');

        if ($completedStatusId !== null) {
            DB::table('ticket_requests')
                ->where('status_id', $completedStatusId)
                ->update(['archived' => true]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_requests') || ! Schema::hasColumn('ticket_requests', 'archived')) {
            return;
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropColumn('archived');
        });
    }
};
