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
use App\Http\Controllers\Api\Admin\DailyController;
use App\Http\Controllers\Api\Admin\ImpersonationController;

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
    // Daily Follow-up Module — المتابعة اليومية
    // Unified daily operations, reminders, appointments, and tasks
    // -------------------------------------------------------------------------

    Route::prefix('daily')->name('daily.')->group(function () {
        Route::get('/', [DailyController::class, 'index'])
            ->name('index');

        Route::get('today', [DailyController::class, 'today'])
            ->name('today');

        Route::get('overdue', [DailyController::class, 'overdue'])
            ->name('overdue');

        Route::get('statistics', [DailyController::class, 'statistics'])
            ->name('statistics');

        Route::get('reminders', [DailyController::class, 'reminders'])
            ->name('reminders.index');

        Route::get('reminders/{id}', [DailyController::class, 'showReminder'])
            ->name('reminders.show');

        Route::get('appointments', [DailyController::class, 'appointments'])
            ->name('appointments.index');

        Route::get('appointments/{id}', [DailyController::class, 'showAppointment'])
            ->name('appointments.show');

        Route::get('rms-reminders', [DailyController::class, 'rmsReminders'])
            ->name('rms-reminders');
    });

    // -------------------------------------------------------------------------
    // User Impersonation Routes (scoped under users)
    // -------------------------------------------------------------------------

    Route::prefix('users')->name('users.')->group(function () {
        Route::post('{user}/impersonate', [ImpersonationController::class, 'start'])
            ->name('impersonate.start')
            ->middleware('can:impersonate-users');

        Route::get('{user}/impersonation-history', [ImpersonationController::class, 'userHistory'])
            ->name('impersonate.user-history');
    });

    // -------------------------------------------------------------------------
    // Impersonation Module — انتحال الشخصية
    // -------------------------------------------------------------------------

    Route::prefix('impersonate')->name('impersonate.')->group(function () {
        Route::post('exit', [ImpersonationController::class, 'exit'])
            ->name('exit');

        Route::get('active', [ImpersonationController::class, 'active'])
            ->name('active');

        Route::get('history', [ImpersonationController::class, 'history'])
            ->name('history');
    });

});
