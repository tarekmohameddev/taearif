<?php

declare(strict_types=1);

return [
    'enabled' => env('DASHBOARD_PRESENCE_ENABLED', true),
    'heartbeat_seconds' => (int) env('DASHBOARD_PRESENCE_HEARTBEAT_SECONDS', 45),
    'online_window_seconds' => (int) env('DASHBOARD_PRESENCE_ONLINE_WINDOW_SECONDS', 120),
    'redis_connection' => env('DASHBOARD_PRESENCE_REDIS_CONNECTION', 'cache'),
    'users_key' => env('DASHBOARD_PRESENCE_USERS_KEY', 'presence:dashboard:users'),
    'tenant_organizations_key' => env('DASHBOARD_PRESENCE_TENANT_ORGANIZATIONS_KEY', 'presence:dashboard:tenant-organizations'),
    'admin_poll_seconds' => (int) env('DASHBOARD_PRESENCE_ADMIN_POLL_SECONDS', 30),
    'exclude_impersonation' => env('DASHBOARD_PRESENCE_EXCLUDE_IMPERSONATION', true),
];
