<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = config('permission.table_names');

        if (Schema::hasTable($tables['roles'])) {
            Schema::table($tables['roles'], function (Blueprint $t) use ($tables) {
                if (!Schema::hasColumn($tables['roles'], 'team_id')) {
                    $t->unsignedBigInteger('team_id')->nullable()->after('guard_name');
                    $t->index('team_id');
                }
            });

            if (Schema::hasColumn($tables['roles'], 'user_id')) {
                DB::table($tables['roles'])->update(['team_id' => DB::raw('user_id')]);
                try { DB::statement('ALTER TABLE '.$tables['roles'].' DROP INDEX '.$tables['roles'].'_name_guard_name_user_id_unique'); } catch (\Throwable $e) {}
                try { DB::statement('ALTER TABLE '.$tables['roles'].' DROP INDEX '.$tables['roles'].'_name_guard_name_unique'); } catch (\Throwable $e) {}
                Schema::table($tables['roles'], function (Blueprint $t) { $t->dropColumn('user_id'); });
            }

            Schema::table($tables['roles'], function (Blueprint $t) use ($tables) {
                $t->unique(['name', 'guard_name', 'team_id'], $tables['roles'].'_name_guard_name_team_id_unique');
            });
        }

        if (Schema::hasTable($tables['model_has_roles'])) {
            Schema::table($tables['model_has_roles'], function (Blueprint $t) use ($tables) {
                if (!Schema::hasColumn($tables['model_has_roles'], 'team_id')) {
                    $t->unsignedBigInteger('team_id')->nullable()->after('model_id');
                    $t->index('team_id');
                }
            });

            if (Schema::hasColumn($tables['model_has_roles'], 'user_id')) {
                DB::table($tables['model_has_roles'])->update(['team_id' => DB::raw('user_id')]);
            }

            try { DB::statement('ALTER TABLE '.$tables['model_has_roles'].' DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE '.$tables['model_has_roles'].' ADD PRIMARY KEY (role_id, model_id, model_type, team_id)');

            if (Schema::hasColumn($tables['model_has_roles'], 'user_id')) {
                Schema::table($tables['model_has_roles'], function (Blueprint $t) { $t->dropColumn('user_id'); });
            }
        }

        if (Schema::hasTable($tables['model_has_permissions'])) {
            Schema::table($tables['model_has_permissions'], function (Blueprint $t) use ($tables) {
                if (!Schema::hasColumn($tables['model_has_permissions'], 'team_id')) {
                    $t->unsignedBigInteger('team_id')->nullable()->after('model_id');
                    $t->index('team_id');
                }
            });

            if (Schema::hasColumn($tables['model_has_permissions'], 'user_id')) {
                DB::table($tables['model_has_permissions'])->update(['team_id' => DB::raw('user_id')]);
            }

            try { DB::statement('ALTER TABLE '.$tables['model_has_permissions'].' DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE '.$tables['model_has_permissions'].' ADD PRIMARY KEY (permission_id, model_id, model_type, team_id)');

            if (Schema::hasColumn($tables['model_has_permissions'], 'user_id')) {
                Schema::table($tables['model_has_permissions'], function (Blueprint $t) { $t->dropColumn('user_id'); });
            }
        }

        if (Schema::hasTable($tables['role_has_permissions']) && Schema::hasColumn($tables['role_has_permissions'], 'user_id')) {
            Schema::table($tables['role_has_permissions'], function (Blueprint $t) { $t->dropColumn('user_id'); });
        }
    }

    public function down(): void
    {
        $tables = config('permission.table_names');

        if (Schema::hasTable($tables['roles'])) {
            Schema::table($tables['roles'], function (Blueprint $t) use ($tables) {
                if (!Schema::hasColumn($tables['roles'], 'user_id')) {
                    $t->unsignedBigInteger('user_id')->nullable()->after('guard_name');
                    $t->index('user_id');
                }
            });

            DB::table($tables['roles'])->update(['user_id' => DB::raw('team_id')]);

            try { Schema::table($tables['roles'], function (Blueprint $t) use ($tables) { $t->dropUnique($tables['roles'].'_name_guard_name_team_id_unique'); }); } catch (\Throwable $e) {}

            Schema::table($tables['roles'], function (Blueprint $t) {
                $t->unique(['name', 'guard_name', 'user_id']);
                $t->dropColumn('team_id');
            });
        }

        if (Schema::hasTable($tables['model_has_roles'])) {
            Schema::table($tables['model_has_roles'], function (Blueprint $t) use ($tables) {
                if (!Schema::hasColumn($tables['model_has_roles'], 'user_id')) {
                    $t->unsignedBigInteger('user_id')->nullable()->after('model_id');
                    $t->index('user_id');
                }
            });

            DB::table($tables['model_has_roles'])->update(['user_id' => DB::raw('team_id')]);

            try { DB::statement('ALTER TABLE '.$tables['model_has_roles'].' DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE '.$tables['model_has_roles'].' ADD PRIMARY KEY (role_id, model_id, model_type, user_id)');

            Schema::table($tables['model_has_roles'], function (Blueprint $t) { $t->dropColumn('team_id'); });
        }

        if (Schema::hasTable($tables['model_has_permissions'])) {
            Schema::table($tables['model_has_permissions'], function (Blueprint $t) use ($tables) {
                if (!Schema::hasColumn($tables['model_has_permissions'], 'user_id')) {
                    $t->unsignedBigInteger('user_id')->nullable()->after('model_id');
                    $t->index('user_id');
                }
            });

            DB::table($tables['model_has_permissions'])->update(['user_id' => DB::raw('team_id')]);

            try { DB::statement('ALTER TABLE '.$tables['model_has_permissions'].' DROP PRIMARY KEY'); } catch (\Throwable $e) {}
            DB::statement('ALTER TABLE '.$tables['model_has_permissions'].' ADD PRIMARY KEY (permission_id, model_id, model_type, user_id)');

            Schema::table($tables['model_has_permissions'], function (Blueprint $t) { $t->dropColumn('team_id'); });
        }

        if (Schema::hasTable($tables['role_has_permissions']) && !Schema::hasColumn($tables['role_has_permissions'], 'user_id')) {
            Schema::table($tables['role_has_permissions'], function (Blueprint $t) {
                $t->unsignedBigInteger('user_id')->nullable();
                $t->index('user_id');
            });
        }
    }
};
