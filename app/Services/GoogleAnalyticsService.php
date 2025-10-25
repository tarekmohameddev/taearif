<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Google\Analytics\Data\V1beta\Filter;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\OrderBy;
use Google\Analytics\Data\V1beta\OrderBy\MetricOrderBy;
use Google\Analytics\Data\V1beta\Filter\StringFilter\MatchType;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Google\Analytics\Data\V1beta\Filter\InListFilter;
use Google\Analytics\Data\V1beta\FilterExpressionList;

class GoogleAnalyticsService
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

    protected function translateSourceName($sourceName)
    {
        $translations = [
            'google' => 'البحث العضوي',
            'direct' => 'الروابط المباشرة',
            'social' => 'وسائل التواصل',
            'ads' => 'الإعلانات',
            'other' => 'أخرى',
        ];

        return $translations[$sourceName] ?? $sourceName;
    }

    /**
     * Execute API call with retry logic and exponential backoff
     */
    protected function executeWithRetry(callable $apiCall, string $methodName = 'API call')
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $this->maxRetries; $attempt++) {
            try {
                return $apiCall();
            } catch (\Google\ApiCore\ApiException $e) {
                $lastException = $e;

                // Only retry on specific error codes (service unavailable, rate limit, etc.)
                $retryableCodes = [14, 8, 13]; // UNAVAILABLE, RESOURCE_EXHAUSTED, INTERNAL

                if (!in_array($e->getCode(), $retryableCodes) || $attempt >= $this->maxRetries) {
                    Log::error("Google Analytics API error in {$methodName}", [
                        'error_code' => $e->getCode(),
                        'error_message' => $e->getMessage(),
                        'attempt' => $attempt,
                        'max_retries' => $this->maxRetries
                    ]);
                    throw $e;
                }

                // Calculate exponential backoff delay
                $delay = $this->baseDelay * pow(2, $attempt - 1);

                Log::warning("Google Analytics API retry for {$methodName}", [
                    'error_code' => $e->getCode(),
                    'attempt' => $attempt,
                    'next_retry_in_seconds' => $delay
                ]);

                sleep($delay);
            }
        }

        throw $lastException;
    }

    public function getEventCountsByName($startDate, $endDate, $tenantId = null)
    {
        $params = [
            'property' => $this->propertyId,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'eventName']),
            ],
            'metrics' => [
                new Metric(['name' => 'eventCount']),
            ],
            'orderBys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                    'desc' => true,
                ]),
            ],
            'limit' => 10,
        ];

        if ($tenantId) {
            $params['dimensionFilter'] = new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'customEvent:tenant_id',
                    'string_filter' => new StringFilter([
                        'value' => $tenantId,
                        'match_type' => MatchType::EXACT,
                    ]),
                ]),
            ]);
        }

        $response = $this->executeWithRetry(function() use ($params) {
            return $this->client->runReport($params);
        }, 'getEventCountsByName');

        return collect($response->getRows())->map(function ($row) {
            return [
                'event' => $row->getDimensionValues()[0]->getValue(),
                'count' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        });
    }

    protected function getSafeValue($arr, $index, $default = null)
    {
        return ($arr && isset($arr[$index])) ? $arr[$index]->getValue() : $default;
    }

    public function getVisitorsAndPageViews($startDate, $endDate)
    {
        $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
            return $this->client->runReport([
                'property' => $this->propertyId,
                'dateRanges' => [
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'sessions']),
                ],
            ]);
        }, 'getVisitorsAndPageViews');

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return [
                'pageViews' => 0,
                'sessions' => 0,
                'message' => 'No data available. GA4 may not be receiving traffic.',
            ];
        }

        $metrics = $rows[0]->getMetricValues();

        return [
            'pageViews' => isset($metrics[0]) ? (int) $metrics[0]->getValue() : 0,
            'sessions' => isset($metrics[1]) ? (int) $metrics[1]->getValue() : 0,
        ];
    }

    protected function translateDeviceName($deviceCategory)
    {
        $translations = [
            'mobile' => 'الهاتف المحمول',
            'desktop' => 'الحاسوب',
            'tablet' => 'الجهاز اللوحي',
            'other' => 'أخرى',
        ];

        return $translations[$deviceCategory] ?? $deviceCategory;
    }

    public function getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter)
    {
        $response = $this->executeWithRetry(function() use ($startDate, $endDate, $tenantFilter) {
            return $this->client->runReport([
                'property' => $this->propertyId,
                'dateRanges' => [
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'deviceCategory']),
                ],
                'metrics' => [
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'screenPageViews']),
                ],
                'dimensionFilter' => $tenantFilter,
            ]);
        }, 'getDeviceBreakdown');

        return collect($response->getRows())->map(function ($row) {
            $deviceCategory = isset($row->getDimensionValues()[0]) ? $row->getDimensionValues()[0]->getValue() : 'Unknown Device';
            $sessions = isset($row->getMetricValues()[0]) ? (int) $row->getMetricValues()[0]->getValue() : 0;

            $color = match ($deviceCategory) {
                'mobile' => '#4285F4',
                'desktop' => '#34A853',
                'tablet' => '#A142F4',
                default => '#6B7280',
            };

            return [
                'name' => $this->translateDeviceName($deviceCategory),
                'value' => $sessions,
                'color' => $color,
            ];
        });
    }

    public function getDashboardData($tenantId, $startDate, $endDate)
    {
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                    'match_type' => MatchType::EXACT,  // Changed from CONTAINS to EXACT for precise filtering
                ]),
            ]),
        ]);

        return [
            'overview' => $this->getOverviewMetrics($startDate, $endDate, $tenantFilter),
            'devices' => $this->getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter),
            'trafficSources' => $this->getTrafficSources($startDate, $endDate, $tenantFilter),
            'topPages' => $this->getTopPages($startDate, $endDate, $tenantFilter),
        ];
    }

    protected function getOverviewMetrics($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->executeWithRetry(function() use ($startDate, $endDate, $tenantFilter) {
            return $this->client->runReport([
                'property' => $this->propertyId,
                'dateRanges' => [new DateRange(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')])],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'totalUsers']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                ],
                'dimensionFilter' => $tenantFilter,
            ]);
        }, 'getOverviewMetrics');

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return ['pageViews' => 0, 'sessions' => 0, 'users' => 0, 'bounceRate' => 0, 'averageSessionDuration' => 0];
        }

        $metrics = $rows[0]->getMetricValues();

        return [
            'pageViews' => $this->getSafeValue($metrics, 0, 0),
            'sessions' => $this->getSafeValue($metrics, 1, 0),
            'users' => $this->getSafeValue($metrics, 2, 0),
            'bounceRate' => $this->getSafeValue($metrics, 3, 0),
            'averageSessionDuration' => $this->getSafeValue($metrics, 4, 0),
        ];
    }

    public function getTrafficSources($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->executeWithRetry(function() use ($startDate, $endDate, $tenantFilter) {
            return $this->client->runReport([
                'property' => $this->propertyId,
                'dateRanges' => [
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'sessionSource']),
                    new Dimension(['name' => 'sessionMedium']),
                ],
                'metrics' => [
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'totalUsers']),
                ],
                'dimensionFilter' => $tenantFilter,
            ]);
        }, 'getTrafficSources');

        return collect($response->getRows())->map(function ($row) {
            $source = $this->getSafeValue($row->getDimensionValues(), 0, 'unknown');
            $sessions = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);

            $color = match ($source) {
                '(direct)' => '#34A853',
                '(none)' => '#F4B400',
                'google' => '#4285F4',
                'social' => '#A142F4',
                'ads' => '#F4B400',
                default => '#6B7280',
            };

            return [
                'name' => $this->translateSourceName($source),
                'value' => $sessions,
                'color' => $color,
            ];
        });
    }

    protected function getTopPages($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->executeWithRetry(function() use ($startDate, $endDate, $tenantFilter) {
            return $this->client->runReport([
                'property' => $this->propertyId,
                'dateRanges' => [
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ],
                'dimensions' => [
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'pageTitle']),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'averageSessionDuration']),
                    new Metric(['name' => 'bounceRate']),
                ],
                'dimensionFilter' => $tenantFilter,
                'orderBys' => [
                    new OrderBy(['metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']), 'desc' => true]),
                ],
                'limit' => 20,
            ]);
        }, 'getTopPages');

        $rows = $response->getRows();
        if (count($rows) === 0) {
            return [];
        }

        return collect($rows)->map(function ($row) {
            $pagePath = $this->getSafeValue($row->getDimensionValues(), 0, 'Unknown Path');
            $pageTitle = $this->getSafeValue($row->getDimensionValues(), 1, 'Unknown Title');

            $pageViews = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
            $avgDuration = (float) $this->getSafeValue($row->getMetricValues(), 1, 0);

            $bounceRateRaw = $this->getSafeValue($row->getMetricValues(), 2, 0);
            $bounceRate = is_numeric($bounceRateRaw) && (float)$bounceRateRaw <= 1.0
                ? round((float)$bounceRateRaw * 100, 1)
                : round((float)$bounceRateRaw, 1);

            return [
                'path' => $pagePath,
                'title' => $pageTitle,
                'pageViews' => $pageViews,
                'avgDuration' => $avgDuration,
                'bounceRate' => $bounceRate, // الآن قيمة float حقيقية
            ];
        })->toArray();
    }

    public function getVisitorData(string $tenantId, Carbon $startDate, Carbon $endDate)
    {
        $propertyName = Str::startsWith($this->propertyId, 'properties/')
            ? $this->propertyId
            : "properties/{$this->propertyId}";

        $filterExpression = new FilterExpression([
            'filter' => new Filter([
                'field_name'    => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'match_type'     => MatchType::EXACT,
                    'value'          => $tenantId,
                    'case_sensitive' => false,
                ]),
            ]),
        ]);

        $response = $this->executeWithRetry(function() use ($propertyName, $startDate, $endDate, $filterExpression) {
            return $this->client->runReport([
                'property'        => $propertyName,
                'dateRanges'      => [
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ]),
                ],
                'dimensions'      => [
                    new Dimension([ 'name' => 'date' ]),
                ],
                'metrics'         => [
                    new Metric([ 'name' => 'sessions'   ]),
                    new Metric([ 'name' => 'totalUsers' ]),
                ],
                'dimensionFilter' => $filterExpression,
            ]);
        }, 'getVisitorData');

        return collect($response->getRows())
            ->map(function ($row) {
                return [
                    'date'     => Carbon::parse($row->getDimensionValues()[0]->getValue()),
                    'sessions' => (int)$row->getMetricValues()[0]->getValue(),
                    'users'    => (int)$row->getMetricValues()[1]->getValue(),
                ];
            });
    }

    public function getRecentEvents($startDate, $endDate, $tenantId = null)
    {
        $params = [
            'property' => $this->propertyId,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'eventName']),
            ],
            'metrics' => [
                new Metric(['name' => 'eventCount']),
            ],
            'orderBys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                    'desc' => true,
                ]),
            ],
            'limit' => 10,
        ];

        if ($tenantId) {
            $params['dimensionFilter'] = new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'customEvent:tenant_id',
                    'string_filter' => new StringFilter([
                        'value' => $tenantId,
                        'match_type' => MatchType::EXACT,
                    ]),
                ]),
            ]);
        }

        $response = $this->executeWithRetry(function() use ($params) {
            return $this->client->runReport($params);
        }, 'getRecentEvents');

        return collect($response->getRows())->map(function ($row) {
            return [
                'event' => $row->getDimensionValues()[0]->getValue(),
                'count' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        });
    }


    public function getPageViewsForPaths(string $tenantId, Carbon $startDate, Carbon $endDate, array $paths): array
    {
        // Keep unique, non-empty paths
        $paths = array_values(array_unique(array_filter($paths)));
        if (empty($paths)) {
            return [];
        }

        // tenant filter (using custom event parameter sent with each page_view)
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name'    => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value'      => $tenantId,
                    'match_type' => MatchType::EXACT,  // Changed from CONTAINS to EXACT for precise filtering
                ]),
            ]),
        ]);

        // only the specific page paths we care about
        $pathsFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name'     => 'pagePath',
                'in_list_filter' => new InListFilter([
                    'values'         => $paths,
                    'case_sensitive' => false,
                ]),
            ]),
        ]);

        $dimensionFilter = new FilterExpression([
            'and_group' => new FilterExpressionList([
                'expressions' => [$tenantFilter, $pathsFilter],
            ]),
        ]);

        $response = $this->executeWithRetry(function() use ($startDate, $endDate, $dimensionFilter, $paths) {
            return $this->client->runReport([
                'property'        => $this->propertyId,
                'dateRanges'      => [new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date'   => $endDate->format('Y-m-d'),
                ])],
                'dimensions'      => [new Dimension(['name' => 'pagePath'])],
                'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                'dimensionFilter' => $dimensionFilter,
                'limit'           => count($paths), // enough to cover all candidates
            ]);
        }, 'getPageViewsForPaths');

        $map = [];
        foreach ($response->getRows() as $row) {
            $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
            $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
            if ($path !== '') {
                $map[$path] = $views;
            }
        }
        return $map;
    }

    /**
     * Debug method to diagnose GA4 data issues
     * Returns raw data about what GA4 is actually receiving
     */
    public function debugPageViews(string $tenantId, Carbon $startDate, Carbon $endDate, array $specificPaths = []): array
    {
        $results = [];

        // Test 1: Get ALL page views (no filters) - checks if GA4 is working at all
        try {
            $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
                return $this->client->runReport([
                    'property'   => $this->propertyId,
                    'dateRanges' => [new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ])],
                    'dimensions' => [new Dimension(['name' => 'pagePath'])],
                    'metrics'    => [new Metric(['name' => 'screenPageViews'])],
                    'limit'      => 100,
                ]);
            }, 'debugPageViews_all');

            $allPaths = [];
            foreach ($response->getRows() as $row) {
                $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $allPaths[] = ['path' => $path, 'views' => $views];
            }
            $results['all_paths'] = $allPaths;
            $results['total_paths_found'] = count($allPaths);
        } catch (\Exception $e) {
            $results['all_paths_error'] = $e->getMessage();
        }

        // Test 2: Get page views WITH tenant_id filter
        try {
            $tenantFilter = new FilterExpression([
                'filter' => new Filter([
                    'field_name'    => 'customEvent:tenant_id',
                    'string_filter' => new StringFilter([
                        'value'      => $tenantId,
                        'match_type' => MatchType::EXACT,  // Changed from CONTAINS to EXACT for precise filtering
                    ]),
                ]),
            ]);

            $response = $this->executeWithRetry(function() use ($startDate, $endDate, $tenantFilter) {
                return $this->client->runReport([
                    'property'        => $this->propertyId,
                    'dateRanges'      => [new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ])],
                    'dimensions'      => [new Dimension(['name' => 'pagePath'])],
                    'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                    'dimensionFilter' => $tenantFilter,
                    'limit'           => 100,
                ]);
            }, 'debugPageViews_tenant');

            $tenantPaths = [];
            foreach ($response->getRows() as $row) {
                $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $tenantPaths[] = ['path' => $path, 'views' => $views];
            }
            $results['tenant_filtered_paths'] = $tenantPaths;
            $results['tenant_paths_found'] = count($tenantPaths);
        } catch (\Exception $e) {
            $results['tenant_filter_error'] = $e->getMessage();
        }

        // Test 3: Check if specific paths exist (without tenant filter)
        if (!empty($specificPaths)) {
            try {
                $pathsFilter = new FilterExpression([
                    'filter' => new Filter([
                        'field_name'     => 'pagePath',
                        'in_list_filter' => new InListFilter([
                            'values'         => $specificPaths,
                            'case_sensitive' => false,
                        ]),
                    ]),
                ]);

                $response = $this->executeWithRetry(function() use ($startDate, $endDate, $pathsFilter, $specificPaths) {
                    return $this->client->runReport([
                        'property'        => $this->propertyId,
                        'dateRanges'      => [new DateRange([
                            'start_date' => $startDate->format('Y-m-d'),
                            'end_date'   => $endDate->format('Y-m-d'),
                        ])],
                        'dimensions'      => [new Dimension(['name' => 'pagePath'])],
                        'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                        'dimensionFilter' => $pathsFilter,
                        'limit'           => count($specificPaths),
                    ]);
                }, 'debugPageViews_specific');

                $specificPathsResult = [];
                foreach ($response->getRows() as $row) {
                    $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                    $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                    $specificPathsResult[] = ['path' => $path, 'views' => $views];
                }
                $results['specific_paths_no_tenant_filter'] = $specificPathsResult;
            } catch (\Exception $e) {
                $results['specific_paths_error'] = $e->getMessage();
            }
        }

        // Test 4: Check what custom event parameters are available
        try {
            $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
                return $this->client->runReport([
                    'property'   => $this->propertyId,
                    'dateRanges' => [new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ])],
                    'dimensions' => [new Dimension(['name' => 'customEvent:tenant_id'])],
                    'metrics'    => [new Metric(['name' => 'screenPageViews'])],
                    'limit'      => 50,
                ]);
            }, 'debugPageViews_tenants');

            $tenants = [];
            foreach ($response->getRows() as $row) {
                $tenant = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $views  = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $tenants[] = ['tenant_id' => $tenant, 'views' => $views];
            }
            $results['tenant_ids_found'] = $tenants;
        } catch (\Exception $e) {
            $results['tenant_ids_error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Get tenant-specific page views (production use)
     * Returns ONLY the specified tenant's paths and views
     *
     * @param string $tenantId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return array
     */
    public function getTenantPageViews(string $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name'    => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value'      => $tenantId,
                    'match_type' => MatchType::EXACT,
                ]),
            ]),
        ]);

        try {
            $response = $this->executeWithRetry(function() use ($startDate, $endDate, $tenantFilter) {
                return $this->client->runReport([
                    'property'        => $this->propertyId,
                    'dateRanges'      => [new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ])],
                    'dimensions'      => [new Dimension(['name' => 'pagePath'])],
                    'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                    'dimensionFilter' => $tenantFilter,
                    'orderBys'        => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                            'desc' => true,
                        ]),
                    ],
                    'limit'           => 100,
                ]);
            }, 'getTenantPageViews');

            $paths = [];
            $totalViews = 0;

            foreach ($response->getRows() as $row) {
                $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);

                if ($path !== '') {
                    $paths[] = [
                        'path' => $path,
                        'views' => $views,
                    ];
                    $totalViews += $views;
                }
            }

            return [
                'paths' => $paths,
                'total_views' => $totalViews,
                'total_paths' => count($paths),
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching tenant page views", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'paths' => [],
                'total_views' => 0,
                'total_paths' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

}
