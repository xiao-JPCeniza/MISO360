<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->foreignId('status_id')
                ->nullable()
                ->after('office_designation_id')
                ->constrained('reference_values')
                ->nullOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->after('status_id')
                ->constrained('reference_values')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropForeign(['category_id']);
        });
    }
};
