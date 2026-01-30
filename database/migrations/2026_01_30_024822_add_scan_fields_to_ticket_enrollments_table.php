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
        Schema::table('ticket_enrollments', function (Blueprint $table) {
            $table->string('repair_status')->nullable();
            $table->text('repair_comments')->nullable();
            $table->timestamp('accepted_for_repair_at')->nullable();
            $table->foreignId('assigned_admin_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_enrollments', function (Blueprint $table) {
            $table->dropForeign(['assigned_admin_id']);
            $table->dropColumn([
                'repair_status',
                'repair_comments',
                'accepted_for_repair_at',
                'assigned_admin_id',
            ]);
        });
    }
};
