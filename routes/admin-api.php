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
use App\Http\Controllers\Api\Admin\UserController;
use App\Http\Controllers\Api\Admin\LookupController;
use App\Http\Controllers\Api\Admin\PlanController;
use App\Http\Controllers\Api\Admin\SubscriptionController;
use App\Http\Controllers\Api\Admin\ImpersonationController;
use App\Http\Controllers\Api\Admin\CrmController;
use App\Http\Controllers\Api\Admin\LeadController;
use App\Http\Controllers\Api\Admin\DomainController;
use App\Http\Controllers\Api\Admin\MarketingController;
use App\Http\Controllers\Api\Admin\AffiliateController;
use App\Http\Controllers\Api\Admin\BillingController;
use App\Http\Controllers\Api\Admin\EmployeeController;
use App\Http\Controllers\Api\Admin\InquiryController;
use App\Http\Controllers\Api\Admin\PlatformController;
use App\Http\Controllers\Api\Admin\AnalyticsController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\PermissionController;
use App\Http\Controllers\Api\Admin\WhatsappAddonController;

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
        Route::pattern('user', '[0-9]+');
        Route::pattern('employee', '[0-9]+');
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
    // Permissions Module — الأذونات
    // -------------------------------------------------------------------------

    Route::get('permissions', [PermissionController::class, 'index'])
        ->name('permissions.index');

    // -------------------------------------------------------------------------
    // Daily Follow-up Module — المتابعة اليومية
    // Unified daily operations, reminders, appointments, and tasks
    // -------------------------------------------------------------------------

    Route::prefix('daily')->name('daily.')
        ->middleware('checkAdminApiPermission:Dashboard')
        ->group(function () {
        Route::get('/', [DailyController::class, 'index'])
            ->name('index');
        
        // Daily follow-up
        Route::get('follow-up', [DailyController::class, 'followUp'])
            ->name('daily.follow-up');

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

    // -------------------------------------------------------------------------
    // Dashboard Module — لوحة التحكم
    // -------------------------------------------------------------------------

    Route::middleware('checkAdminApiPermission:Dashboard')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('dashboard/quick-stats', [DashboardController::class, 'quickStats'])
            ->name('dashboard.quick-stats');
    });

    // -------------------------------------------------------------------------
    // WhatsApp Add-ons
    // -------------------------------------------------------------------------
    Route::prefix('whatsapp-addons')->name('whatsapp-addons.')->group(function () {
        Route::get('/', [WhatsappAddonController::class, 'index'])->name('index');

        // Plans Management
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\WhatsappAddonPlanController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Admin\WhatsappAddonPlanController::class, 'store'])->name('store');
            Route::put('{id}', [\App\Http\Controllers\Api\Admin\WhatsappAddonPlanController::class, 'update'])->name('update');
            Route::delete('{id}', [\App\Http\Controllers\Api\Admin\WhatsappAddonPlanController::class, 'destroy'])->name('destroy');
            Route::post('{id}/toggle-status', [\App\Http\Controllers\Api\Admin\WhatsappAddonPlanController::class, 'toggleStatus'])->name('toggle-status');
        });
    });

    // -------------------------------------------------------------------------
    // Employee Add-ons
    // -------------------------------------------------------------------------
    Route::prefix('employee-addons')->name('employee-addons.')->group(function () {
        // Plans Management
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\EmployeeAddonPlanController::class, 'index'])->name('index');
            Route::post('/', [\App\Http\Controllers\Api\Admin\EmployeeAddonPlanController::class, 'store'])->name('store');
            Route::put('{id}', [\App\Http\Controllers\Api\Admin\EmployeeAddonPlanController::class, 'update'])->name('update');
            Route::delete('{id}', [\App\Http\Controllers\Api\Admin\EmployeeAddonPlanController::class, 'destroy'])->name('destroy');
            Route::post('{id}/toggle-status', [\App\Http\Controllers\Api\Admin\EmployeeAddonPlanController::class, 'toggleStatus'])->name('toggle-status');
        });
    });

    // -------------------------------------------------------------------------
    // User Management Module — إدارة المستخدمين
    // -------------------------------------------------------------------------

    // Lookups for cities and districts
    Route::prefix('lookups')->name('lookups.')->group(function () {
        Route::get('cities', [LookupController::class, 'cities'])->name('cities');
        Route::get('districts', [LookupController::class, 'districts'])->name('districts');
        Route::get('plans', [LookupController::class, 'plans'])->name('plans');
    });

    Route::prefix('users')->name('users.')
        ->middleware('checkAdminApiPermission:Registered Users')
        ->group(function () {
        Route::get('/', [UserController::class, 'index'])
            ->name('index');

        // Tenants table endpoint (offset-based filters + filter options)
        Route::get('table', [UserController::class, 'table'])
            ->name('table');

        Route::post('/', [UserController::class, 'store'])
            ->name('store');

        Route::get('{user}', [UserController::class, 'show'])
            ->name('show');

        Route::get('{user}/invoices', [UserController::class, 'invoices'])
            ->name('invoices');

        Route::get('{user}/activity', [UserController::class, 'activity'])
            ->name('activity');

        Route::post('{user}/send-whatsapp', [UserController::class, 'sendWhatsApp'])
            ->name('send-whatsapp');

        Route::post('{user}/pause', [UserController::class, 'pause'])
            ->name('pause');

        Route::post('{user}/resume', [UserController::class, 'resume'])
            ->name('resume');

        Route::post('{user}/change-plan', [UserController::class, 'changePlan'])
            ->name('change-plan');

        Route::post('{user}/cancel-subscription', [UserController::class, 'cancelSubscription'])
            ->name('cancel-subscription');

        Route::put('{user}', [UserController::class, 'update'])
            ->name('update');

        Route::delete('{user}', [UserController::class, 'destroy'])
            ->name('destroy');

        Route::put('{user}/password', [UserController::class, 'updatePassword'])
            ->name('password');

        Route::post('{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])
            ->name('send-password-reset');

        Route::post('{user}/ban', [UserController::class, 'toggleBan'])
            ->name('ban');

        Route::post('{user}/featured', [UserController::class, 'toggleFeatured'])
            ->name('featured');

        // -------------------------------------------------------------------------
        // User Impersonation Routes (scoped under users)
        // -------------------------------------------------------------------------
        Route::post('{user}/impersonate', [ImpersonationController::class, 'start'])
            ->name('impersonate.start')
            ->middleware('can:impersonate-users');

        Route::get('{user}/impersonation-history', [ImpersonationController::class, 'userHistory'])
            ->name('impersonate.user-history');
    });

    // -------------------------------------------------------------------------
    // Plans Management Module — إدارة الباقات
    // -------------------------------------------------------------------------

    Route::prefix('plans')->name('plans.')
        ->middleware('checkAdminApiPermission:Packages')
        ->group(function () {
        Route::get('/', [PlanController::class, 'index'])
            ->name('index');

        Route::post('/', [PlanController::class, 'store'])
            ->name('store');

        Route::get('{plan}', [PlanController::class, 'show'])
            ->name('show');

        Route::put('{plan}', [PlanController::class, 'update'])
            ->name('update');

        Route::delete('{plan}', [PlanController::class, 'destroy'])
            ->name('destroy');

        Route::post('{plan}/active', [PlanController::class, 'toggleActive'])
            ->name('active');

        Route::post('{plan}/featured', [PlanController::class, 'toggleFeatured'])
            ->name('featured');
    });

    // -------------------------------------------------------------------------
    // Subscriptions Module — الاشتراكات
    // -------------------------------------------------------------------------

    Route::prefix('subscriptions')->name('subscriptions.')
        ->middleware('checkAdminApiPermission:Packages')
        ->group(function () {
        Route::get('/', [SubscriptionController::class, 'index'])
            ->name('index');

        Route::get('statistics', [SubscriptionController::class, 'statistics'])
            ->name('statistics');

        Route::post('{subscription}/change-plan', [SubscriptionController::class, 'changePlan'])
            ->name('change-plan');

        Route::get('{subscriptionId}', [SubscriptionController::class, 'show'])
            ->name('show');
    });

    Route::get('users/{user}/subscription', [SubscriptionController::class, 'showByUser'])
        ->name('users.subscription.show')
        ->middleware('checkAdminApiPermission:Packages');

    // -------------------------------------------------------------------------
    // Billing & Invoices Module — الفوترة
    // -------------------------------------------------------------------------

    Route::prefix('billing')->name('billing.')
        ->middleware('checkAdminApiPermission:Payment Log')
        ->group(function () {
        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [BillingController::class, 'index'])
                ->name('index');

            Route::get('user/{userId}', [BillingController::class, 'showByUser'])
                ->name('showByUser');

            Route::get('{invoiceId}', [BillingController::class, 'show'])
                ->name('show');

            Route::post('{invoiceId}/approve', [BillingController::class, 'approve'])
                ->name('approve');

            Route::post('{invoiceId}/reject', [BillingController::class, 'reject'])
                ->name('reject');
        });

        Route::get('statistics', [BillingController::class, 'statistics'])
            ->name('statistics');

        Route::get('revenue', [BillingController::class, 'revenue'])
            ->name('revenue');
    });

    // -------------------------------------------------------------------------
    // CRM Module — إدارة علاقات العملاء
    // -------------------------------------------------------------------------

    Route::middleware('checkAdminApiPermission:Registered Users')->group(function () {
        Route::get('crm', [CrmController::class, 'index'])
            ->name('crm.index');

        Route::prefix('crm/leads')->name('crm.leads.')->group(function () {
            Route::get('/', [LeadController::class, 'index'])
                ->name('index');

            Route::post('/', [LeadController::class, 'store'])
                ->name('store');

            Route::get('{lead}', [LeadController::class, 'show'])
                ->name('show');

            Route::put('{lead}', [LeadController::class, 'update'])
                ->name('update');

            Route::delete('{lead}', [LeadController::class, 'destroy'])
                ->name('destroy');

            Route::post('{lead}/move', [LeadController::class, 'moveStage'])
                ->name('move');

            Route::post('{lead}/convert', [LeadController::class, 'convert'])
                ->name('convert');

            Route::get('{lead}/activities', [LeadController::class, 'activities'])
                ->name('activities.index');

            Route::post('{lead}/activities', [LeadController::class, 'storeActivity'])
                ->name('activities.store');

            Route::put('{lead}/activities/{activity}', [LeadController::class, 'updateActivity'])
                ->name('activities.update');

            Route::delete('{lead}/activities/{activity}', [LeadController::class, 'destroyActivity'])
                ->name('activities.destroy');
        });
    });

    // -------------------------------------------------------------------------
    // Domains Management Module — النطاقات
    // -------------------------------------------------------------------------

    Route::prefix('domains')->name('domains.')
        ->middleware('checkAdminApiPermission:Settings')
        ->group(function () {
        Route::get('/', [DomainController::class, 'index'])
            ->name('index');

        Route::post('/', [DomainController::class, 'store'])
            ->name('store');

        Route::get('{domain}', [DomainController::class, 'show'])
            ->name('show');

        Route::put('{domain}', [DomainController::class, 'update'])
            ->name('update');

        Route::delete('{domain}', [DomainController::class, 'destroy'])
            ->name('destroy');

        Route::post('{domain}/approve', [DomainController::class, 'approve'])
            ->name('approve');

        Route::post('{domain}/reject', [DomainController::class, 'reject'])
            ->name('reject');

        Route::post('{domain}/toggle-status', [DomainController::class, 'toggleStatus'])
            ->name('toggle-status');

        Route::get('statistics/all', [DomainController::class, 'statistics'])
            ->name('statistics');

        // Domains metadata (registrar / expiry / auto-renewal)
        Route::patch('{domain}/metadata', [DomainController::class, 'updateMetadata'])
            ->name('update-metadata');

        // Renewal summary & action
        Route::get('{domain}/renewal', [DomainController::class, 'renewalSummary'])
            ->name('renewal-summary');
        Route::post('{domain}/renew', [DomainController::class, 'renew'])
            ->name('renew');

        // SSL management
        Route::patch('{domain}/ssl', [DomainController::class, 'updateSsl'])
            ->name('update-ssl');

        // DNS Records management
        Route::get('{domain}/dns-records', [DomainController::class, 'getDnsRecords'])
            ->name('get-dns-records');
        Route::put('{domain}/dns-records', [DomainController::class, 'updateDnsRecords'])
            ->name('update-dns-records');
    });

    // Domain Renewal Pricing Management
    Route::prefix('domain-renewal-pricings')->name('domain-renewal-pricings.')
        ->middleware('checkAdminApiPermission:Settings')
        ->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\DomainRenewalPricingController::class, 'index'])
                ->name('index');

            Route::post('/', [\App\Http\Controllers\Api\Admin\DomainRenewalPricingController::class, 'store'])
                ->name('store');

            Route::get('{id}', [\App\Http\Controllers\Api\Admin\DomainRenewalPricingController::class, 'show'])
                ->name('show');

            Route::put('{id}', [\App\Http\Controllers\Api\Admin\DomainRenewalPricingController::class, 'update'])
                ->name('update');

            Route::delete('{id}', [\App\Http\Controllers\Api\Admin\DomainRenewalPricingController::class, 'destroy'])
                ->name('destroy');
        });

    // -------------------------------------------------------------------------
    // Marketing Module — التسويق
    // -------------------------------------------------------------------------

    Route::middleware('checkAdminApiPermission:Settings')->group(function () {
        Route::get('marketing', [MarketingController::class, 'index'])
            ->name('marketing.index');

        Route::get('marketing/statistics', [MarketingController::class, 'statistics'])
            ->name('marketing.statistics');

        Route::prefix('marketing/whatsapp/templates')->name('marketing.whatsapp.templates.')->group(function () {
            Route::get('/', [MarketingController::class, 'templates'])
                ->name('index');

            Route::post('/', [MarketingController::class, 'storeTemplate'])
                ->name('store');

            Route::get('{template}', [MarketingController::class, 'showTemplate'])
                ->name('show');

            Route::put('{template}', [MarketingController::class, 'updateTemplate'])
                ->name('update');

            Route::delete('{template}', [MarketingController::class, 'destroyTemplate'])
                ->name('destroy');

            Route::post('{template}/toggle-status', [MarketingController::class, 'toggleTemplateStatus'])
                ->name('toggle-status');
        });

        Route::get('marketing/whatsapp/settings', [MarketingController::class, 'getWhatsAppSettings'])
            ->name('marketing.whatsapp.settings');

        Route::put('marketing/whatsapp/settings', [MarketingController::class, 'updateWhatsAppSettings'])
            ->name('marketing.whatsapp.settings.update');

        Route::get('marketing/automated-messages', [MarketingController::class, 'getAutomatedMessages'])
            ->name('marketing.automated-messages');

        Route::get('marketing/automated-messages/{type}', [MarketingController::class, 'getAutomatedMessage'])
            ->name('marketing.automated-messages.show');

        Route::put('marketing/automated-messages/{type}', [MarketingController::class, 'updateAutomatedMessage'])
            ->name('marketing.automated-messages.update');
    });

    // -------------------------------------------------------------------------
    // Affiliates Management Module — برنامج الإحالة
    // -------------------------------------------------------------------------

    Route::prefix('affiliates')->name('affiliates.')
        ->middleware('checkAdminApiPermission:Settings')
        ->group(function () {
        Route::get('/', [AffiliateController::class, 'index'])
            ->name('index');

        Route::post('/', [AffiliateController::class, 'store'])
            ->name('store');

        Route::get('statistics/all', [AffiliateController::class, 'statistics'])
            ->name('statistics');

        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [AffiliateController::class, 'transactions'])
                ->name('index');

            Route::get('{transaction}', [AffiliateController::class, 'showTransaction'])
                ->name('show');

            Route::post('{transaction}/collect', [AffiliateController::class, 'collectTransaction'])
                ->name('collect');
        });

        Route::get('{affiliate}', [AffiliateController::class, 'show'])
            ->name('show');

        Route::put('{affiliate}', [AffiliateController::class, 'update'])
            ->name('update');

        Route::post('{affiliate}/request-status', [AffiliateController::class, 'updateStatus'])
            ->name('request-status.update');
    });

    // -------------------------------------------------------------------------
    // Employees Management Module — إدارة الموظفين
    // -------------------------------------------------------------------------

    Route::prefix('employees')->name('employees.')
        ->middleware('checkAdminApiPermission:Admins Management')
        ->group(function () {
        Route::get('/', [EmployeeController::class, 'index'])
            ->name('index');

        Route::post('/', [EmployeeController::class, 'store'])
            ->name('store');

        Route::post('upload-image', [EmployeeController::class, 'uploadImage'])
            ->name('upload-image');

        Route::get('roles/list', [EmployeeController::class, 'roles'])
            ->name('roles.list');

        Route::get('statistics', [EmployeeController::class, 'statistics'])
            ->name('statistics');

        Route::get('{employee}', [EmployeeController::class, 'show'])
            ->name('show');

        Route::put('{employee}', [EmployeeController::class, 'update'])
            ->name('update');

        Route::delete('{employee}', [EmployeeController::class, 'destroy'])
            ->name('destroy');

        Route::put('{employee}/password', [EmployeeController::class, 'updatePassword'])
            ->name('password.update');

        Route::post('{employee}/toggle-status', [EmployeeController::class, 'toggleStatus'])
            ->name('toggle-status');

        Route::put('{employee}/role', [EmployeeController::class, 'updateRole'])
            ->name('role.update');
    });

    // -------------------------------------------------------------------------
    // Inquiries/Support Module — الاستفسارات والدعم
    // -------------------------------------------------------------------------

    Route::prefix('inquiries')->name('inquiries.')
        ->middleware('checkAdminApiPermission:Contact Page')
        ->group(function () {
        Route::get('statistics', [InquiryController::class, 'statistics'])
            ->name('statistics');

        Route::get('export', [InquiryController::class, 'export'])
            ->name('export');

        Route::post('bulk-delete', [InquiryController::class, 'bulkDelete'])
            ->name('bulk-delete');

        Route::get('tenant/{userId}', [InquiryController::class, 'byTenant'])
            ->name('by-tenant');

        Route::get('customer/{customerId}', [InquiryController::class, 'byCustomer'])
            ->name('by-customer');

        Route::get('/', [InquiryController::class, 'index'])
            ->name('index');

        Route::post('/', [InquiryController::class, 'store'])
            ->name('store');

        Route::get('{inquiry}', [InquiryController::class, 'show'])
            ->name('show');

        Route::put('{inquiry}', [InquiryController::class, 'update'])
            ->name('update');

        Route::delete('{inquiry}', [InquiryController::class, 'destroy'])
            ->name('destroy');
    });

    // -------------------------------------------------------------------------
    // Platform Settings Module — إعدادات المنصة
    // -------------------------------------------------------------------------

    Route::prefix('platform')->name('platform.')
        ->middleware('checkAdminApiPermission:Settings')
        ->group(function () {
        Route::get('settings', [PlatformController::class, 'index'])
            ->name('settings.index');

        Route::get('settings/{section}', [PlatformController::class, 'show'])
            ->name('settings.show');

        Route::put('settings/{section}', [PlatformController::class, 'update'])
            ->name('settings.update');
    });

    // -------------------------------------------------------------------------
    // Analytics Module — التحليلات المتقدمة
    // -------------------------------------------------------------------------

    Route::prefix('analytics')->name('analytics.')
        ->middleware('checkAdminApiPermission:Dashboard')
        ->group(function () {
        Route::get('overview', [AnalyticsController::class, 'overview'])
            ->name('overview');

        Route::get('mrr', [AnalyticsController::class, 'mrr'])
            ->name('mrr');

        Route::get('churn', [AnalyticsController::class, 'churn'])
            ->name('churn');

        Route::get('plans', [AnalyticsController::class, 'plans'])
            ->name('plans');

        Route::get('lifecycle', [AnalyticsController::class, 'lifecycle'])
            ->name('lifecycle');

        Route::get('clv', [AnalyticsController::class, 'clv'])
            ->name('clv');

        Route::get('cohorts', [AnalyticsController::class, 'cohorts'])
            ->name('cohorts');

        Route::get('forecast', [AnalyticsController::class, 'forecast'])
            ->name('forecast');

        Route::get('geography', [AnalyticsController::class, 'geography'])
            ->name('geography');

        Route::get('activity', [AnalyticsController::class, 'activity'])
            ->name('activity');

        Route::get('referrals', [AnalyticsController::class, 'referrals'])
            ->name('referrals');

        Route::get('compare', [AnalyticsController::class, 'compare'])
            ->name('compare');

        Route::post('export', [AnalyticsController::class, 'export'])
            ->name('export');
    });

});
