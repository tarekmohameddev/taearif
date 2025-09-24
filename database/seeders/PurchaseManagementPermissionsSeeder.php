<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PurchaseManagementPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Define permissions for Purchase Management System
        $permissions = [
            // Purchase Request permissions
            'view purchase requests',
            'create purchase requests', 
            'edit purchase requests',
            'delete purchase requests',
            
            // Stage management permissions
            'view purchase request stages',
            'update purchase request stages',
            'manage all purchase stages',
            
            // Dashboard and analytics
            'view purchase dashboard',
            'view purchase analytics',
            
            // Assignment permissions
            'assign purchase requests',
            'reassign purchase requests',
        ];

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'sanctum'
            ]);
        }

        // Create the main permission that user mentioned
        Permission::firstOrCreate([
            'name' => 'purchase management system',
            'guard_name' => 'sanctum'
        ]);

        $this->command->info('Purchase Management System permissions created successfully.');

        // Optionally create a role and assign permissions
        $role = Role::firstOrCreate([
            'name' => 'Purchase Manager',
            'guard_name' => 'sanctum'
        ]);

        // Assign all purchase management permissions to the role
        $allPermissions = Permission::where('name', 'like', '%purchase%')->get();
        $role->syncPermissions($allPermissions);

        $this->command->info('Purchase Manager role created and permissions assigned.');
    }
}
