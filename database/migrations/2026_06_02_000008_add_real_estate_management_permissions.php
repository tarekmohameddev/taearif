<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('rbac.guard', 'sanctum');
        $permissions = [
            'properties.change_status',
            'properties.view_audit_log',
            'properties.view_broker',
            'properties.view_owner_data',
        ];

        foreach ($permissions as $name) {
            Permission::firstOrCreate([
                'name' => $name,
                'guard_name' => $guard,
            ]);
        }
    }

    public function down(): void
    {
        $guard = config('rbac.guard', 'sanctum');
        Permission::where('guard_name', $guard)
            ->whereIn('name', [
                'properties.change_status',
                'properties.view_audit_log',
                'properties.view_broker',
                'properties.view_owner_data',
            ])
            ->delete();
    }
};
