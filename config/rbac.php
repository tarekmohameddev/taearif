<?php

return [
    // bump this when you add/change templates to auto-upgrade tenants
    'rbac_version' => 1,
    // Concurrency control for bootstrap
    'lock_seconds'  => 5, // how long a lock lives
    'block_seconds' => 5,  // how long to wait for another request to finish seeding

    // the guard Spatie uses in your app
    'guard' => 'sanctum',
    'prefer_global_permissions' => true,
    'sync_strategy' => 'additive',
    // Role that must never be renamed/deleted; will be auto-assigned to tenant owner
    'owner_role' => 'owner',
    'protected_roles' => ['owner'],

    // Flat list of ALL permissions you want available by default
    // (Optional—if omitted, the code will infer from roles’ arrays)
    'permissions' => [
        // --- fill with your real permissions ---
        'settings.update',
        'roles.read',
        'roles.write',
        'permissions.read',
        'permissions.write',
        'menu.dashboard',
        'menu.content',
        'menu.settings',
        'menu.projects',
        'menu.properties',
        'menu.blog',
        'menu.customers',
        'menu.apps',
        'menu.affiliate',
        'menu.whatsapp',
    ],
    // Permissions that are safe to show/assign in tenant UIs
    'tenant_visible_permissions' => [
        'settings.update',
        'roles.read',
        'permissions.read',
        'menu.dashboard',
        'menu.content',
        'menu.settings',
        'menu.projects',
        'menu.properties',
        'menu.blog',
        'menu.customers',
        'menu.apps',
        'menu.affiliate',
        'menu.whatsapp',
    ],

    // Nice UI grouping (optional)
    'groups' => [
        'Dashboard' => ['menu.dashboard'],
        'Projects' => ['menu.projects'],
        'Properties' => ['menu.properties'],
    ],
    // Role → permissions mapping (fill with your actual needs)
    'roles' => [
        'owner'     => [ // usually the superset
            'settings.update',
            'roles.read', 'roles.write',
            'permissions.read', 'permissions.write',
            'employees.roles.sync', 'employees.perms.sync',
            'menu.dashboard', 'menu.content', 'menu.settings',
            'menu.projects', 'menu.properties', 'menu.blog', 'menu.customers',
            'menu.apps', 'menu.affiliate',
        ],
        'manager'   => [
            'settings.update',
            'roles.read',
            'permissions.read',
            'employees.roles.sync',
            'menu.dashboard', 'menu.content', 'menu.customers',
            'menu.projects', 'menu.properties',
        ],
        'supporter' => [
            'menu.dashboard', 'menu.customers',
        ],
    ],
    // Permissions that should never appear in tenant UI
    'system_only' => ['rbac.*','tenants.manage','impersonate.*'],

    // // flat list of permissions you support
    // 'permissions' => [
    //     // menus / UI
    //     'menu.dashboard',
    //     'menu.content',
    //     'menu.settings',
    //     'menu.customers',
    //     'menu.crm',
    //     'menu.projects',
    //     'menu.properties',
    //     'menu.blog',
    //     'menu.apps',
    //     'menu.affiliate',

    //     // settings / RBAC
    //     'settings.update',
    //     'rbac.roles.read',
    //     'rbac.roles.write',
    //     'rbac.perms.read',
    //     'rbac.perms.write',
    //     'rbac.assign',

    //     // domain/business modules (sample)
    //     'customers.view','customers.create','customers.update','customers.delete',
    //     'projects.view','projects.create','projects.update','projects.delete',
    //     'properties.view','properties.create','properties.update','properties.delete',
    // ],

    // // role → permissions template
    // 'role_templates' => [
    //     // hard-protected role (tenant only)
    //     'owner'     => ['*'], // full access via Gate::before, templates kept for documentation

    //     // tenant can grant to employees
    //     'manager'   => [
    //         'menu.*',
    //         'settings.update',
    //         'rbac.*',
    //         'customers.*',
    //         'projects.*',
    //         'properties.*',
    //     ],
    //     'supporter' => [
    //         'menu.dashboard','menu.customers','menu.properties',
    //         'customers.view','customers.create','customers.update',
    //         'properties.view','properties.create','properties.update',
    //     ],
    // ],
];
