<?php

/**
 * ========================================================================
 * Admin Dashboard API Routes - v1
 * ========================================================================
 *
 * This file defines all routes for the Admin Dashboard API.
 * Routes are organized by domain/module following clean architecture principles.
 *
 * Base URL: /api/v1/admin
 * Authentication: Sanctum (Bearer Token)
 * Guard: admin-sanctum
 *
 * @see docs/admin-dashboard-api-v1/openapi.json for complete API specification
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\AuthController;

// =============================================================================
// PUBLIC ROUTES - No Authentication Required
// =============================================================================

Route::prefix(config('admin-api.prefix'))
    ->name('admin.api.')
    ->group(function () {

        /*
         * Authentication Endpoints
         * These routes are publicly accessible for login/password recovery
         */
        Route::post('login', [AuthController::class, 'login'])
            ->name('login')
            ->middleware('throttle:' . config('admin-api.rate_limits.login'));

        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->name('forgot-password')
            ->middleware('throttle:' . config('admin-api.rate_limits.forgot_password'));

        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->name('reset-password')
            ->middleware('throttle:' . config('admin-api.rate_limits.forgot_password'));
    });

// =============================================================================
// PROTECTED ROUTES - Require Authentication
// =============================================================================

Route::prefix(config('admin-api.prefix'))
    ->name('admin.api.')
    ->middleware(['auth:' . config('admin-api.guard')])
    ->group(function () {
        // Test endpoint
        // Route::get('test', function () {
        //     return response()->json(['message' => 'API is working']);
        // })->name('test');
    // -------------------------------------------------------------------------
    // Authentication & Profile
    // -------------------------------------------------------------------------

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');

    Route::get('me', [AuthController::class, 'me'])
        ->name('me');

    // -------------------------------------------------------------------------
    // Dashboard Module — لوحة التحكم
    // Unified endpoint returning all dashboard sections in one request
    // -------------------------------------------------------------------------

});
