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
                    'dimensions' => [
                        new Dimension(['name' => 'pageLocation']),  // Full URL
                        new Dimension(['name' => 'pagePath']),
                        new Dimension(['name' => 'customEvent:tenant_id'])
                    ],
                    'metrics'    => [new Metric(['name' => 'screenPageViews'])],
                    'limit'      => 100,
                ]);
            }, 'debugPageViews_all');

            $allPaths = [];
            foreach ($response->getRows() as $row) {
                $fullUrl  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $path     = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $tenantId = $this->getSafeValue($row->getDimensionValues(), 2, '');
                $views    = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $allPaths[] = [
                    'full_url' => $fullUrl,
                    'path' => $path, 
                    'tenant_id' => $tenantId,
                    'views' => $views
                ];
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
                    'dimensions'      => [
                        new Dimension(['name' => 'pageLocation']),  // Full URL
                        new Dimension(['name' => 'pagePath']),
                        new Dimension(['name' => 'customEvent:tenant_id'])
                    ],
                    'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                    'dimensionFilter' => $tenantFilter,
                    'limit'           => 100,
                ]);
            }, 'debugPageViews_tenant');

            $tenantPaths = [];
            foreach ($response->getRows() as $row) {
                $fullUrl  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $path     = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $tenantId = $this->getSafeValue($row->getDimensionValues(), 2, '');
                $views    = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $tenantPaths[] = [
                    'full_url' => $fullUrl,
                    'path' => $path, 
                    'tenant_id' => $tenantId,
                    'views' => $views
                ];
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
                        'dimensions'      => [
                            new Dimension(['name' => 'pageLocation']),  // Full URL
                            new Dimension(['name' => 'pagePath']),
                            new Dimension(['name' => 'customEvent:tenant_id'])
                        ],
                        'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                        'dimensionFilter' => $pathsFilter,
                        'limit'           => count($specificPaths),
                    ]);
                }, 'debugPageViews_specific');

                $specificPathsResult = [];
                foreach ($response->getRows() as $row) {
                    $fullUrl  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                    $path     = $this->getSafeValue($row->getDimensionValues(), 1, '');
                    $tenantId = $this->getSafeValue($row->getDimensionValues(), 2, '');
                    $views    = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                    $specificPathsResult[] = [
                        'full_url' => $fullUrl,
                        'path' => $path, 
                        'tenant_id' => $tenantId,
                        'views' => $views
                    ];
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
     * Get all analytics data with flexible backend filtering
     *
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param array $filters - ['tenant_ids' => [], 'min_views' => 0, 'paths' => [], 'limit' => 100]
     * @return array
     */
    public function getAllAnalyticsWithFilters(Carbon $startDate, Carbon $endDate, array $filters = []): array
    {
        try {
            // Step 1: Get ALL data from GA4 (no tenant filter)
            $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
                return $this->client->runReport([
                    'property'        => $this->propertyId,
                    'dateRanges'      => [new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ])],
                    'dimensions'      => [
                        new Dimension(['name' => 'pagePath']),
                        new Dimension(['name' => 'customEvent:tenant_id']),
                    ],
                    'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                    'orderBys'        => [
                        new OrderBy([
                            'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                            'desc' => true,
                        ]),
                    ],
                    'limit'           => 1000, // Get more data for filtering
                ]);
            }, 'getAllAnalyticsWithFilters');

            // Step 2: Parse all data
            $allData = [];
            foreach ($response->getRows() as $row) {
                $path      = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $tenantId  = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $views     = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);

                if ($path !== '') {
                    $allData[] = [
                        'tenant_id' => $tenantId,
                        'path' => $path,
                        'views' => $views,
                    ];
                }
            }

            // Step 3: Apply backend filters
            $filteredData = $this->applyFilters($allData, $filters);

            // Step 4: Group by tenant if requested
            if (isset($filters['group_by_tenant']) && $filters['group_by_tenant']) {
                return $this->groupByTenant($filteredData);
            }

            return [
                'data' => $filteredData,
                'total_items' => count($filteredData),
                'total_views' => array_sum(array_column($filteredData, 'views')),
                'filters_applied' => $filters,
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching all analytics with filters", [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return [
                'data' => [],
                'total_items' => 0,
                'total_views' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Apply filters to data array
     */
    protected function applyFilters(array $data, array $filters): array
    {
        $filtered = $data;

        // Filter by tenant IDs
        if (!empty($filters['tenant_ids'])) {
            $tenantIds = is_array($filters['tenant_ids'])
                ? $filters['tenant_ids']
                : explode(',', $filters['tenant_ids']);

            $filtered = array_filter($filtered, function($item) use ($tenantIds) {
                return in_array($item['tenant_id'], $tenantIds);
            });
        }

        // Filter by minimum views
        if (isset($filters['min_views']) && $filters['min_views'] > 0) {
            $filtered = array_filter($filtered, function($item) use ($filters) {
                return $item['views'] >= $filters['min_views'];
            });
        }

        // Filter by maximum views
        if (isset($filters['max_views']) && $filters['max_views'] > 0) {
            $filtered = array_filter($filtered, function($item) use ($filters) {
                return $item['views'] <= $filters['max_views'];
            });
        }

        // Filter by paths (contains)
        if (!empty($filters['paths'])) {
            $paths = is_array($filters['paths'])
                ? $filters['paths']
                : explode(',', $filters['paths']);

            $filtered = array_filter($filtered, function($item) use ($paths) {
                foreach ($paths as $path) {
                    if (stripos($item['path'], trim($path)) !== false) {
                        return true;
                    }
                }
                return false;
            });
        }

        // Filter by path prefix
        if (!empty($filters['path_prefix'])) {
            $filtered = array_filter($filtered, function($item) use ($filters) {
                return strpos($item['path'], $filters['path_prefix']) === 0;
            });
        }

        // Filter by path contains
        if (!empty($filters['path_contains'])) {
            $filtered = array_filter($filtered, function($item) use ($filters) {
                return stripos($item['path'], $filters['path_contains']) !== false;
            });
        }

        // Exclude empty tenant_ids if requested
        if (isset($filters['exclude_empty_tenant']) && $filters['exclude_empty_tenant']) {
            $filtered = array_filter($filtered, function($item) {
                return !empty($item['tenant_id']) && $item['tenant_id'] !== '(not set)';
            });
        }

        // Limit results
        if (isset($filters['limit']) && $filters['limit'] > 0) {
            $filtered = array_slice(array_values($filtered), 0, $filters['limit']);
        }

        return array_values($filtered); // Re-index array
    }

    /**
     * Group data by tenant
     */
    protected function groupByTenant(array $data): array
    {
        $grouped = [];

        foreach ($data as $item) {
            $tenantId = $item['tenant_id'];

            if (!isset($grouped[$tenantId])) {
                $grouped[$tenantId] = [
                    'tenant_id' => $tenantId,
                    'total_views' => 0,
                    'total_paths' => 0,
                    'paths' => [],
                ];
            }

            $grouped[$tenantId]['total_views'] += $item['views'];
            $grouped[$tenantId]['total_paths']++;
            $grouped[$tenantId]['paths'][] = [
                'path' => $item['path'],
                'views' => $item['views'],
            ];
        }

        return [
            'tenants' => array_values($grouped),
            'total_tenants' => count($grouped),
        ];
    }

    /**
     * Get page locations (full URLs) with views
     * Returns full URLs including domain, not just paths
     * 
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param string|null $tenantId - Optional filter by tenant
     * @return array
     */
    public function getPageLocations(Carbon $startDate, Carbon $endDate, ?string $tenantId = null): array
    {
        try {
            // Build params
            $params = [
                'property'        => $this->propertyId,
                'dateRanges'      => [new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date'   => $endDate->format('Y-m-d'),
                ])],
                'dimensions'      => [
                    new Dimension(['name' => 'pageLocation']), // Full URL
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                'orderBys'        => [
                    new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                        'desc' => true,
                    ]),
                ],
                'limit'           => 500,
            ];

            // Add tenant filter if specified
            if ($tenantId) {
                $params['dimensionFilter'] = new FilterExpression([
                    'filter' => new Filter([
                        'field_name'    => 'customEvent:tenant_id',
                        'string_filter' => new StringFilter([
                            'value'      => $tenantId,
                            'match_type' => MatchType::EXACT,
                        ]),
                    ]),
                ]);
            }

            $response = $this->executeWithRetry(function() use ($params) {
                return $this->client->runReport($params);
            }, 'getPageLocations');

            $locations = [];
            $totalViews = 0;

            foreach ($response->getRows() as $row) {
                $pageLocation = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $tenantIdValue = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                
                if ($pageLocation !== '') {
                    // Parse URL to extract domain and path
                    $parsedUrl = parse_url($pageLocation);
                    
                    $locations[] = [
                        'full_url' => $pageLocation,
                        'domain' => $parsedUrl['host'] ?? '',
                        'path' => $parsedUrl['path'] ?? '/',
                        'tenant_id' => $tenantIdValue,
                        'views' => $views,
                    ];
                    $totalViews += $views;
                }
            }

            return [
                'locations' => $locations,
                'total_views' => $totalViews,
                'total_locations' => count($locations),
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching page locations", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'locations' => [],
                'total_views' => 0,
                'total_locations' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get realtime data (last 30 minutes)
     * Returns currently active users and recent page views
     * 
     * NOTE: Realtime API does NOT support custom event parameters
     * So we get all data and filter by path pattern on backend
     * 
     * @param string|null $tenantId - Optional filter by tenant (filters by matching subdomain in path)
     * @return array
     */
    public function getRealtimeData(?string $tenantId = null): array
    {
        try {
            // Realtime API - Note: customEvent parameters NOT supported!
            $params = [
                'property' => $this->propertyId,
                'dimensions' => [
                    new Dimension(['name' => 'unifiedScreenName']), // Page path
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'screenPageViews']),
                ],
                'orderBys' => [
                    new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                        'desc' => true,
                    ]),
                ],
                'limit' => 100,
            ];

            // Use runRealtimeReport for last 30 minutes data
            $response = $this->client->runRealtimeReport($params);

            $pages = [];
            $totalActiveUsers = 0;
            $totalViews = 0;

            foreach ($response->getRows() as $row) {
                $pagePath = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $activeUsers = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $views = (int) $this->getSafeValue($row->getMetricValues(), 1, 0);
                
                if ($pagePath !== '') {
                    // Try to extract tenant from path
                    // Paths like: /lira/property/xyz or /lira or just /ar
                    $extractedTenant = $this->extractTenantFromPath($pagePath);
                    
                    $pages[] = [
                        'path' => $pagePath,
                        'tenant_id' => $extractedTenant, // Extracted from path
                        'active_users' => $activeUsers,
                        'views' => $views,
                    ];
                    $totalActiveUsers += $activeUsers;
                    $totalViews += $views;
                }
            }

            // Filter by tenant if specified
            if ($tenantId) {
                $pages = array_filter($pages, function($page) use ($tenantId) {
                    return $page['tenant_id'] === $tenantId;
                });
                $pages = array_values($pages); // Re-index
                
                // Recalculate totals for filtered data
                $totalActiveUsers = array_sum(array_column($pages, 'active_users'));
                $totalViews = array_sum(array_column($pages, 'views'));
            }

            return [
                'pages' => $pages,
                'total_active_users' => $totalActiveUsers,
                'total_views' => $totalViews,
                'total_pages' => count($pages),
                'timeframe' => 'Last 30 minutes',
                'note' => $tenantId ? "Filtered by tenant: {$tenantId}" : "All tenants included",
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching realtime data", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'pages' => [],
                'total_active_users' => 0,
                'total_views' => 0,
                'total_pages' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Extract tenant ID from path
     * Paths like /lira/property/xyz or /lira → tenant is "lira"
     */
    protected function extractTenantFromPath(string $path): ?string
    {
        // Remove leading slash and split
        $parts = explode('/', trim($path, '/'));
        
        // If path is like /lira or /lira/property/xyz
        // First segment might be tenant or language
        $firstSegment = $parts[0] ?? '';
        
        // Common language codes - not tenants
        $languages = ['ar', 'en', 'fr', 'es', 'de'];
        
        if (!empty($firstSegment) && !in_array($firstSegment, $languages)) {
            // Likely a tenant subdomain path
            return $firstSegment;
        }
        
        return null; // Can't determine tenant from path
    }

    /**
     * Get today's analytics data (near realtime with tenant filtering)
     * Returns data from today only - updates every 1-2 hours
     * 
     * @param string|null $tenantId - Optional filter by tenant
     * @return array
     */
    public function getTodayData(?string $tenantId = null): array
    {
        try {
            // Query today's data (from midnight to now)
            $startDate = Carbon::today();
            $endDate = Carbon::now();

            $params = [
                'property' => $this->propertyId,
                'dateRanges' => [new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ])],
                'dimensions' => [
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'totalUsers']),
                ],
                'orderBys' => [
                    new OrderBy([
                        'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                        'desc' => true,
                    ]),
                ],
                'limit' => 200,
            ];

            // Add tenant filter if specified
            if ($tenantId) {
                $params['dimensionFilter'] = new FilterExpression([
                    'filter' => new Filter([
                        'field_name'    => 'customEvent:tenant_id',
                        'string_filter' => new StringFilter([
                            'value'      => $tenantId,
                            'match_type' => MatchType::EXACT,
                        ]),
                    ]),
                ]);
            }

            $response = $this->executeWithRetry(function() use ($params) {
                return $this->client->runReport($params);
            }, 'getTodayData');

            $pages = [];
            $totalViews = 0;
            $totalUsers = 0;

            foreach ($response->getRows() as $row) {
                $path = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $tenantIdValue = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $users = (int) $this->getSafeValue($row->getMetricValues(), 1, 0);
                
                if ($path !== '') {
                    $pages[] = [
                        'path' => $path,
                        'tenant_id' => $tenantIdValue,
                        'views' => $views,
                        'users' => $users,
                    ];
                    $totalViews += $views;
                    $totalUsers += $users;
                }
            }

            return [
                'pages' => $pages,
                'total_views' => $totalViews,
                'total_users' => $totalUsers,
                'total_pages' => count($pages),
                'timeframe' => 'Today (' . $startDate->format('Y-m-d') . ')',
                'last_updated' => Carbon::now()->toIso8601String(),
            ];

        } catch (\Exception $e) {
            Log::error("Error fetching today's data", [
                'tenant_id' => $tenantId,
                'error' => $e->getMessage(),
            ]);

            return [
                'pages' => [],
                'total_views' => 0,
                'total_users' => 0,
                'total_pages' => 0,
                'error' => $e->getMessage(),
            ];
        }
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
