<?php

return [
    // bump this when you add/change templates to auto-upgrade tenants
    'rbac_version' => 1,
    // Concurrency control for bootstrap
    'lock_seconds'  => 30, // how long a lock lives
    'block_seconds' => 5,  // how long to wait for another request to finish seeding

    // the guard Spatie uses in your app
    'guard' => 'sanctum',

    // flat list of permissions you support
    'permissions' => [
        // menus / UI
        'menu.dashboard',
        'menu.content',
        'menu.settings',
        'menu.customers',
        'menu.crm',
        'menu.projects',
        'menu.properties',
        'menu.blog',
        'menu.apps',
        'menu.affiliate',

        // settings / RBAC
        'settings.update',
        'rbac.roles.read',
        'rbac.roles.write',
        'rbac.perms.read',
        'rbac.perms.write',
        'rbac.assign',

        // domain/business modules (sample)
        'customers.view','customers.create','customers.update','customers.delete',
        'projects.view','projects.create','projects.update','projects.delete',
        'properties.view','properties.create','properties.update','properties.delete',
    ],

    // role → permissions template
    'role_templates' => [
        // hard-protected role (tenant only)
        'owner'     => ['*'], // full access via Gate::before, templates kept for documentation

        // tenant can grant to employees
        'manager'   => [
            'menu.*',
            'settings.update',
            'rbac.*',
            'customers.*',
            'projects.*',
            'properties.*',
        ],
        'supporter' => [
            'menu.dashboard','menu.customers','menu.properties',
            'customers.view','customers.create','customers.update',
            'properties.view','properties.create','properties.update',
        ],
    ],
];
