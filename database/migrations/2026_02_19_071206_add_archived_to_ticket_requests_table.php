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
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->boolean('archived')->default(false)->after('status_id');
        });

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropColumn('archived');
        });
    }
};
