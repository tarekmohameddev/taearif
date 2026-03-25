<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class DeduplicateApiPermissions extends Command
{
    protected $signature = 'rbac:deduplicate-api-permissions {--dry-run : Show what would change without writing}';

    protected $description = 'Merge duplicate api_permissions rows (same name, guard_name, team_id), re-point pivots, delete extras';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        $groups = DB::table('api_permissions')
            ->select('name', 'guard_name', 'team_id', DB::raw('COUNT(*) as c'))
            ->groupBy('name', 'guard_name', 'team_id')
            ->having('c', '>', 1)
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate permission groups found.');
            return self::SUCCESS;
        }

        $this->warn('Found '.$groups->count().' duplicate group(s).');

        $removed = 0;

        $run = function () use ($groups, $dry, &$removed) {
            foreach ($groups as $g) {
                $q = DB::table('api_permissions')
                    ->where('name', $g->name)
                    ->where('guard_name', $g->guard_name);

                if ($g->team_id === null) {
                    $q->whereNull('team_id');
                } else {
                    $q->where('team_id', $g->team_id);
                }

                $ids = $q->orderBy('id')->pluck('id');
                if ($ids->count() < 2) {
                    continue;
                }

                $keeper = (int) $ids->first();
                $dupIds = $ids->slice(1)->values()->all();

                foreach ($dupIds as $dupId) {
                    $dupId = (int) $dupId;
                    if ($dry) {
                        $this->line("Would merge permission id {$dupId} -> {$keeper} ({$g->name} / {$g->guard_name})");
                        $removed++;
                        continue;
                    }

                    $this->mergeRoleHasPermissions($keeper, $dupId);
                    $this->mergeModelHasPermissions($keeper, $dupId);

                    DB::table('api_permissions')->where('id', $dupId)->delete();
                    $removed++;
                }
            }
        };

        if ($dry) {
            $run();
            $this->info("Dry run: {$removed} duplicate api_permissions row(s) would be removed (pivots merged).");
            return self::SUCCESS;
        }

        DB::transaction($run);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info("Removed {$removed} duplicate api_permissions row(s); pivots point to the lowest id per group.");
        $this->comment('Run: php artisan permission:cache-reset');

        return self::SUCCESS;
    }

    private function mergeRoleHasPermissions(int $keeperId, int $dupId): void
    {
        $rows = DB::table('api_role_has_permissions')->where('permission_id', $dupId)->get();
        foreach ($rows as $row) {
            $conflict = DB::table('api_role_has_permissions')
                ->where('permission_id', $keeperId)
                ->where('role_id', $row->role_id)
                ->exists();

            if ($conflict) {
                DB::table('api_role_has_permissions')
                    ->where('permission_id', $dupId)
                    ->where('role_id', $row->role_id)
                    ->delete();
            } else {
                DB::table('api_role_has_permissions')
                    ->where('permission_id', $dupId)
                    ->where('role_id', $row->role_id)
                    ->update(['permission_id' => $keeperId]);
            }
        }
    }

    private function mergeModelHasPermissions(int $keeperId, int $dupId): void
    {
        $rows = DB::table('api_model_has_permissions')->where('permission_id', $dupId)->get();
        foreach ($rows as $row) {
            $conflict = DB::table('api_model_has_permissions')
                ->where('team_id', $row->team_id)
                ->where('permission_id', $keeperId)
                ->where('model_id', $row->model_id)
                ->where('model_type', $row->model_type)
                ->exists();

            if ($conflict) {
                DB::table('api_model_has_permissions')
                    ->where('team_id', $row->team_id)
                    ->where('permission_id', $dupId)
                    ->where('model_id', $row->model_id)
                    ->where('model_type', $row->model_type)
                    ->delete();
            } else {
                DB::table('api_model_has_permissions')
                    ->where('team_id', $row->team_id)
                    ->where('permission_id', $dupId)
                    ->where('model_id', $row->model_id)
                    ->where('model_type', $row->model_type)
                    ->update(['permission_id' => $keeperId]);
            }
        }
    }
}
