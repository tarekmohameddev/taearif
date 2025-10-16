<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CleanupInternalPermissionTranslations extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // List of internal/system permissions that should be DELETED
        $internalPermissions = [
            // Menu permissions
            'menu.dashboard',
            'menu.content',
            'menu.settings',
            'menu.projects',
            'menu.properties',
            'menu.blog',
            'menu.customers',
            'menu.apps',
            'menu.affiliate',
            'menu.crm',

            // Roles & Permissions management
            'roles.read',
            'roles.write',
            'permissions.read',
            'permissions.write',

            // Employee management
            'employees.roles.sync',
            'employees.perms.sync',

            // Logs
            'logs.read',
        ];

        // Delete internal permissions completely from the database
        $deleted = DB::table('api_permissions')
            ->whereIn('name', $internalPermissions)
            ->delete();

        $this->command->info('Deleted internal/system permissions from database!');
        $this->command->info('Total permissions deleted: ' . $deleted);
        $this->command->info('');
        $this->command->info('These permissions have been removed:');
        foreach ($internalPermissions as $perm) {
            $this->command->line('  • ' . $perm);
        }
        $this->command->info('');
        $this->command->warn('Note: Make sure these permissions are not assigned to any roles or users!');
    }
}

