<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $guard = config('rbac.guard', 'sanctum');
        foreach (array_unique(config('rbac.permissions', [])) as $name) {
            // Use DB-level NULL matching: Spatie findOrCreate() only filters name+guard via cache;
            // MySQL UNIQUE(name, guard_name, team_id) allows multiple rows when team_id IS NULL.
            Permission::query()->firstOrCreate(
                [
                    'name' => $name,
                    'guard_name' => $guard,
                    'team_id' => null,
                ]
            );
        }
    }
}
