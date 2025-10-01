<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Membership Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the membership system,
    | including package IDs, maintenance mode settings, and other
    | membership-related configurations.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Package Configuration
    |--------------------------------------------------------------------------
    */
    'free_package_id' => env('FREE_PACKAGE_ID', 16),
    'trial_package_id' => env('TRIAL_PACKAGE_ID', 26),
    
    /*
    |--------------------------------------------------------------------------
    | Package Terms
    |--------------------------------------------------------------------------
    */
    'package_terms' => [
        'monthly' => 'monthly',
        'yearly' => 'yearly',
        'lifetime' => 'lifetime',
        'trial' => 'trial',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Paid Package Terms
    |--------------------------------------------------------------------------
    */
    'paid_package_terms' => ['monthly', 'yearly'],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Configuration
    |--------------------------------------------------------------------------
    */
    'auto_maintenance_mode' => env('AUTO_MAINTENANCE_MODE', true),
    'maintenance_mode_message' => env('MAINTENANCE_MODE_MESSAGE', 'تم تحويلك إلى الباقة المجانية بعد انتهاء فترة التجربة. يمكنك ترقية باقاتك في أي وقت من لوحة التحكم.'),

    /*
    |--------------------------------------------------------------------------
    | Notification Configuration
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'subscription_expired' => [
            'enabled' => env('SUBSCRIPTION_EXPIRED_NOTIFICATIONS', true),
            'whatsapp' => [
                'enabled' => env('SUBSCRIPTION_EXPIRED_WHATSAPP', true),
                'message_template' => '{name}، انتهت صلاحية اشتراكك في {package_name} في {expiry_date}. يرجى تجديد اشتراكك لاستعادة الخدمة.',
            ],
            'email' => [
                'enabled' => env('SUBSCRIPTION_EXPIRED_EMAIL', true),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Free Package Features
    |--------------------------------------------------------------------------
    */
    'free_package_features' => [
        'properties_limit' => 10,
        'projects_limit' => 0,
        'custom_domain' => false,
        'maintenance_mode_control' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Business Rules
    |--------------------------------------------------------------------------
    */
    'rules' => [
        'force_maintenance_mode_for_free' => true,
        'prevent_maintenance_mode_control_for_free' => true,
        'auto_disable_maintenance_on_upgrade' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'enabled' => env('MEMBERSHIP_LOGGING', true),
        'level' => env('MEMBERSHIP_LOG_LEVEL', 'info'),
        'channels' => [
            'membership_changes' => 'single',
            'maintenance_mode' => 'single',
        ],
    ],
];
