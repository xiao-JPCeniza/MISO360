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
            $table->foreignId('remarks_id')
                ->nullable()
                ->after('category_id')
                ->constrained('reference_values')
                ->nullOnDelete();
            $table->foreignId('assigned_staff_id')
                ->nullable()
                ->after('remarks_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->date('date_received')->nullable()->after('assigned_staff_id');
            $table->date('date_started')->nullable()->after('date_received');
            $table->date('estimated_completion_date')->nullable()->after('date_started');
            $table->string('action_taken', 500)->nullable()->after('estimated_completion_date');
            $table->json('equipment_network_details')->nullable()->after('action_taken');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_requests', function (Blueprint $table) {
            $table->dropForeign(['remarks_id']);
            $table->dropForeign(['assigned_staff_id']);
            $table->dropColumn([
                'remarks_id',
                'assigned_staff_id',
                'date_received',
                'date_started',
                'estimated_completion_date',
                'action_taken',
                'equipment_network_details',
            ]);
        });
    }
};
