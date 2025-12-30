<?php

namespace App\Services\Analytics;

use App\Models\Analytics\AnalyticsDailySummary;
use App\Services\GoogleAnalyticsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AnalyticsMaterializationService
{
    public function __construct(
        protected GoogleAnalyticsService $gaService
    ) {}

    /**
     * Main sync method - materializes all metric types for a tenant/date
     */
    public function materializeTenantData(string $tenantId, Carbon $date): void
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();

        try {
            $this->materializeVisitorsData($tenantId, $startDate, $endDate, $date);
            $this->materializeDevicesData($tenantId, $startDate, $endDate, $date);
            $this->materializeTrafficSourcesData($tenantId, $startDate, $endDate, $date);
            $this->materializeSummaryData($tenantId, $startDate, $endDate, $date);
            $this->materializeTopPagesData($tenantId, $startDate, $endDate, $date);
        } catch (\Exception $e) {
            Log::error('Failed to materialize tenant data', [
                'tenant_id' => $tenantId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Materialize visitors data (date series)
     * Stores data for a single day - for date ranges, call this for each day
     */
    public function materializeVisitorsData(string $tenantId, Carbon $startDate, Carbon $endDate, Carbon $targetDate): void
    {
        try {
            // For single day materialization, query just that day
            $data = $this->gaService->getVisitorData($tenantId, $targetDate->copy()->startOfDay(), $targetDate->copy()->endOfDay());

            // Format data to match API response structure
            $formattedData = collect($data)->map(function ($item) {
                return [
                    'date' => $item['date']->locale('ar')->isoFormat('D MMMM'), // Arabic date format
                    'visits' => $item['sessions'],
                    'uniqueVisitors' => $item['users']
                ];
            })->toArray();

            // Calculate totals for this day
            $totalVisits = collect($data)->sum('sessions');
            $totalUniqueVisitors = collect($data)->sum('users');

            // Store formatted data matching API response (for single day)
            AnalyticsDailySummary::storeData(
                $tenantId,
                $targetDate,
                'visitors',
                [
                    'visitor_data' => $formattedData,
                    'total_visits' => $totalVisits,
                    'total_unique_visitors' => $totalUniqueVisitors,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Failed to materialize visitors data', [
                'tenant_id' => $tenantId,
                'date' => $targetDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Materialize devices data
     */
    public function materializeDevicesData(string $tenantId, Carbon $startDate, Carbon $endDate, Carbon $targetDate): void
    {
        try {
            $tenantFilter = $this->buildTenantFilter($tenantId);
            $devices = $this->gaService->getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter);

            // Store devices data matching API response
            AnalyticsDailySummary::storeData(
                $tenantId,
                $targetDate,
                'devices',
                ['devices' => $devices]
            );
        } catch (\Exception $e) {
            Log::error('Failed to materialize devices data', [
                'tenant_id' => $tenantId,
                'date' => $targetDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Materialize traffic sources data
     */
    public function materializeTrafficSourcesData(string $tenantId, Carbon $startDate, Carbon $endDate, Carbon $targetDate): void
    {
        try {
            $tenantFilter = $this->buildTenantFilter($tenantId, true); // Use CONTAINS for traffic sources
            $sources = $this->gaService->getTrafficSources($startDate, $endDate, $tenantFilter);

            // Store traffic sources data matching API response
            AnalyticsDailySummary::storeData(
                $tenantId,
                $targetDate,
                'traffic_sources',
                ['sources' => $sources]
            );
        } catch (\Exception $e) {
            Log::error('Failed to materialize traffic sources data', [
                'tenant_id' => $tenantId,
                'date' => $targetDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Materialize summary data (overview metrics)
     */
    public function materializeSummaryData(string $tenantId, Carbon $startDate, Carbon $endDate, Carbon $targetDate): void
    {
        try {
            $tenantFilter = $this->buildTenantFilter($tenantId);
            $overview = $this->gaService->getOverviewMetrics($startDate, $endDate, $tenantFilter);

            // Store summary data matching API response structure
            AnalyticsDailySummary::storeData(
                $tenantId,
                $targetDate,
                'summary',
                ['overview' => $overview]
            );
        } catch (\Exception $e) {
            Log::error('Failed to materialize summary data', [
                'tenant_id' => $tenantId,
                'date' => $targetDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Materialize top pages data
     */
    public function materializeTopPagesData(string $tenantId, Carbon $startDate, Carbon $endDate, Carbon $targetDate): void
    {
        try {
            $tenantFilter = $this->buildTenantFilter($tenantId);
            $topPages = $this->gaService->getTopPages($startDate, $endDate, $tenantFilter);

            // Format pages data to match API response
            $totalViews = collect($topPages)->sum('pageViews');
            $formattedPages = collect($topPages)->map(function ($page) use ($totalViews) {
                $percentage = $totalViews > 0 ? round(($page['pageViews'] / $totalViews) * 100, 2) : 0;

                $avgTime = isset($page['averageSessionDuration']) 
                    ? $this->formatDuration($page['averageSessionDuration']) 
                    : 'N/A';

                $uniqueVisitors = isset($page['users']) ? $page['users'] : 0;

                $bounceRate = isset($page['bounceRate']) ? $page['bounceRate'] : 0.0;

                if (is_numeric($bounceRate)) {
                    $bounceRate = (float)$bounceRate;
                    $bounceRateFormatted = $bounceRate <= 1.0
                        ? round($bounceRate * 100, 1)
                        : round($bounceRate, 1);
                } else {
                    $bounceRateFormatted = 0.0;
                }

                return [
                    'path' => $page['path'],
                    'views' => $page['pageViews'],
                    'unique_visitors' => $uniqueVisitors,
                    'bounce_rate' => (float) $bounceRateFormatted,
                    'avg_time' => $avgTime,
                    'percentage' => $percentage,
                ];
            })->toArray();

            // Store top pages data matching API response
            AnalyticsDailySummary::storeData(
                $tenantId,
                $targetDate,
                'top_pages',
                ['pages' => $formattedPages]
            );
        } catch (\Exception $e) {
            Log::error('Failed to materialize top pages data', [
                'tenant_id' => $tenantId,
                'date' => $targetDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all active tenants (users with username)
     */
    public function getAllTenants(): array
    {
        return \DB::table('users')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->pluck('username')
            ->toArray();
    }

    /**
     * Sync data for a date range
     */
    public function syncDateRange(string $tenantId, Carbon $startDate, Carbon $endDate): void
    {
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            try {
                $this->materializeTenantData($tenantId, $currentDate->copy());
                Log::info('Materialized data for tenant and date', [
                    'tenant_id' => $tenantId,
                    'date' => $currentDate->format('Y-m-d'),
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to materialize data for date', [
                    'tenant_id' => $tenantId,
                    'date' => $currentDate->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
                // Continue with next date even if one fails
            }
            
            $currentDate->addDay();
        }
    }

    /**
     * Build tenant filter for Google Analytics queries
     */
    protected function buildTenantFilter(string $tenantId, bool $useContains = false)
    {
        $stringFilterOptions = [
            'value' => $tenantId,
        ];

        if ($useContains) {
            $stringFilterOptions['match_type'] = \Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType::CONTAINS;
        }

        return new \Google\Analytics\Data\V1beta\FilterExpression([
            'filter' => new \Google\Analytics\Data\V1beta\Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new \Google\Analytics\Data\V1beta\Filter\StringFilter($stringFilterOptions),
            ]),
        ]);
    }

    /**
     * Format duration from seconds to MM:SS
     */
    protected function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        $seconds = floor($seconds % 60);
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}

