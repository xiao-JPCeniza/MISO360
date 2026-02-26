<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated application schema.
 *
 * Idempotent: safe to run on fresh installs or after previous incremental
 * migrations. Uses hasTable/hasColumn checks so existing schema is unchanged.
 *
 * Existing deployments: after pulling, run `php artisan migrate` once. To clear
 * "Missing" entries in migrate:status, delete the old migration batch records
 * from the `migrations` table for the replaced filenames.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->ensureReferenceValuesTable();
        $this->ensureUserColumns();
        $this->ensureNatureOfRequestsTable();
        $this->ensurePermissionTables();
        $this->ensureTwoFactorChallengesTable();
        $this->ensureAuditLogsTable();
        $this->ensureTicketEnrollmentsTable();
        $this->ensureTicketArchivesTable();
        $this->ensureIssuedUidsTable();
        $this->ensureProfileSlidesTable();
        $this->ensureTicketRequestsTable();
        $this->backfillArchivedCompletedTickets();
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_requests');
        Schema::dropIfExists('profile_slides');
        Schema::dropIfExists('issued_uids');
        Schema::dropIfExists('ticket_archives');
        Schema::dropIfExists('ticket_enrollments');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('two_factor_challenges');
        $this->dropPermissionTables();
        Schema::dropIfExists('nature_of_requests');
        Schema::dropIfExists('reference_values');
        $this->dropUserColumns();
    }

    private function ensureUserColumns(): void
    {
        $table = 'users';
        if (! Schema::hasTable($table)) {
            return;
        }

        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! Schema::hasColumn($table, 'password')) {
                $t->string('password')->nullable()->after('email_verified_at');
            }
            if (! Schema::hasColumn($table, 'role')) {
                $t->string('role')->default('user')->after('password');
            }
            if (! Schema::hasColumn($table, 'phone')) {
                $t->string('phone')->nullable()->after('email');
            }
            if (! Schema::hasColumn($table, 'is_active')) {
                $t->boolean('is_active')->default(true)->after('role');
            }
            if (! Schema::hasColumn($table, 'two_factor_enabled')) {
                $t->boolean('two_factor_enabled')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn($table, 'two_factor_confirmed_at')) {
                $t->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_enabled');
            }
            if (! Schema::hasColumn($table, 'position_title')) {
                $t->string('position_title')->nullable()->after('name');
            }
            if (! Schema::hasColumn($table, 'office_designation_id')) {
                $t->foreignId('office_designation_id')
                    ->nullable()
                    ->after('position_title')
                    ->constrained('reference_values')
                    ->nullOnDelete();
            }
        });
    }

    private function dropUserColumns(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }
        if (Schema::hasColumn('users', 'office_designation_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['office_designation_id']);
            });
        }
        $cols = [
            'office_designation_id', 'position_title', 'two_factor_confirmed_at',
            'two_factor_enabled', 'is_active', 'phone', 'role', 'password',
        ];
        $drop = array_filter($cols, fn (string $col) => Schema::hasColumn('users', $col));
        if ($drop !== []) {
            Schema::table('users', fn (Blueprint $table) => $table->dropColumn($drop));
        }
    }

    private function ensureReferenceValuesTable(): void
    {
        if (Schema::hasTable('reference_values')) {
            if (! Schema::hasColumn('reference_values', 'system_seeded')) {
                Schema::table('reference_values', function (Blueprint $table) {
                    $table->boolean('system_seeded')->default(false)->after('name');
                });
            }

            return;
        }

        Schema::create('reference_values', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 50);
            $table->string('name');
            $table->boolean('system_seeded')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index('group_key');
            $table->unique(['group_key', 'name']);
        });
    }

    private function ensureNatureOfRequestsTable(): void
    {
        if (Schema::hasTable('nature_of_requests')) {
            return;
        }
        Schema::create('nature_of_requests', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function ensurePermissionTables(): void
    {
        $teams = config('permission.teams');
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        throw_if(empty($tableNames), \Exception::class, 'Error: config/permission.php not loaded. Run [php artisan config:clear] and try again.');
        throw_if($teams && empty($columnNames['team_foreign_key'] ?? null), \Exception::class, 'Error: team_foreign_key on config/permission.php not loaded.');

        if (Schema::hasTable($tableNames['permissions'])) {
            return;
        }

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');
            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'], 'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);
            $table->foreign($pivotPermission)->references('id')->on($tableNames['permissions'])->onDelete('cascade');
            $table->foreign($pivotRole)->references('id')->on($tableNames['roles'])->onDelete('cascade');
            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });

        app('cache')
            ->store(config('permission.cache.store') != 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    private function dropPermissionTables(): void
    {
        $tableNames = config('permission.table_names');
        if (empty($tableNames)) {
            return;
        }
        Schema::dropIfExists($tableNames['role_has_permissions'] ?? 'role_has_permissions');
        Schema::dropIfExists($tableNames['model_has_roles'] ?? 'model_has_roles');
        Schema::dropIfExists($tableNames['model_has_permissions'] ?? 'model_has_permissions');
        Schema::dropIfExists($tableNames['roles'] ?? 'roles');
        Schema::dropIfExists($tableNames['permissions'] ?? 'permissions');
    }

    private function ensureTwoFactorChallengesTable(): void
    {
        if (Schema::hasTable('two_factor_challenges')) {
            return;
        }
        Schema::create('two_factor_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose');
            $table->string('code_hash');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'purpose']);
        });
    }

    private function ensureAuditLogsTable(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('target_type');
            $table->unsignedBigInteger('target_id')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['action', 'target_type', 'target_id']);
        });
    }

    private function ensureTicketEnrollmentsTable(): void
    {
        if (Schema::hasTable('ticket_enrollments')) {
            if (! Schema::hasColumn('ticket_enrollments', 'equipment_images')) {
                Schema::table('ticket_enrollments', function (Blueprint $table) {
                    $table->json('equipment_images')->nullable()->after('equipment_image');
                });
                $this->backfillEquipmentImages('ticket_enrollments');
            }
            if (! Schema::hasColumn('ticket_enrollments', 'repair_status')) {
                Schema::table('ticket_enrollments', function (Blueprint $table) {
                    $table->string('repair_status')->nullable();
                    $table->text('repair_comments')->nullable();
                    $table->timestamp('accepted_for_repair_at')->nullable();
                    $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
                });
            }

            return;
        }

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
            $table->json('equipment_images')->nullable();
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
            $table->string('repair_status')->nullable();
            $table->text('repair_comments')->nullable();
            $table->timestamp('accepted_for_repair_at')->nullable();
            $table->foreignId('assigned_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    private function ensureTicketArchivesTable(): void
    {
        if (Schema::hasTable('ticket_archives')) {
            if (! Schema::hasColumn('ticket_archives', 'equipment_images')) {
                Schema::table('ticket_archives', function (Blueprint $table) {
                    $table->json('equipment_images')->nullable()->after('equipment_image');
                });
                $this->backfillEquipmentImages('ticket_archives');
            }

            return;
        }

        Schema::create('ticket_archives', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->index();
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
            $table->json('equipment_images')->nullable();
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
            $table->timestamp('archived_at');
            $table->timestamps();
        });
    }

    private function backfillEquipmentImages(string $tableName): void
    {
        $rows = DB::table($tableName)
            ->select('id', 'equipment_image', 'equipment_images')
            ->get();

        foreach ($rows as $row) {
            if (! empty($row->equipment_images) || empty($row->equipment_image)) {
                continue;
            }
            DB::table($tableName)
                ->where('id', $row->id)
                ->update(['equipment_images' => json_encode([$row->equipment_image])]);
        }
    }

    private function ensureIssuedUidsTable(): void
    {
        if (Schema::hasTable('issued_uids')) {
            return;
        }
        Schema::create('issued_uids', function (Blueprint $table) {
            $table->id();
            $table->string('uid')->unique();
            $table->unsignedInteger('sequence')->unique();
            $table->timestamps();
        });
    }

    private function ensureProfileSlidesTable(): void
    {
        if (Schema::hasTable('profile_slides')) {
            if (! Schema::hasColumn('profile_slides', 'archived_at')) {
                Schema::table('profile_slides', function (Blueprint $table) {
                    $table->timestamp('archived_at')->nullable()->after('is_active');
                });
            }

            return;
        }
        Schema::create('profile_slides', function (Blueprint $table) {
            $table->id();
            $table->string('image_path');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('text_position', 10)->default('left');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    private function ensureTicketRequestsTable(): void
    {
        if (Schema::hasTable('ticket_requests')) {
            $this->ensureTicketRequestColumns();

            return;
        }

        Schema::create('ticket_requests', function (Blueprint $table) {
            $table->id();
            $table->string('control_ticket_number')->unique();
            $table->foreignId('nature_of_request_id')->constrained('nature_of_requests')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_for_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('office_designation_id')->nullable()->constrained('reference_values')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('reference_values')->nullOnDelete();
            $table->boolean('archived')->default(false);
            $table->foreignId('category_id')->nullable()->constrained('reference_values')->nullOnDelete();
            $table->foreignId('remarks_id')->nullable()->constrained('reference_values')->nullOnDelete();
            $table->foreignId('assigned_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('date_received')->nullable();
            $table->date('date_started')->nullable();
            $table->dateTime('time_started')->nullable();
            $table->date('estimated_completion_date')->nullable();
            $table->dateTime('time_completed')->nullable();
            $table->string('action_taken', 500)->nullable();
            $table->json('equipment_network_details')->nullable();
            $table->boolean('has_qr_code')->default(false);
            $table->string('qr_code_number')->nullable();
            $table->text('description');
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
    }

    private function ensureTicketRequestColumns(): void
    {
        $table = 'ticket_requests';
        Schema::table($table, function (Blueprint $t) use ($table) {
            if (! Schema::hasColumn($table, 'status_id')) {
                $t->foreignId('status_id')->nullable()->after('office_designation_id')->constrained('reference_values')->nullOnDelete();
            }
            if (! Schema::hasColumn($table, 'archived')) {
                $t->boolean('archived')->default(false)->after('status_id');
            }
            if (! Schema::hasColumn($table, 'category_id')) {
                $t->foreignId('category_id')->nullable()->after('status_id')->constrained('reference_values')->nullOnDelete();
            }
            if (! Schema::hasColumn($table, 'remarks_id')) {
                $t->foreignId('remarks_id')->nullable()->after('category_id')->constrained('reference_values')->nullOnDelete();
            }
            if (! Schema::hasColumn($table, 'assigned_staff_id')) {
                $t->foreignId('assigned_staff_id')->nullable()->after('remarks_id')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn($table, 'date_received')) {
                $t->date('date_received')->nullable()->after('assigned_staff_id');
            }
            if (! Schema::hasColumn($table, 'date_started')) {
                $t->date('date_started')->nullable()->after('date_received');
            }
            if (! Schema::hasColumn($table, 'time_started')) {
                $t->dateTime('time_started')->nullable()->after('date_started');
            }
            if (! Schema::hasColumn($table, 'estimated_completion_date')) {
                $t->date('estimated_completion_date')->nullable()->after('time_started');
            }
            if (! Schema::hasColumn($table, 'time_completed')) {
                $t->dateTime('time_completed')->nullable()->after('estimated_completion_date');
            }
            if (! Schema::hasColumn($table, 'action_taken')) {
                $t->string('action_taken', 500)->nullable()->after('time_completed');
            }
            if (! Schema::hasColumn($table, 'equipment_network_details')) {
                $t->json('equipment_network_details')->nullable()->after('action_taken');
            }
        });
    }

    private function backfillArchivedCompletedTickets(): void
    {
        if (! Schema::hasTable('ticket_requests') || ! Schema::hasColumn('ticket_requests', 'archived')) {
            return;
        }
        if (! Schema::hasTable('reference_values')) {
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
};
