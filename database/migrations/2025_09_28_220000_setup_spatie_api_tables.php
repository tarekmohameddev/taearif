<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if Spatie tables already exist with correct structure
        $apiRolesExists = Schema::hasTable('api_roles');
        $apiPermissionsExists = Schema::hasTable('api_permissions');
        
        if ($apiRolesExists && $apiPermissionsExists) {
            // Check if they have the correct Spatie structure
            $hasTeamId = Schema::hasColumn('api_roles', 'team_id');
            $hasGuardName = Schema::hasColumn('api_roles', 'guard_name');
            
            if ($hasTeamId && $hasGuardName) {
                // Tables already exist with correct Spatie structure, just clean up legacy tables
                if (Schema::hasTable('api_employee_role')) {
                    Schema::dropIfExists('api_employee_role');
                }
                if (Schema::hasTable('api_employees')) {
                    Schema::dropIfExists('api_employees');
                }
                return; // Exit early, everything is already set up correctly
            }
        }
        
        // Step 1: Backup existing api_roles and api_permissions data if they exist with wrong structure
        if ($apiRolesExists) {
            // Create backup table
            Schema::create('old_api_roles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('team_id')->nullable();
                $table->string('name');
                $table->string('guard_name')->nullable();
                $table->timestamps();
            });
            
            // Copy data to backup
            try {
                DB::statement('INSERT INTO old_api_roles (id, team_id, name, guard_name, created_at, updated_at) SELECT id, team_id, name, guard_name, created_at, updated_at FROM api_roles');
            } catch (Exception $e) {
                // If there are issues, just drop the old table
                echo 'Could not backup api_roles data: ' . $e->getMessage() . PHP_EOL;
            }
            
            // Drop original table
            Schema::dropIfExists('api_roles');
        }
        
        if ($apiPermissionsExists) {
            // Create backup table
            Schema::create('old_api_permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();
            });
            
            // Copy data to backup
            try {
                DB::statement('INSERT INTO old_api_permissions SELECT * FROM api_permissions');
            } catch (Exception $e) {
                echo 'Could not backup api_permissions data: ' . $e->getMessage() . PHP_EOL;
            }
            
            // Drop original table
            Schema::dropIfExists('api_permissions');
        }

        // Step 2: Create new Spatie tables with api_ prefix
        $this->createSpatieTables();
        
        // Step 3: Drop legacy tables that are no longer needed
        if (Schema::hasTable('api_employee_role')) {
            Schema::dropIfExists('api_employee_role');
        }
        if (Schema::hasTable('api_employees')) {
            Schema::dropIfExists('api_employees');
        }
    }

    /**
     * Create Spatie permission tables with api_ prefix
     */
    private function createSpatieTables(): void
    {
        // api_permissions table
        Schema::create('api_permissions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->unique(['name', 'guard_name']);
        });

        // api_roles table
        Schema::create('api_roles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();

            $table->index('team_id', 'api_roles_team_foreign_key_index');
            $table->unique(['team_id', 'name', 'guard_name']);
        });

        // api_model_has_permissions table
        Schema::create('api_model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');

            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'api_model_has_permissions_model_id_model_type_index');

            $table->foreign('permission_id')
                ->references('id')
                ->on('api_permissions')
                ->onDelete('cascade');
            
            $table->unsignedBigInteger('team_id');
            $table->index('team_id', 'api_model_has_permissions_team_foreign_key_index');

            $table->primary(['team_id', 'permission_id', 'model_id', 'model_type'],
                'api_model_has_permissions_permission_model_type_primary');
        });

        // api_model_has_roles table
        Schema::create('api_model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');

            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type'], 'api_model_has_roles_model_id_model_type_index');

            $table->foreign('role_id')
                ->references('id')
                ->on('api_roles')
                ->onDelete('cascade');
            
            $table->unsignedBigInteger('team_id');
            $table->index('team_id', 'api_model_has_roles_team_foreign_key_index');

            $table->primary(['team_id', 'role_id', 'model_id', 'model_type'],
                'api_model_has_roles_role_model_type_primary');
        });

        // api_role_has_permissions table
        Schema::create('api_role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');

            $table->foreign('permission_id')
                ->references('id')
                ->on('api_permissions')
                ->onDelete('cascade');

            $table->foreign('role_id')
                ->references('id')
                ->on('api_roles')
                ->onDelete('cascade');

            $table->primary(['permission_id', 'role_id'], 'api_role_has_permissions_permission_id_role_id_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop new Spatie tables
        Schema::dropIfExists('api_role_has_permissions');
        Schema::dropIfExists('api_model_has_roles');
        Schema::dropIfExists('api_model_has_permissions');
        Schema::dropIfExists('api_roles');
        Schema::dropIfExists('api_permissions');
        
        // Restore original tables from backup if they exist
        if (Schema::hasTable('old_api_roles')) {
            Schema::rename('old_api_roles', 'api_roles');
        }
        if (Schema::hasTable('old_api_permissions')) {
            Schema::rename('old_api_permissions', 'api_permissions');
        }
    }
};
