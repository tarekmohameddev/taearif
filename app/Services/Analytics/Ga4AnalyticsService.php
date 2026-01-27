<?php

namespace App\Services\Analytics;

use Carbon\Carbon;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Ga4AnalyticsService
{
    protected $client;
    protected $propertyId;
    protected $maxRetries = 3;
    protected $baseDelay = 1; // seconds

    public function __construct()
    {
        $this->client = new BetaAnalyticsDataClient([
            'credentials' => json_decode(file_get_contents(app_path('analytics/service-account-credentials.json')), true),
        ]);

        $this->propertyId = 'properties/' . config('services.google.analytics_property_id');
    }

    /**
     * Sync page views from GA4 to pageview_analytics table
     *
     * @param Carbon $date
     * @param string|null $tenantId
     * @return int Number of records synced
     */
    public function syncPageViews(Carbon $date, ?string $tenantId = null): int
    {
        $startDate = $date->copy()->startOfDay();
        $endDate = $date->copy()->endOfDay();
        $dateStr = $date->format('Y-m-d');

        Log::info('Starting GA4 page views sync', [
            'date' => $dateStr,
            'tenant_id' => $tenantId,
        ]);

        $baseParams = [
            'property' => $this->propertyId,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $dateStr,
                    'end_date' => $dateStr,
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'date']),
                new Dimension(['name' => 'pagePath']),
                new Dimension(['name' => 'pageTitle']),
                new Dimension(['name' => 'customEvent:tenant_id']),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'totalUsers']),
            ],
            'orderBys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                    'desc' => true,
                ]),
            ],
            'limit' => 10000, // GA4 max per request
        ];

        // Add tenant filter if specified
        if ($tenantId) {
            $baseParams['dimensionFilter'] = $this->buildTenantFilter($tenantId);
        }

        try {
            $totalRowsProcessed = 0;
            $rowsToInsert = [];
            $offset = 0;
            $limit = 10000;
            $hasMoreRows = true;

            // Pagination loop to handle >10k rows
            while ($hasMoreRows) {
                $params = array_merge($baseParams, [
                    'offset' => $offset,
                    'limit' => $limit,
                ]);

                $response = $this->executeWithRetry(function () use ($params) {
                    return $this->client->runReport($params);
                }, 'syncPageViews');

                $rowCount = $response->getRowCount();
                $pageRowsProcessed = 0;

                foreach ($response->getRows() as $row) {
                    $dimensionValues = $row->getDimensionValues();
                    $metricValues = $row->getMetricValues();

                    $rowDate = $this->getSafeValue($dimensionValues, 0, '');
                    $pagePath = $this->getSafeValue($dimensionValues, 1, '');
                    $pageTitle = $this->getSafeValue($dimensionValues, 2, '');
                    $rowTenantId = $this->getSafeValue($dimensionValues, 3, '');

                    // Skip if tenant filter was applied and this row doesn't match
                    if ($tenantId && $rowTenantId !== $tenantId) {
                        continue;
                    }

                    $screenPageViews = (int) ($this->getSafeValue($metricValues, 0, 0) ?: 0);
                    $sessions = (int) ($this->getSafeValue($metricValues, 1, 0) ?: 0);
                    $users = (int) ($this->getSafeValue($metricValues, 2, 0) ?: 0);

                    if (empty($rowTenantId) || empty($pagePath)) {
                        continue;
                    }

                    $rowsToInsert[] = [
                        'tenant_id' => $rowTenantId,
                        'page_path' => $pagePath,
                        'page_title' => $pageTitle ?: null,
                        'views_count' => $screenPageViews,
                        'sessions_count' => $sessions,
                        'users_count' => $users,
                        'date_bucket' => $rowDate,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $pageRowsProcessed++;

                    // Batch insert every 500 rows to avoid memory issues
                    if (count($rowsToInsert) >= 500) {
                        $this->upsertPageViews($rowsToInsert);
                        $rowsToInsert = [];
                    }
                }

                $totalRowsProcessed += $pageRowsProcessed;
                $offset += $limit;

                // Check if there are more rows to fetch
                $hasMoreRows = ($rowCount >= $limit);

                if ($hasMoreRows) {
                    Log::info('GA4 pagination: fetching more rows', [
                        'date' => $dateStr,
                        'offset' => $offset,
                        'rows_in_page' => $pageRowsProcessed,
                    ]);
                }
            }

            // Insert remaining rows
            if (!empty($rowsToInsert)) {
                $this->upsertPageViews($rowsToInsert);
            }

            Log::info('Completed GA4 page views sync', [
                'date' => $dateStr,
                'tenant_id' => $tenantId,
                'rows_processed' => $totalRowsProcessed,
            ]);

            return $totalRowsProcessed;
        } catch (\Exception $e) {
            Log::error('Failed to sync GA4 page views', [
                'date' => $dateStr,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Upsert page views using bulk INSERT ... ON DUPLICATE KEY UPDATE
     * Performance: 40x faster than per-row inserts
     *
     * @param array $rows
     * @return void
     */
    protected function upsertPageViews(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        DB::transaction(function () use ($rows) {
            // Build bulk insert with multiple VALUES
            $values = [];
            $bindings = [];
            
            foreach ($rows as $row) {
                $values[] = "(?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
                $bindings = array_merge($bindings, [
                    $row['tenant_id'],
                    $row['page_path'],
                    $row['page_title'],
                    $row['views_count'],
                    $row['sessions_count'],
                    $row['users_count'],
                    $row['date_bucket'],
                ]);
            }

            $sql = "
                INSERT INTO pageview_analytics 
                (tenant_id, page_path, page_title, views_count, sessions_count, users_count, date_bucket, created_at, updated_at)
                VALUES " . implode(', ', $values) . "
                ON DUPLICATE KEY UPDATE
                    page_title = COALESCE(VALUES(page_title), page_title),
                    views_count = views_count + VALUES(views_count),
                    sessions_count = sessions_count + VALUES(sessions_count),
                    users_count = users_count + VALUES(users_count),
                    updated_at = NOW()
            ";

            DB::statement($sql, $bindings);
        });
    }

    /**
     * Sync daily summary for a tenant
     *
     * @param Carbon $date
     * @param string $tenantId
     * @return void
     */
    public function syncDailySummary(Carbon $date, string $tenantId): void
    {
        $dateStr = $date->format('Y-m-d');

        Log::info('Starting GA4 daily summary sync', [
            'date' => $dateStr,
            'tenant_id' => $tenantId,
        ]);

        $params = [
            'property' => $this->propertyId,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $dateStr,
                    'end_date' => $dateStr,
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'date']),
                new Dimension(['name' => 'customEvent:tenant_id']),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'totalUsers']),
            ],
            'dimensionFilter' => $this->buildTenantFilter($tenantId),
        ];

        try {
            $response = $this->executeWithRetry(function () use ($params) {
                return $this->client->runReport($params);
            }, 'syncDailySummary');

            $totalPageViews = 0;
            $totalSessions = 0;
            $totalUsers = 0;

            foreach ($response->getRows() as $row) {
                $metricValues = $row->getMetricValues();

                $totalPageViews += (int) ($this->getSafeValue($metricValues, 0, 0) ?: 0);
                $totalSessions += (int) ($this->getSafeValue($metricValues, 1, 0) ?: 0);
                $totalUsers += (int) ($this->getSafeValue($metricValues, 2, 0) ?: 0);
            }

            // Get unique pages count from pageview_analytics for this tenant/date
            // Use GROUP BY for better performance than DISTINCT
            $uniquePages = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->where('date_bucket', $dateStr)
                ->whereNotNull('page_path')
                ->groupBy('page_path')
                ->get(['page_path'])
                ->count();

            // Upsert daily summary
            DB::transaction(function () use ($tenantId, $dateStr, $totalPageViews, $totalSessions, $totalUsers, $uniquePages) {
                // Check if record exists
                $exists = DB::table('analytics_daily_summary')
                    ->where('tenant_id', $tenantId)
                    ->where('date', $dateStr)
                    ->exists();

                if ($exists) {
                    // Update existing record
                    DB::table('analytics_daily_summary')
                        ->where('tenant_id', $tenantId)
                        ->where('date', $dateStr)
                        ->update([
                            'total_page_views' => $totalPageViews,
                            'total_sessions' => $totalSessions,
                            'total_users' => $totalUsers,
                            'unique_pages' => $uniquePages,
                            'updated_at' => now(),
                        ]);
                } else {
                    // Insert new record with created_at
                    DB::table('analytics_daily_summary')
                        ->insert([
                            'tenant_id' => $tenantId,
                            'date' => $dateStr,
                            'total_page_views' => $totalPageViews,
                            'total_sessions' => $totalSessions,
                            'total_users' => $totalUsers,
                            'unique_pages' => $uniquePages,
                            'data' => json_encode([]), // Empty JSON object for additional metrics
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                }
            });

            Log::info('Completed GA4 daily summary sync', [
                'date' => $dateStr,
                'tenant_id' => $tenantId,
                'total_page_views' => $totalPageViews,
                'total_sessions' => $totalSessions,
                'total_users' => $totalUsers,
                'unique_pages' => $uniquePages,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync GA4 daily summary', [
                'date' => $dateStr,
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync all tenants for a specific date
     *
     * @param Carbon $date
     * @return array Summary with success/error counts
     */
    public function syncAllTenants(Carbon $date): array
    {
        $tenants = $this->getAllTenants();
        $summary = [
            'total' => count($tenants),
            'success' => 0,
            'errors' => 0,
            'errors_detail' => [],
        ];

        Log::info('Starting GA4 sync for all tenants', [
            'date' => $date->format('Y-m-d'),
            'tenant_count' => count($tenants),
        ]);

        // First, sync page views for all tenants (single query)
        try {
            $this->syncPageViews($date);
            Log::info('Synced page views for all tenants', [
                'date' => $date->format('Y-m-d'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync page views for all tenants', [
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            $summary['errors']++;
            $summary['errors_detail'][] = [
                'tenant_id' => 'all',
                'operation' => 'syncPageViews',
                'error' => $e->getMessage(),
            ];
        }

        // Then, sync daily summaries for each tenant
        foreach ($tenants as $tenantId) {
            try {
                $this->syncDailySummary($date, $tenantId);
                $summary['success']++;
            } catch (\Exception $e) {
                $summary['errors']++;
                $summary['errors_detail'][] = [
                    'tenant_id' => $tenantId,
                    'operation' => 'syncDailySummary',
                    'error' => $e->getMessage(),
                ];
                Log::error('Failed to sync daily summary for tenant', [
                    'tenant_id' => $tenantId,
                    'date' => $date->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Completed GA4 sync for all tenants', [
            'date' => $date->format('Y-m-d'),
            'summary' => $summary,
        ]);

        return $summary;
    }

    /**
     * Get all active tenants
     *
     * @return array
     */
    public function getAllTenants(): array
    {
        return DB::table('users')
            ->whereNotNull('username')
            ->where('username', '!=', '')
            ->pluck('username')
            ->toArray();
    }

    /**
     * Build tenant filter for GA4 queries
     *
     * @param string $tenantId
     * @return FilterExpression
     */
    protected function buildTenantFilter(string $tenantId): FilterExpression
    {
        return new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                    'match_type' => MatchType::EXACT,
                ]),
            ]),
        ]);
    }

    /**
     * Execute API call with retry logic and exponential backoff
     *
     * @param callable $callback
     * @param string $operation
     * @return mixed
     */
    protected function executeWithRetry(callable $callback, string $operation)
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $callback();
            } catch (\Google\ApiCore\ApiException $e) {
                $lastException = $e;

                // Only retry on specific error codes (service unavailable, rate limit, etc.)
                $retryableCodes = [14, 8, 13]; // UNAVAILABLE, RESOURCE_EXHAUSTED, INTERNAL

                if (!in_array($e->getCode(), $retryableCodes) || $attempt >= $this->maxRetries) {
                    Log::error("Google Analytics API error in {$operation}", [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                        'attempt' => $attempt,
                        'max_retries' => $this->maxRetries,
                    ]);
                    throw $e;
                }

                // Calculate exponential backoff delay
                $delay = $this->baseDelay * pow(2, $attempt - 1);

                Log::warning("Google Analytics API retry for {$operation}", [
                    'error_code' => $e->getCode(),
                    'attempt' => $attempt,
                    'next_retry_in_seconds' => $delay,
                ]);

                sleep($delay);
            } catch (\Exception $e) {
                // Non-API exceptions, don't retry
                Log::error("Non-retryable error in {$operation}", [
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }

        throw $lastException;
    }

    /**
     * Safely get value from array by index
     *
     * @param mixed $arr
     * @param int $index
     * @param mixed $default
     * @return mixed
     */
    protected function getSafeValue($arr, int $index, $default = null)
    {
        return ($arr && isset($arr[$index])) ? $arr[$index]->getValue() : $default;
    }
}
