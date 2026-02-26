<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class BackfillRbacNewPermissions extends Command
{
    protected $signature = 'rbac:backfill-new-permissions
        {--apply : Persist changes (default is dry-run)}
        {--guard=sanctum : Permission guard}
        {--tenant=* : Optional tenant ID(s) to process}';

    protected $description = 'Additively backfill new RBAC permissions for existing tenant roles/users without removing anything.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $guard = (string) $this->option('guard');
        $tenantIds = collect((array) $this->option('tenant'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values();

        $mapping = [
            'properties.view' => ['buildings.view', 'property_requests.view'],
            'properties.create' => ['buildings.create', 'property_requests.create'],
            'properties.update' => ['buildings.update', 'property_requests.update'],
            'properties.delete' => ['buildings.delete', 'property_requests.delete'],
            'content.view' => ['rentals.view', 'job_applications.view'],
        ];

        $targets = collect($mapping)->flatten()->unique()->values();
        $targetPermissions = Permission::query()
            ->where('guard_name', $guard)
            ->whereIn('name', $targets)
            ->get()
            ->keyBy('name');

        $missingTargets = $targets->reject(fn (string $name) => $targetPermissions->has($name))->values();
        if ($missingTargets->isNotEmpty()) {
            $this->error('Missing target permissions in DB. Seed first with `php artisan rbac:seed-global-permissions`.');
            $this->line('Missing: ' . $missingTargets->implode(', '));
            return self::FAILURE;
        }

        $tenantsQuery = User::query()
            ->select('id')
            ->where('account_type', 'tenant');

        if ($tenantIds->isNotEmpty()) {
            $tenantsQuery->whereIn('id', $tenantIds);
        }

        $tenants = $tenantsQuery->orderBy('id')->pluck('id');
        if ($tenants->isEmpty()) {
            $this->warn('No tenants found for the given filters.');
            return self::SUCCESS;
        }

        $registrar = app(PermissionRegistrar::class);
        $stats = [
            'roles_scanned' => 0,
            'users_scanned' => 0,
            'role_grants' => 0,
            'user_grants' => 0,
        ];

        $this->info(($apply ? 'APPLY' : 'DRY-RUN') . " mode. Tenants: {$tenants->count()}");

        foreach ($tenants as $tenantId) {
            $registrar->setPermissionsTeamId((int) $tenantId);

            $roles = Role::query()
                ->where('team_id', (int) $tenantId)
                ->where('guard_name', $guard)
                ->with('permissions:id,name')
                ->get();

            foreach ($roles as $role) {
                $stats['roles_scanned']++;
                $rolePerms = $role->permissions->pluck('name')->flip();

                foreach ($mapping as $source => $targetsForSource) {
                    if (!$rolePerms->has($source)) {
                        continue;
                    }

                    foreach ($targetsForSource as $target) {
                        if ($rolePerms->has($target)) {
                            continue;
                        }

                        $stats['role_grants']++;
                        if ($apply) {
                            $role->givePermissionTo($targetPermissions[$target]);
                        }
                    }
                }
            }

            $users = User::query()
                ->where(function ($q) use ($tenantId) {
                    $q->where('id', (int) $tenantId)
                        ->orWhere('tenant_id', (int) $tenantId);
                })
                ->with('permissions:id,name')
                ->get();

            foreach ($users as $user) {
                $stats['users_scanned']++;
                $directPerms = $user->getDirectPermissions()->pluck('name')->flip();

                foreach ($mapping as $source => $targetsForSource) {
                    if (!$directPerms->has($source)) {
                        continue;
                    }

                    foreach ($targetsForSource as $target) {
                        if ($directPerms->has($target)) {
                            continue;
                        }

                        $stats['user_grants']++;
                        if ($apply) {
                            $user->givePermissionTo($targetPermissions[$target]);
                        }
                    }
                }
            }
        }

        $registrar->forgetCachedPermissions();

        $this->newLine();
        $this->info('Backfill summary');
        $this->line('Roles scanned: ' . $stats['roles_scanned']);
        $this->line('Users scanned: ' . $stats['users_scanned']);
        $this->line('Role grants ' . ($apply ? 'applied' : 'planned') . ': ' . $stats['role_grants']);
        $this->line('User direct grants ' . ($apply ? 'applied' : 'planned') . ': ' . $stats['user_grants']);

        if (!$apply) {
            $this->warn('Dry-run only. Re-run with --apply to persist changes.');
        }

        return self::SUCCESS;
    }
}

