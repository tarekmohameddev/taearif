<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TestPermissionRestrictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:test {user_id? : The ID of the user to test}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test permission restrictions for a user (tenant owner or employee)';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('');
        $this->info('========================================');
        $this->info('  PERMISSION RESTRICTION TEST');
        $this->info('========================================');
        $this->info('');

        // Get user ID from argument or ask
        $userId = $this->argument('user_id');

        if (!$userId) {
            $userId = $this->ask('Enter user ID to test');
        }

        $user = User::find($userId);

        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return Command::FAILURE;
        }

        // Display user info
        $this->info("👤 User Information:");
        $this->info("───────────────────────────────────────");
        $this->line("ID: {$user->id}");
        $this->line("Name: {$user->first_name} {$user->last_name}");
        $this->line("Email: {$user->email}");
        $this->line("Account Type: " . ($user->account_type ?? 'N/A'));
        $this->line("Tenant ID: " . ($user->tenant_id ?? 'N/A (Owner)'));
        $this->info('');

        // Check if tenant owner
        $isTenantOwner = ($user->account_type === 'tenant' && empty($user->tenant_id));

        if ($isTenantOwner) {
            $this->info("🔓 USER IS TENANT OWNER");
            $this->info("───────────────────────────────────────");
            $this->line("This user has FULL ACCESS to everything.");
            $this->line("Permission checks are BYPASSED.");
            $this->info('');
            return Command::SUCCESS;
        }

        // Check if employee
        if ($user->account_type === 'employee') {
            $this->info("👨‍💼 USER IS EMPLOYEE");
            $this->info("───────────────────────────────────────");
            $this->line("Access is RESTRICTED based on assigned permissions.");
            $this->info('');

            // Set tenant context
            $tenantId = (int) ($user->tenant_id ?? $user->id);
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenantId);

            // Get assigned permissions
            $permissions = $user->getAllPermissions();
            $roles = $user->roles;

            $this->info("📋 Assigned Roles ({$roles->count()}):");
            $this->info("───────────────────────────────────────");
            if ($roles->count() > 0) {
                foreach ($roles as $role) {
                    $this->line("  • {$role->name}");
                }
            } else {
                $this->line("  No roles assigned");
            }
            $this->info('');

            $this->info("🔑 Assigned Permissions ({$permissions->count()}):");
            $this->info("───────────────────────────────────────");
            if ($permissions->count() > 0) {
                foreach ($permissions as $perm) {
                    $this->line("  ✓ {$perm->name}");
                }
            } else {
                $this->warn("  ⚠️  No permissions assigned - User has NO ACCESS!");
            }
            $this->info('');

            // Test access to all modules
            $this->info("🧪 Testing Access to Modules:");
            $this->info("───────────────────────────────────────");

            $modules = [
                'Customers' => ['customers.view', 'customers.create', 'customers.update', 'customers.delete'],
                'Properties' => ['properties.view', 'properties.create', 'properties.update', 'properties.delete'],
                'Projects' => ['projects.view', 'projects.create', 'projects.update', 'projects.delete'],
                'CRM' => ['crm.view', 'crm.create', 'crm.update', 'crm.delete'],
                'Content' => ['content.view', 'content.create', 'content.update', 'content.delete'],
            ];

            foreach ($modules as $moduleName => $perms) {
                $this->line("  {$moduleName}:");
                foreach ($perms as $perm) {
                    $hasPermission = $user->can($perm);
                    $icon = $hasPermission ? '✅' : '❌';
                    $status = $hasPermission ? 'Allowed' : 'DENIED';
                    $action = str_replace(['customers.', 'properties.', 'projects.', 'crm.', 'content.'], '', $perm);
                    $this->line("    {$icon} {$action}: {$status}");
                }
            }

            $this->info('');

            // Summary
            $totalPerms = 30; // We have 30 active permissions
            $assignedPerms = $permissions->count();
            $percentage = $totalPerms > 0 ? round(($assignedPerms / $totalPerms) * 100) : 0;

            $this->info("📊 Access Summary:");
            $this->info("───────────────────────────────────────");
            $this->line("Assigned: {$assignedPerms} / {$totalPerms} permissions ({$percentage}%)");

            if ($assignedPerms === 0) {
                $this->warn("⚠️  WARNING: Employee has NO permissions!");
                $this->warn("They will be DENIED access to all actions.");
            } elseif ($assignedPerms === $totalPerms) {
                $this->info("✅ Employee has FULL ACCESS (all permissions)");
            } else {
                $this->info("⚡ Employee has PARTIAL ACCESS");
            }

        } else {
            $this->warn("⚠️  Unknown account type: " . ($user->account_type ?? 'N/A'));
        }

        $this->info('');
        $this->info('========================================');
        $this->info('✅ TEST COMPLETE');
        $this->info('========================================');
        $this->info('');

        return Command::SUCCESS;
    }
}
