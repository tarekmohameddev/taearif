<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Admin API Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the Admin Dashboard API.
    | You can modify these settings to customize the API behavior.
    |
    */

    /**
     * API Version
     */
    'version' => 'v1',

    /**
     * API Route Prefix
     * Full prefix will be: api/v1/admin
     */
    'prefix' => 'v1/admin',

    /**
     * Authentication Guard
     */
    'guard' => 'admin-sanctum',

    /**
     * Default Pagination
     */
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    /**
     * Rate Limiting
     */
    'rate_limits' => [
        'login' => '10,1',           // 10 attempts per minute
        'forgot_password' => '3,1', // 3 attempts per minute
        'general' => '120,1',       // 120 requests per minute for authenticated routes
    ],

    /**
     * Token Configuration
     */
    'token' => [
        'expiration' => 60 * 24,    // 24 hours (in minutes)
        'default_name' => 'admin-dashboard',
    ],

    /**
     * Logging
     */
    'logging' => [
        'enabled' => true,
        'channel' => 'admin',       // Custom log channel for admin activities
        'log_login_attempts' => true,
        'log_failed_attempts' => true,
    ],

    /**
     * Security
     */
    'security' => [
        'revoke_other_tokens_on_login' => false,
        'require_active_status' => true,
        'track_user_agent' => true,
        'track_ip' => true,
    ],

    /**
     * Error Codes
     */
    'error_codes' => [
        'AUTH_001' => 'Invalid credentials',
        'AUTH_002' => 'Token expired',
        'AUTH_003' => 'Account suspended',
        'VAL_001' => 'Validation failed',
        'VAL_002' => 'Duplicate entry',
        'NOT_FOUND' => 'Resource not found',
        'BIZ_001' => 'Business logic error',
        'SYS_001' => 'System error',
    ],

];
