<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'api_roles';

        // Read all UNIQUE indexes on api_roles
        $rows = DB::select("
            SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND NON_UNIQUE = 0
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ", [$table]);

        // Group by index and collect ordered columns
        $indexes = collect($rows)->groupBy('INDEX_NAME')->map(function ($grp) {
            return $grp->pluck('COLUMN_NAME')->values()->all(); // ordered
        });

        // 1) Drop any UNIQUE index that covers only (name, guard_name)
        foreach ($indexes as $idxName => $cols) {
            $set = collect($cols)->sort()->values()->all();
            if ($set === ['guard_name', 'name']) {
                // Drop the incorrect unique
                DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$idxName}`");
            }
        }

        // Refresh after potential drop
        $rows = DB::select("
            SELECT INDEX_NAME, NON_UNIQUE, SEQ_IN_INDEX, COLUMN_NAME
            FROM INFORMATION_SCHEMA.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND NON_UNIQUE = 0
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ", [$table]);
        $indexes = collect($rows)->groupBy('INDEX_NAME')->map(fn ($grp) => $grp->pluck('COLUMN_NAME')->values()->all());

        // 2) Ensure there is a UNIQUE index on (team_id, name, guard_name) in any order
        $hasTenantScopedUnique = $indexes->contains(function ($cols) {
            $set = collect($cols)->sort()->values()->all();
            return $set === ['guard_name', 'name', 'team_id'];
        });

        if (!$hasTenantScopedUnique) {
            // Create our preferred order for efficiency: team_id first
            Schema::table($table, function ($t) {
                $t->unique(['team_id', 'name', 'guard_name'], 'api_roles_team_name_guard_unique');
            });
        }
    }

    public function down(): void
    {
        $table = 'api_roles';

        // Drop the unique we added if it exists
        try {
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `api_roles_team_name_guard_unique`");
        } catch (\Throwable $e) {
            // ignore if it doesn't exist
        }

        // (We do NOT recreate the broken 2-column unique)
    }
};
