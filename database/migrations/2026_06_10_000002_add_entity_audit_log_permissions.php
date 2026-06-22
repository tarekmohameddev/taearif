<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        $guard = config('rbac.guard', 'sanctum');
        $permissions = [
            'projects.view_audit_log',
            'buildings.view_audit_log',
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
                'projects.view_audit_log',
                'buildings.view_audit_log',
            ])
            ->delete();
    }
};
