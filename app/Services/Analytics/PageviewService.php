<?php

namespace App\Services\Analytics;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PageviewService
{
    /**
     * Common bot user agents
     */
    protected array $botUserAgents = [
        'googlebot',
        'bingbot',
        'slurp',
        'duckduckbot',
        'baiduspider',
        'yandexbot',
        'sogou',
        'exabot',
        'facebot',
        'ia_archiver',
        'crawler',
        'spider',
        'bot',
        'crawling',
    ];

    /**
     * Track a page view with atomic increment
     *
     * @param string $tenantId
     * @param string $slug
     * @param string|null $dynamicSlug
     * @param string $path
     * @param string $pageType
     * @param string|null $userAgent
     * @return int Total views count after increment
     */
    public function trackPageView(
        string $tenantId,
        string $slug,
        ?string $dynamicSlug,
        string $path,
        string $pageType,
        ?string $userAgent = null
    ): int {
        // Skip tracking for bots
        if ($this->isBot($userAgent)) {
            Log::debug('Skipping pageview tracking for bot', [
                'user_agent' => $userAgent,
                'path' => $path,
            ]);
            return 0;
        }

        $dateBucket = Carbon::today()->toDateString();
        $normalizedDynamicSlug = $dynamicSlug ?: null;

        return DB::transaction(function () use ($tenantId, $slug, $normalizedDynamicSlug, $path, $pageType, $dateBucket) {
            // Try to increment existing record
            $updated = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->where('page_slug', $slug)
                ->where('dynamic_slug', $normalizedDynamicSlug)
                ->where('date_bucket', $dateBucket)
                ->lockForUpdate()
                ->increment('views_count', 1);

            if ($updated > 0) {
                // Record was updated, get the new count
                $record = DB::table('pageview_analytics')
                    ->where('tenant_id', $tenantId)
                    ->where('page_slug', $slug)
                    ->where('dynamic_slug', $normalizedDynamicSlug)
                    ->where('date_bucket', $dateBucket)
                    ->first();

                return $record ? (int) $record->views_count : 1;
            }

            // No existing record, try to insert new one
            // Handle race condition: if another request inserted between check and insert,
            // catch the duplicate key error and retry increment
            try {
                DB::table('pageview_analytics')->insert([
                    'tenant_id' => $tenantId,
                    'page_slug' => $slug,
                    'dynamic_slug' => $normalizedDynamicSlug,
                    'full_path' => $path,
                    'page_type' => $pageType,
                    'views_count' => 1,
                    'date_bucket' => $dateBucket,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return 1;
            } catch (\Illuminate\Database\QueryException $e) {
                // Duplicate key error (race condition) - retry increment
                if ($e->getCode() === '23000' || str_contains($e->getMessage(), 'Duplicate entry')) {
                    $updated = DB::table('pageview_analytics')
                        ->where('tenant_id', $tenantId)
                        ->where('page_slug', $slug)
                        ->where('dynamic_slug', $normalizedDynamicSlug)
                        ->where('date_bucket', $dateBucket)
                        ->increment('views_count', 1);

                    if ($updated > 0) {
                        $record = DB::table('pageview_analytics')
                            ->where('tenant_id', $tenantId)
                            ->where('page_slug', $slug)
                            ->where('dynamic_slug', $normalizedDynamicSlug)
                            ->where('date_bucket', $dateBucket)
                            ->first();

                        return $record ? (int) $record->views_count : 1;
                    }
                }

                // Re-throw if it's not a duplicate key error
                throw $e;
            }
        });
    }

    /**
     * Get dashboard summary for a tenant
     *
     * @param string $tenantId
     * @param int $days
     * @return array
     */
    public function getDashboardSummary(string $tenantId, int $days = 30): array
    {
        $startDate = Carbon::today()->subDays($days)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $summary = DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date_bucket', [$startDate, $endDate])
            ->selectRaw('
                SUM(views_count) as total_views,
                COUNT(DISTINCT page_slug) as unique_pages,
                COUNT(DISTINCT date_bucket) as active_days
            ')
            ->first();

        // Get views by page type
        $viewsByType = DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date_bucket', [$startDate, $endDate])
            ->select('page_type', DB::raw('SUM(views_count) as total_views'))
            ->groupBy('page_type')
            ->pluck('total_views', 'page_type')
            ->toArray();

        // Get daily trend (last 7 days)
        $trendDays = min($days, 7);
        $dailyTrend = DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date_bucket', [
                Carbon::today()->subDays($trendDays - 1)->toDateString(),
                $endDate
            ])
            ->select('date_bucket', DB::raw('SUM(views_count) as views'))
            ->groupBy('date_bucket')
            ->orderBy('date_bucket')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->date_bucket => (int) $item->views];
            })
            ->toArray();

        return [
            'total_views' => (int) ($summary->total_views ?? 0),
            'unique_pages' => (int) ($summary->unique_pages ?? 0),
            'active_days' => (int) ($summary->active_days ?? 0),
            'views_by_type' => $viewsByType,
            'daily_trend' => $dailyTrend,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'days' => $days,
            ],
        ];
    }

    /**
     * Get top pages for a tenant
     *
     * @param string $tenantId
     * @param int $days
     * @param int $limit
     * @param string|null $pageType Optional filter: page, post, project, property (matches TrackPageViewRequest)
     * @return array
     */
    public function getTopPages(string $tenantId, int $days = 30, int $limit = 10, ?string $pageType = null): array
    {
        $startDate = Carbon::today()->subDays($days)->toDateString();
        $endDate = Carbon::today()->toDateString();

        $query = DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date_bucket', [$startDate, $endDate]);

        if ($pageType !== null && $pageType !== '') {
            $query->where('page_type', $pageType);
        }

        return $query
            ->select(
                'page_slug',
                'dynamic_slug',
                'full_path',
                'page_type',
                DB::raw('SUM(views_count) as total_views')
            )
            ->groupBy('page_slug', 'dynamic_slug', 'full_path', 'page_type')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'slug' => $item->page_slug,
                    'dynamic_slug' => $item->dynamic_slug,
                    'path' => $item->full_path,
                    'page_type' => $item->page_type,
                    'views' => (int) $item->total_views,
                ];
            })
            ->toArray();
    }

    /**
     * Get top posts for a tenant
     *
     * @param string $tenantId
     * @param int $days
     * @param int $limit
     * @return array
     */
    public function getTopPosts(string $tenantId, int $days = 30, int $limit = 10): array
    {
        $startDate = Carbon::today()->subDays($days)->toDateString();
        $endDate = Carbon::today()->toDateString();

        return DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->where('page_type', 'post')
            ->whereBetween('date_bucket', [$startDate, $endDate])
            ->select(
                'page_slug',
                'dynamic_slug',
                'full_path',
                DB::raw('SUM(views_count) as total_views')
            )
            ->groupBy('page_slug', 'dynamic_slug', 'full_path')
            ->orderByDesc('total_views')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'slug' => $item->page_slug,
                    'dynamic_slug' => $item->dynamic_slug,
                    'path' => $item->full_path,
                    'views' => (int) $item->total_views,
                ];
            })
            ->toArray();
    }

    /**
     * Get views summary by date range
     *
     * @param string $tenantId
     * @param string $startDate
     * @param string $endDate
     * @return array
     */
    public function getViewsSummary(string $tenantId, string $startDate, string $endDate): array
    {
        $summary = DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date_bucket', [$startDate, $endDate])
            ->selectRaw('
                SUM(views_count) as total_views,
                COUNT(DISTINCT page_slug) as unique_pages,
                COUNT(DISTINCT date_bucket) as active_days,
                AVG(views_count) as avg_views_per_day
            ')
            ->first();

        // Get daily breakdown
        $dailyBreakdown = DB::table('pageview_analytics')
            ->where('tenant_id', $tenantId)
            ->whereBetween('date_bucket', [$startDate, $endDate])
            ->select('date_bucket', DB::raw('SUM(views_count) as views'))
            ->groupBy('date_bucket')
            ->orderBy('date_bucket')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date_bucket,
                    'views' => (int) $item->views,
                ];
            })
            ->toArray();

        return [
            'total_views' => (int) ($summary->total_views ?? 0),
            'unique_pages' => (int) ($summary->unique_pages ?? 0),
            'active_days' => (int) ($summary->active_days ?? 0),
            'avg_views_per_day' => round((float) ($summary->avg_views_per_day ?? 0), 2),
            'daily_breakdown' => $dailyBreakdown,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];
    }

    /**
     * Check if user agent is a bot
     *
     * @param string|null $userAgent
     * @return bool
     */
    protected function isBot(?string $userAgent): bool
    {
        if (empty($userAgent)) {
            return false;
        }

        $userAgentLower = strtolower($userAgent);

        foreach ($this->botUserAgents as $bot) {
            if (str_contains($userAgentLower, $bot)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean up old pageview records
     *
     * @param int $monthsToKeep
     * @param bool $aggregateBeforeDelete
     * @return int Number of records deleted
     */
    public function cleanupOldRecords(int $monthsToKeep = 12, bool $aggregateBeforeDelete = false): int
    {
        $cutoffDate = Carbon::today()->subMonths($monthsToKeep)->toDateString();

        if ($aggregateBeforeDelete) {
            // Optional: Aggregate into monthly summaries before deletion
            // This would require a separate monthly_summaries table
            // For now, we'll just delete old records
        }

        $deleted = DB::table('pageview_analytics')
            ->where('date_bucket', '<', $cutoffDate)
            ->delete();

        Log::info('Cleaned up old pageview records', [
            'cutoff_date' => $cutoffDate,
            'records_deleted' => $deleted,
        ]);

        return $deleted;
    }
}
