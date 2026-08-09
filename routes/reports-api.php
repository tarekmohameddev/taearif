<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Reports\WhatsAppReportController;
use App\Http\Controllers\Api\V1\Reports\CustomersReportController;
use App\Http\Controllers\Api\V1\Reports\ProjectsReportController;
use App\Http\Controllers\Api\V1\Reports\PropertiesReportController;
use App\Http\Controllers\Api\V1\Reports\PlatformReportController;
use App\Http\Controllers\Api\V1\Reports\ReportExportController;

Route::prefix('v1/reports')
    ->middleware(['auth:sanctum', 'report.access'])
    ->group(function () {

        // ── 1. WhatsApp Report ────────────────────────────────────────────────
        Route::prefix('whatsapp')->group(function () {
            Route::get('summary',                        [WhatsAppReportController::class, 'summary']);
            Route::get('charts/conversation-volume',     [WhatsAppReportController::class, 'conversationVolume']);
            Route::get('charts/hourly-distribution',     [WhatsAppReportController::class, 'hourlyDistribution']);
            Route::get('charts/campaign-delivery',       [WhatsAppReportController::class, 'campaignDelivery']);
            Route::get('charts/automation-triggers',     [WhatsAppReportController::class, 'automationTriggers']);
            Route::get('charts/conversation-status',     [WhatsAppReportController::class, 'conversationStatus']);
            Route::get('tables/agent-performance',       [WhatsAppReportController::class, 'agentPerformance']);
            Route::get('tables/number-performance',      [WhatsAppReportController::class, 'numberPerformance']);
        });

        // ── 2. Customers Report ───────────────────────────────────────────────
        Route::prefix('customers')->group(function () {
            Route::get('summary',                        [CustomersReportController::class, 'summary']);
            Route::get('charts/requests-by-source',      [CustomersReportController::class, 'requestsBySource']);
            Route::get('charts/pipeline-funnel',         [CustomersReportController::class, 'pipelineFunnel']);
            Route::get('charts/daily-new-customers',     [CustomersReportController::class, 'dailyNewCustomers']);
            Route::get('charts/lifecycle-distribution',  [CustomersReportController::class, 'lifecycleDistribution']);
            Route::get('tables/agent-performance',       [CustomersReportController::class, 'agentPerformance']);
            Route::get('tables/top-deals',               [CustomersReportController::class, 'topDeals']);
        });

        // ── 3. Projects Report ────────────────────────────────────────────────
        Route::prefix('projects')->group(function () {
            Route::get('summary',                        [ProjectsReportController::class, 'summary']);
            Route::get('charts/inquiries-trend',         [ProjectsReportController::class, 'inquiriesTrend']);
            Route::get('charts/status-distribution',     [ProjectsReportController::class, 'statusDistribution']);
            Route::get('charts/top-by-visits',           [ProjectsReportController::class, 'topByVisits']);
            Route::get('tables/projects',                [ProjectsReportController::class, 'projectsList']);
        });

        // ── 4. Properties Report ──────────────────────────────────────────────
        Route::prefix('properties')->group(function () {
            Route::get('summary',                        [PropertiesReportController::class, 'summary']);
            Route::get('charts/price-distribution',      [PropertiesReportController::class, 'priceDistribution']);
            Route::get('charts/by-city',                 [PropertiesReportController::class, 'byCity']);
            Route::get('charts/by-type',                 [PropertiesReportController::class, 'byType']);
            Route::get('charts/views-trend',             [PropertiesReportController::class, 'viewsTrend']);
            Route::get('charts/featured-comparison',     [PropertiesReportController::class, 'featuredComparison']);
            Route::get('charts/import-history',          [PropertiesReportController::class, 'importHistory']);
            Route::get('tables/top-listings',            [PropertiesReportController::class, 'topListings']);
            Route::get('tables/agent-performance',       [PropertiesReportController::class, 'agentPerformance']);
        });

        // ── 5. Platform Overview ──────────────────────────────────────────────
        Route::prefix('platform')->group(function () {
            Route::get('summary',                        [PlatformReportController::class, 'summary']);
            Route::get('overview/revenue',               [PlatformReportController::class, 'overviewRevenue']);
            Route::get('overview/portfolio',             [PlatformReportController::class, 'overviewPortfolio']);
            Route::get('employees',                      [PlatformReportController::class, 'employees']);
            Route::get('geographic/cities',              [PlatformReportController::class, 'geographicCities']);
            Route::get('geographic/areas',               [PlatformReportController::class, 'geographicAreas']);
            Route::get('properties/reservation-status', [PlatformReportController::class, 'reservationStatus']);
            Route::get('properties/details',            [PlatformReportController::class, 'propertyDetails']);
            Route::get('financial/monthly',              [PlatformReportController::class, 'financialMonthly']);
            Route::get('financial/summary',              [PlatformReportController::class, 'financialSummary']);
            Route::get('performance/alerts',             [PlatformReportController::class, 'performanceAlerts']);
        });

        // ── 6. Exports ────────────────────────────────────────────────────────
        Route::get('{group}/export', [ReportExportController::class, 'export'])
            ->where('group', 'whatsapp|customers|projects|properties|platform');
    });
