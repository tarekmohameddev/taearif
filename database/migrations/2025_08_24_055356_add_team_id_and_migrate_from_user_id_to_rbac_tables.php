<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        $this->addTeamColAndBackfill('api_roles', 'guard_name');
        $this->addTeamColAndBackfill('api_permissions', 'guard_name');
        $this->addTeamColAndBackfill('api_model_has_roles', 'role_id');
        $this->addTeamColAndBackfill('api_model_has_permissions', 'permission_id');
        $this->addTeamColAndBackfill('api_role_has_permissions', 'permission_id');
    }

    private function addTeamColAndBackfill(string $table, string $after): void
    {
        if (!Schema::hasColumn($table, 'team_id')) {
            Schema::table($table, function (Blueprint $t) use ($after) {
                $col = $t->unsignedBigInteger('team_id')->nullable()->index();
                // @phpstan-ignore-next-line
                method_exists($col, 'after') ? $col->after($after) : null;
            });
        }

        if (Schema::hasColumn($table, 'user_id')) {
            DB::table($table)
                ->whereNotNull('user_id')
                ->whereNull('team_id')
                ->update(['team_id' => DB::raw('user_id')]);

            Schema::table($table, function (Blueprint $t) {
                $t->dropColumn('user_id');
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'api_roles',
            'api_permissions',
            'api_model_has_roles',
            'api_model_has_permissions',
            'api_role_has_permissions',
        ] as $table) {
            if (Schema::hasColumn($table, 'team_id')) {
                Schema::table($table, function (Blueprint $t) {
                    $t->dropColumn('team_id');
                });
            }
        }
    }
};


