<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'properties.view', 'properties.create', 'properties.update', 'properties.delete',
            'projects.view', 'projects.create', 'projects.update', 'projects.delete',
            'customers.view', 'customers.create', 'customers.update', 'customers.delete',
            'affiliates.view', 'affiliates.create', 'affiliates.update', 'affiliates.delete',
            'property_request.view', 'property_request.create', 'property_request.update', 'property_request.delete',
            'isthara.view', 'isthara.create', 'isthara.update', 'isthara.delete',
            'CRM.view', 'CRM.create', 'CRM.update', 'CRM.delete',
            'rms.view', 'rms.create', 'rms.update', 'rms.delete',
            'side_menu.view', 'side_menu.create', 'side_menu.update', 'side_menu.delete',
            'settings.update',
        ] as $name) {
            Permission::firstOrCreate(['name' => $name, 'guard_name' => 'sanctum']);
        }
    }
}
