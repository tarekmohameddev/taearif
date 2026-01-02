<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Performance Optimizations
    |--------------------------------------------------------------------------
    |
    | Enable performance optimizations for API endpoints including caching,
    | eager loading, and database index usage. When enabled, the following
    | endpoints will use optimized code paths:
    |
    | - api/dashboard/visitors
    | - api/dashboard/devices
    | - api/dashboard/traffic-sources
    | - api/steps/progress
    | - api/user/getUserInfo
    |
    | Set to false to disable optimizations and use original code paths.
    | This allows safe rollout and quick rollback if issues arise.
    |
    */

    'enable_api_performance_optimizations' => env('ENABLE_API_PERFORMANCE_OPTIMIZATIONS', false),
];

