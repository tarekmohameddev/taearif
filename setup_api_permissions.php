<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

echo "Setting up API Permissions and Roles...\n";
echo "=====================================\n\n";

echo "Creating permissions...\n";

$permissions = [
    'properties.view', 'properties.create', 'properties.update', 'properties.delete',
    'customers.view', 'customers.create', 'customers.update', 'customers.delete',
    'projects.view', 'projects.create', 'projects.update', 'projects.delete',
    'settings.view', 'settings.update'
];

foreach ($permissions as $perm) {
    Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'sanctum']);
    echo "✓ Created permission: $perm\n";
}

echo "\nCreating roles for tenant 1037...\n";

$owner = Role::firstOrCreate(['name' => 'owner', 'team_id' => 1037, 'guard_name' => 'sanctum']);
$manager = Role::firstOrCreate(['name' => 'manager', 'team_id' => 1037, 'guard_name' => 'sanctum']);
$agent = Role::firstOrCreate(['name' => 'agent', 'team_id' => 1037, 'guard_name' => 'sanctum']);

echo "✓ Created role: owner\n";
echo "✓ Created role: manager\n";
echo "✓ Created role: agent\n";

echo "\nAssigning permissions to roles...\n";

$owner->syncPermissions($permissions);
$manager->syncPermissions([
    'properties.view', 'properties.create', 'properties.update',
    'customers.view', 'customers.create', 'customers.update',
    'projects.view', 'projects.create', 'projects.update',
    'settings.view'
]);
$agent->syncPermissions(['properties.view', 'customers.view', 'projects.view']);

echo "✓ Assigned all permissions to owner role\n";
echo "✓ Assigned limited permissions to manager role\n";
echo "✓ Assigned basic permissions to agent role\n";

echo "\nSetup complete!\n";
echo "===============\n";
echo "Owner role has: " . $owner->permissions->count() . " permissions\n";
echo "Manager role has: " . $manager->permissions->count() . " permissions\n";
echo "Agent role has: " . $agent->permissions->count() . " permissions\n";
echo "\nTotal permissions created: " . count($permissions) . "\n";
echo "Total roles created: 3\n";
echo "\nYour API Employee system is now ready to use!\n";
