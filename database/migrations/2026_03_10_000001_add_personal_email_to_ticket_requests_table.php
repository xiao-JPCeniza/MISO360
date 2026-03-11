<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ticket_requests')) {
            return;
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_requests', 'personal_email')) {
                $table->string('personal_email')->nullable()->after('nature_of_request_id');
            }
        });

        Schema::table('ticket_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('ticket_requests', 'office_email')) {
                $table->string('office_email')->nullable()->after('personal_email');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('ticket_requests')) {
            return;
        }

        Schema::table('ticket_requests', function (Blueprint $table) {
            if (Schema::hasColumn('ticket_requests', 'office_email')) {
                $table->dropColumn('office_email');
            }
            if (Schema::hasColumn('ticket_requests', 'personal_email')) {
                $table->dropColumn('personal_email');
            }
        });
    }
};
