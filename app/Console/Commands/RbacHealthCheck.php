<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

class RbacHealthCheck extends Command
{
    protected $signature = 'rbac:health-check {--guard=sanctum : Permission guard}';

    protected $description = 'Validate RBAC tables: global permissions exist, templates are consistent, and pivots have no orphaned permission IDs.';

    public function handle(): int
    {
        $guard = (string) $this->option('guard');

        $configPermissions = collect(config('rbac.permissions', []))
            ->filter()
            ->unique()
            ->values();

        $templates = collect(config('rbac.role_templates', []));
        $templatePermissions = $templates
            ->flatten()
            ->filter()
            ->unique()
            ->values();

        $errors = 0;

        // 1) Verify all config permissions exist as GLOBAL permissions (team_id NULL).
        $global = Permission::query()
            ->where('guard_name', $guard)
            ->whereNull('team_id')
            ->whereIn('name', $configPermissions)
            ->pluck('name')
            ->flip();

        $missingGlobal = $configPermissions->reject(fn (string $p) => $global->has($p))->values();
        if ($missingGlobal->isNotEmpty()) {
            $errors++;
            $this->error('Missing global permissions (team_id IS NULL).');
            $this->line($missingGlobal->implode(', '));
        }

        // 2) Verify templates reference only known permissions.
        $missingFromConfig = $templatePermissions->diff($configPermissions)->values();
        if ($missingFromConfig->isNotEmpty()) {
            $errors++;
            $this->error('Role templates reference permissions not listed in config(rbac.permissions).');
            $this->line($missingFromConfig->implode(', '));
        }

        // 3) Verify pivot table has no orphaned permission_id references.
        $orphans = DB::table('api_role_has_permissions as rhp')
            ->leftJoin('api_permissions as p', 'p.id', '=', 'rhp.permission_id')
            ->whereNull('p.id')
            ->select('rhp.permission_id', 'rhp.role_id')
            ->limit(50)
            ->get();

        if ($orphans->isNotEmpty()) {
            $errors++;
            $this->error('Found orphaned api_role_has_permissions rows (permission_id missing from api_permissions).');
            foreach ($orphans as $row) {
                $this->line("permission_id={$row->permission_id}, role_id={$row->role_id}");
            }
            $this->comment('Showing up to 50 rows.');
        }

        if ($errors > 0) {
            $this->newLine();
            $this->error("RBAC health check failed ({$errors} issue(s)).");
            return self::FAILURE;
        }

        $this->info('RBAC health check passed.');
        return self::SUCCESS;
    }
}

