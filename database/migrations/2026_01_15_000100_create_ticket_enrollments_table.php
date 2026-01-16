<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->string('equipment_name');
            $table->string('equipment_type')->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('asset_tag')->nullable();
            $table->string('supplier')->nullable();
            $table->date('purchase_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('warranty_status')->nullable();
            $table->string('equipment_image')->nullable();
            $table->string('spec_memory')->nullable();
            $table->string('spec_storage')->nullable();
            $table->string('spec_operating_system')->nullable();
            $table->string('spec_network_address')->nullable();
            $table->string('spec_accessories')->nullable();
            $table->string('location_assigned_to')->nullable();
            $table->string('location_office_division')->nullable();
            $table->date('location_date_issued')->nullable();
            $table->string('request_nature')->nullable();
            $table->date('request_date')->nullable();
            $table->string('request_action_taken')->nullable();
            $table->string('request_assigned_staff')->nullable();
            $table->string('request_remarks')->nullable();
            $table->date('maintenance_date')->nullable();
            $table->string('maintenance_remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_enrollments');
    }
};
