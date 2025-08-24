<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // ---- api_roles: add user_id, copy from team_id, drop team_id
        Schema::table('api_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('api_roles', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('id')->index();
            }
        });

        if (Schema::hasColumn('api_roles', 'team_id')) {
            DB::statement('UPDATE api_roles SET user_id = team_id WHERE user_id IS NULL');
            Schema::table('api_roles', function (Blueprint $table) {
                $table->dropColumn('team_id');
            });
        }

        // ---- api_model_has_roles: add user_id, copy, swap PK, drop team_id
        Schema::table('api_model_has_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('api_model_has_roles', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('model_type')->index();
            }
        });

        if (Schema::hasColumn('api_model_has_roles', 'team_id')) {
            DB::statement('UPDATE api_model_has_roles SET user_id = team_id WHERE user_id IS NULL');

            // drop current PK (likely includes team_id)
            Schema::table('api_model_has_roles', function (Blueprint $table) {
                $table->dropPrimary(); // drops PRIMARY
            });

            // drop the old team_id column
            Schema::table('api_model_has_roles', function (Blueprint $table) {
                $table->dropColumn('team_id');
            });

            // set new composite primary including user_id
            Schema::table('api_model_has_roles', function (Blueprint $table) {
                $table->primary(['user_id', 'role_id', 'model_id', 'model_type']);
            });
        } else {
            // ensure PK exists with user_id if table was created earlier without PK
            Schema::table('api_model_has_roles', function (Blueprint $table) {
                // guard: only add primary if none
                // some MySQLs need try/catch, but Laravel handles dropPrimary above
                // Add primary if missing:
                // $table->primary(['user_id', 'role_id', 'model_id', 'model_type']);
            });
        }

        // ---- api_model_has_permissions: add user_id, copy, swap PK, drop team_id
        Schema::table('api_model_has_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('api_model_has_permissions', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('model_type')->index();
            }
        });

        if (Schema::hasColumn('api_model_has_permissions', 'team_id')) {
            DB::statement('UPDATE api_model_has_permissions SET user_id = team_id WHERE user_id IS NULL');

            Schema::table('api_model_has_permissions', function (Blueprint $table) {
                $table->dropPrimary();
            });

            Schema::table('api_model_has_permissions', function (Blueprint $table) {
                $table->dropColumn('team_id');
            });

            Schema::table('api_model_has_permissions', function (Blueprint $table) {
                $table->primary(['user_id', 'permission_id', 'model_id', 'model_type']);
            });
        }
    }

    public function down(): void
    {
        // Reverse: add team_id back, copy from user_id, drop user_id, restore PKs

        // roles
        Schema::table('api_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('api_roles', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('id')->index();
            }
        });
        if (Schema::hasColumn('api_roles', 'user_id')) {
            DB::statement('UPDATE api_roles SET team_id = user_id WHERE team_id IS NULL');
            Schema::table('api_roles', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
        }

        // model_has_roles
        Schema::table('api_model_has_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('api_model_has_roles', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('model_type')->index();
            }
        });
        if (Schema::hasColumn('api_model_has_roles', 'user_id')) {
            DB::statement('UPDATE api_model_has_roles SET team_id = user_id WHERE team_id IS NULL');

            Schema::table('api_model_has_roles', function (Blueprint $table) {
                $table->dropPrimary();
            });
            Schema::table('api_model_has_roles', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            Schema::table('api_model_has_roles', function (Blueprint $table) {
                $table->primary(['team_id', 'role_id', 'model_id', 'model_type']);
            });
        }

        // model_has_permissions
        Schema::table('api_model_has_permissions', function (Blueprint $table) {
            if (!Schema::hasColumn('api_model_has_permissions', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable()->after('model_type')->index();
            }
        });
        if (Schema::hasColumn('api_model_has_permissions', 'user_id')) {
            DB::statement('UPDATE api_model_has_permissions SET team_id = user_id WHERE team_id IS NULL');

            Schema::table('api_model_has_permissions', function (Blueprint $table) {
                $table->dropPrimary();
            });
            Schema::table('api_model_has_permissions', function (Blueprint $table) {
                $table->dropColumn('user_id');
            });
            Schema::table('api_model_has_permissions', function (Blueprint $table) {
                $table->primary(['team_id', 'permission_id', 'model_id', 'model_type']);
            });
        }
    }
};
