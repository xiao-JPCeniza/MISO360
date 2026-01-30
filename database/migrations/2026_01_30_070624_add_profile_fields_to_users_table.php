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
        Schema::table('users', function (Blueprint $table) {
            $table->string('position_title')->nullable()->after('name');
            $table->foreignId('office_designation_id')
                ->nullable()
                ->after('position_title')
                ->constrained('reference_values')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['office_designation_id']);
            $table->dropColumn(['position_title', 'office_designation_id']);
        });
    }
};
