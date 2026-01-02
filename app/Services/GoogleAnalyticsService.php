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
    protected $slugTenantMap = []; // Request-level cache for slug-to-tenant mappings
    protected $slugLookupService = null; // Lazy-loaded SlugLookupService

    public function __construct()
    {
        $this->client = new BetaAnalyticsDataClient([
            'credentials' => json_decode(file_get_contents(app_path('analytics/service-account-credentials.json')), true),
        ]);

        $this->propertyId = 'properties/' . config('services.google.analytics_property_id');
    }
    
    /**
     * Get SlugLookupService instance (lazy load)
     */
    protected function getSlugLookupService()
    {
        if ($this->slugLookupService === null) {
            $this->slugLookupService = app(\App\Services\Analytics\SlugLookupService::class);
        }
        return $this->slugLookupService;
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
        // Get ALL device data (no GA4 filter) - filter on backend
        $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
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
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics' => [
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'screenPageViews']),
                ],
            ]);
        }, 'getDeviceBreakdown');

        // Collect all paths that need slug lookup (batch optimization)
        $pathsToLookup = [];
        $rowsData = [];
        
        foreach ($response->getRows() as $row) {
            $pagePath = $this->getSafeValue($row->getDimensionValues(), 1, '');
            $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 2, '');
            
            // If tenant is empty, we'll need to look it up
            if ((empty($recordedTenant) || $recordedTenant === '(not set)') && !empty($pagePath)) {
                $pathsToLookup[] = $pagePath;
            }
            
            $rowsData[] = [
                'deviceCategory' => $this->getSafeValue($row->getDimensionValues(), 0, 'Unknown Device'),
                'pagePath' => $pagePath,
                'recordedTenant' => $recordedTenant,
                'sessions' => (int) $this->getSafeValue($row->getMetricValues(), 0, 0),
            ];
        }
        
        // Batch lookup slugs for all paths at once
        $slugTenantMap = [];
        if (!empty($pathsToLookup)) {
            $propertyPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/property/') !== false || strpos($p, '/ar/property/') !== false || strpos($p, '/en/property/') !== false);
            $projectPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/project/') !== false || strpos($p, '/ar/project/') !== false || strpos($p, '/en/project/') !== false);
            
            if (!empty($propertyPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($propertyPaths, 'property'));
            }
            if (!empty($projectPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($projectPaths, 'project'));
            }
        }

        // Aggregate by device with smart tenant filtering
        $deviceMap = [];

        foreach ($rowsData as $rowData) {
            $deviceCategory = $rowData['deviceCategory'];
            $pagePath = $rowData['pagePath'];
            $recordedTenant = $rowData['recordedTenant'];
            $sessions = $rowData['sessions'];

            // Smart tenant matching
            $belongsToTenant = false;
            if (!empty($recordedTenant) && $recordedTenant === $tenantId) {
                $belongsToTenant = true;
            } elseif (empty($recordedTenant) || $recordedTenant === '(not set)') {
                // Use batch lookup result
                $slug = $this->extractSlugFromPath($pagePath);
                if ($slug && isset($slugTenantMap[strtolower($slug)])) {
                    $derivedTenant = $slugTenantMap[strtolower($slug)];
                    if ($derivedTenant === $tenantId) {
                        $belongsToTenant = true;
                    }
                }
            }

            if (!$belongsToTenant) {
                continue;
            }

            // Aggregate by device
            if (!isset($deviceMap[$deviceCategory])) {
                $deviceMap[$deviceCategory] = $sessions;
            } else {
                $deviceMap[$deviceCategory] += $sessions;
            }
        }

        // Format response
        return collect($deviceMap)->map(function ($sessions, $deviceCategory) {
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
        })->values();
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

    /**
     * Get overview metrics only (without devices, traffic sources, top pages)
     * Used by summary endpoint to avoid fetching unused data
     */
    public function getOverviewMetricsOnly(string $tenantId, Carbon $startDate, Carbon $endDate): array
    {
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                    'match_type' => MatchType::EXACT,
                ]),
            ]),
        ]);

        return $this->getOverviewMetrics($startDate, $endDate, $tenantFilter);
    }

    protected function getOverviewMetrics($startDate, $endDate, FilterExpression $tenantFilter)
    {
        // Extract tenant ID from filter
        $tenantId = null;
        if ($tenantFilter->hasFilter()) {
            $filter = $tenantFilter->getFilter();
            if ($filter->hasStringFilter()) {
                $tenantId = $filter->getStringFilter()->getValue();
            }
        }

        // Get ALL overview data (no GA4 filter) - filter on backend
        $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
            return $this->client->runReport([
                'property' => $this->propertyId,
                'dateRanges' => [new DateRange(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')])],
                'dimensions' => [
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'totalUsers']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                ],
            ]);
        }, 'getOverviewMetrics');

        // Collect all paths that need slug lookup (batch optimization)
        $pathsToLookup = [];
        $rowsData = [];
        
        foreach ($response->getRows() as $row) {
            $pagePath = $this->getSafeValue($row->getDimensionValues(), 0, '');
            $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 1, '');
            
            // If tenant is empty, we'll need to look it up
            if ($tenantId && (empty($recordedTenant) || $recordedTenant === '(not set)') && !empty($pagePath)) {
                $pathsToLookup[] = $pagePath;
            }
            
            $rowsData[] = [
                'pagePath' => $pagePath,
                'recordedTenant' => $recordedTenant,
                'pageViews' => (int) $this->getSafeValue($row->getMetricValues(), 0, 0),
                'sessions' => (int) $this->getSafeValue($row->getMetricValues(), 1, 0),
                'users' => (int) $this->getSafeValue($row->getMetricValues(), 2, 0),
                'bounceRate' => (float) $this->getSafeValue($row->getMetricValues(), 3, 0),
                'duration' => (float) $this->getSafeValue($row->getMetricValues(), 4, 0),
            ];
        }
        
        // Batch lookup slugs for all paths at once
        $slugTenantMap = [];
        if (!empty($pathsToLookup)) {
            $propertyPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/property/') !== false || strpos($p, '/ar/property/') !== false || strpos($p, '/en/property/') !== false);
            $projectPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/project/') !== false || strpos($p, '/ar/project/') !== false || strpos($p, '/en/project/') !== false);
            
            if (!empty($propertyPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($propertyPaths, 'property'));
            }
            if (!empty($projectPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($projectPaths, 'project'));
            }
        }

        // Aggregate metrics with smart tenant filtering
        $totals = [
            'pageViews' => 0,
            'sessions' => 0,
            'users' => 0,
            'bounceRateSum' => 0,
            'durationSum' => 0,
            'rowCount' => 0,
        ];

        foreach ($rowsData as $rowData) {
            $pagePath = $rowData['pagePath'];
            $recordedTenant = $rowData['recordedTenant'];

            // Smart tenant matching
            $belongsToTenant = false;
            if ($tenantId) {
                if (!empty($recordedTenant) && $recordedTenant === $tenantId) {
                    $belongsToTenant = true;
                } elseif (empty($recordedTenant) || $recordedTenant === '(not set)') {
                    // Use batch lookup result
                    $slug = $this->extractSlugFromPath($pagePath);
                    if ($slug && isset($slugTenantMap[strtolower($slug)])) {
                        $derivedTenant = $slugTenantMap[strtolower($slug)];
                        if ($derivedTenant === $tenantId) {
                            $belongsToTenant = true;
                        }
                    }
                }
            } else {
                $belongsToTenant = true;
            }

            if (!$belongsToTenant) {
                continue;
            }

            // Aggregate metrics
            $totals['pageViews'] += $rowData['pageViews'];
            $totals['sessions'] += $rowData['sessions'];
            $totals['users'] += $rowData['users'];
            $totals['bounceRateSum'] += $rowData['bounceRate'];
            $totals['durationSum'] += $rowData['duration'];
            $totals['rowCount']++;
        }

        if ($totals['rowCount'] === 0) {
            return ['pageViews' => 0, 'sessions' => 0, 'users' => 0, 'bounceRate' => 0, 'averageSessionDuration' => 0];
        }

        return [
            'pageViews' => $totals['pageViews'],
            'sessions' => $totals['sessions'],
            'users' => $totals['users'],
            'bounceRate' => $totals['bounceRateSum'] / $totals['rowCount'],  // Average bounce rate
            'averageSessionDuration' => $totals['durationSum'] / $totals['rowCount'],  // Average duration
        ];
    }

    public function getTrafficSources($startDate, $endDate, FilterExpression $tenantFilter)
    {
        // Extract tenant ID from filter
        $tenantId = null;
        if ($tenantFilter->hasFilter()) {
            $filter = $tenantFilter->getFilter();
            if ($filter->hasStringFilter()) {
                $tenantId = $filter->getStringFilter()->getValue();
            }
        }

        // Get ALL traffic sources (no GA4 filter) - filter on backend
        $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
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
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics' => [
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'totalUsers']),
                ],
            ]);
        }, 'getTrafficSources');

        // Collect all paths that need slug lookup (batch optimization)
        $pathsToLookup = [];
        $rowsData = [];
        
        foreach ($response->getRows() as $row) {
            $pagePath = $this->getSafeValue($row->getDimensionValues(), 2, '');
            $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 3, '');
            
            // If tenant is empty, we'll need to look it up
            if ($tenantId && (empty($recordedTenant) || $recordedTenant === '(not set)') && !empty($pagePath)) {
                $pathsToLookup[] = $pagePath;
            }
            
            $rowsData[] = [
                'source' => $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'),
                'medium' => $this->getSafeValue($row->getDimensionValues(), 1, ''),
                'pagePath' => $pagePath,
                'recordedTenant' => $recordedTenant,
                'sessions' => (int) $this->getSafeValue($row->getMetricValues(), 0, 0),
            ];
        }
        
        // Batch lookup slugs for all paths at once
        $slugTenantMap = [];
        if (!empty($pathsToLookup)) {
            $propertyPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/property/') !== false || strpos($p, '/ar/property/') !== false || strpos($p, '/en/property/') !== false);
            $projectPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/project/') !== false || strpos($p, '/ar/project/') !== false || strpos($p, '/en/project/') !== false);
            
            if (!empty($propertyPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($propertyPaths, 'property'));
            }
            if (!empty($projectPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($projectPaths, 'project'));
            }
        }

        // Aggregate by source with smart tenant filtering
        $sourceMap = [];

        foreach ($rowsData as $rowData) {
            $source = $rowData['source'];
            $pagePath = $rowData['pagePath'];
            $recordedTenant = $rowData['recordedTenant'];
            $sessions = $rowData['sessions'];

            // Smart tenant matching
            $belongsToTenant = false;
            if ($tenantId) {
                if (!empty($recordedTenant) && $recordedTenant === $tenantId) {
                    $belongsToTenant = true;
                } elseif (empty($recordedTenant) || $recordedTenant === '(not set)') {
                    // Use batch lookup result
                    $slug = $this->extractSlugFromPath($pagePath);
                    if ($slug && isset($slugTenantMap[strtolower($slug)])) {
                        $derivedTenant = $slugTenantMap[strtolower($slug)];
                        if ($derivedTenant === $tenantId) {
                            $belongsToTenant = true;
                        }
                    }
                }
            } else {
                $belongsToTenant = true;
            }

            if (!$belongsToTenant) {
                continue;
            }

            // Aggregate by source
            if (!isset($sourceMap[$source])) {
                $sourceMap[$source] = $sessions;
            } else {
                $sourceMap[$source] += $sessions;
            }
        }

        // Format response
        return collect($sourceMap)->map(function ($sessions, $source) {
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
        })->values();
    }

    public function getTopPages($startDate, $endDate, FilterExpression $tenantFilter)
    {
        // Extract tenant ID from the filter for backend filtering
        $tenantId = null;
        if ($tenantFilter->hasFilter()) {
            $filter = $tenantFilter->getFilter();
            if ($filter->hasStringFilter()) {
                $tenantId = $filter->getStringFilter()->getValue();
            }
        }

        // Get ALL pages data (no GA4 filter) - we'll filter on backend
        $response = $this->executeWithRetry(function() use ($startDate, $endDate) {
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
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics' => [
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'averageSessionDuration']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'totalUsers']),
                ],
                'orderBys' => [
                    new OrderBy(['metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']), 'desc' => true]),
                ],
                'limit' => 200,  // Get more to filter on backend
            ]);
        }, 'getTopPages_allData');

        // Collect all paths that need slug lookup (batch optimization)
        $pathsToLookup = [];
        $rowsData = [];
        
        foreach ($response->getRows() as $row) {
            $pagePath = $this->getSafeValue($row->getDimensionValues(), 0, '');
            $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 2, '');
            
            if ($pagePath === '') {
                continue;
            }
            
            // If tenant is empty, we'll need to look it up
            if ($tenantId && (empty($recordedTenant) || $recordedTenant === '(not set)')) {
                $pathsToLookup[] = $pagePath;
            }
            
            $rowsData[] = [
                'pagePath' => $pagePath,
                'pageTitle' => $this->getSafeValue($row->getDimensionValues(), 1, ''),
                'recordedTenant' => $recordedTenant,
                'pageViews' => (int) $this->getSafeValue($row->getMetricValues(), 0, 0),
                'avgDuration' => (float) $this->getSafeValue($row->getMetricValues(), 1, 0),
                'bounceRateRaw' => $this->getSafeValue($row->getMetricValues(), 2, 0),
                'users' => (int) $this->getSafeValue($row->getMetricValues(), 3, 0),
            ];
        }
        
        // Batch lookup slugs for all paths at once
        $slugTenantMap = [];
        if (!empty($pathsToLookup)) {
            $propertyPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/property/') !== false || strpos($p, '/ar/property/') !== false || strpos($p, '/en/property/') !== false);
            $projectPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/project/') !== false || strpos($p, '/ar/project/') !== false || strpos($p, '/en/project/') !== false);
            
            if (!empty($propertyPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($propertyPaths, 'property'));
            }
            if (!empty($projectPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($projectPaths, 'project'));
            }
        }

        // Build map with smart tenant matching (backend filtering)
        $pageMap = [];
        foreach ($rowsData as $rowData) {
            $pagePath = $rowData['pagePath'];
            $pageTitle = $rowData['pageTitle'];
            $recordedTenant = $rowData['recordedTenant'];
            $pageViews = $rowData['pageViews'];
            $avgDuration = $rowData['avgDuration'];
            $bounceRateRaw = $rowData['bounceRateRaw'];
            $users = $rowData['users'];

            // Smart tenant matching: Check if this row belongs to requested tenant
            $belongsToTenant = false;

            if ($tenantId) {
                // If tenant_id is recorded and matches, include it
                if (!empty($recordedTenant) && $recordedTenant === $tenantId) {
                    $belongsToTenant = true;
                }
                // If tenant_id is empty, use batch lookup result
                elseif (empty($recordedTenant) || $recordedTenant === '(not set)') {
                    $slug = $this->extractSlugFromPath($pagePath);
                    if ($slug && isset($slugTenantMap[strtolower($slug)])) {
                        $derivedTenant = $slugTenantMap[strtolower($slug)];
                        if ($derivedTenant === $tenantId) {
                            $belongsToTenant = true;
                        }
                    }
                }
            } else {
                // No tenant filter, include all
                $belongsToTenant = true;
            }

            if (!$belongsToTenant) {
                continue;
            }

            $bounceRate = is_numeric($bounceRateRaw) && (float)$bounceRateRaw <= 1.0
                ? round((float)$bounceRateRaw * 100, 1)
                : round((float)$bounceRateRaw, 1);

            // Aggregate by path
            if (!isset($pageMap[$pagePath])) {
                $pageMap[$pagePath] = [
                    'path' => $pagePath,
                    'title' => $pageTitle,
                    'pageViews' => $pageViews,
                    'averageSessionDuration' => $avgDuration,
                    'bounceRate' => $bounceRate,
                    'users' => $users,
                ];
            } else {
                $pageMap[$pagePath]['pageViews'] += $pageViews;
                $pageMap[$pagePath]['users'] += $users;
                // Average the duration and bounce rate
                $pageMap[$pagePath]['averageSessionDuration'] =
                    ($pageMap[$pagePath]['averageSessionDuration'] + $avgDuration) / 2;
                $pageMap[$pagePath]['bounceRate'] =
                    ($pageMap[$pagePath]['bounceRate'] + $bounceRate) / 2;
            }
        }

        // If no data found, return empty
        if (empty($pageMap)) {
            return [];
        }

        // Return top 20 by views
        return collect($pageMap)
            ->sortByDesc('pageViews')
            ->take(20)
            ->values()
            ->toArray();
    }

    public function getVisitorData(string $tenantId, Carbon $startDate, Carbon $endDate)
    {
        $propertyName = Str::startsWith($this->propertyId, 'properties/')
            ? $this->propertyId
            : "properties/{$this->propertyId}";

        // Get ALL visitor data (no GA4 filter) - we'll filter on backend to include historical data
        $response = $this->executeWithRetry(function() use ($propertyName, $startDate, $endDate) {
            return $this->client->runReport([
                'property'        => $propertyName,
                'dateRanges'      => [
                    new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ]),
                ],
                'dimensions'      => [
                    new Dimension(['name' => 'date']),
                    new Dimension(['name' => 'pagePath']),
                    new Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics'         => [
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'totalUsers']),
                ],
            ]);
        }, 'getVisitorData');

        // Collect all paths that need slug lookup (batch optimization)
        $pathsToLookup = [];
        $rowsData = [];
        
        foreach ($response->getRows() as $row) {
            $date = $this->getSafeValue($row->getDimensionValues(), 0, '');
            $pagePath = $this->getSafeValue($row->getDimensionValues(), 1, '');
            $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 2, '');
            
            if ($date === '') {
                continue;
            }
            
            // If tenant is empty, we'll need to look it up
            if ((empty($recordedTenant) || $recordedTenant === '(not set)') && !empty($pagePath)) {
                $pathsToLookup[] = $pagePath;
            }
            
            $rowsData[] = [
                'date' => $date,
                'pagePath' => $pagePath,
                'recordedTenant' => $recordedTenant,
                'sessions' => (int) $this->getSafeValue($row->getMetricValues(), 0, 0),
                'users' => (int) $this->getSafeValue($row->getMetricValues(), 1, 0),
            ];
        }
        
        // Batch lookup slugs for all paths at once
        $slugTenantMap = [];
        if (!empty($pathsToLookup)) {
            $propertyPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/property/') !== false || strpos($p, '/ar/property/') !== false || strpos($p, '/en/property/') !== false);
            $projectPaths = array_filter($pathsToLookup, fn($p) => strpos($p, '/project/') !== false || strpos($p, '/ar/project/') !== false || strpos($p, '/en/project/') !== false);
            
            if (!empty($propertyPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($propertyPaths, 'property'));
            }
            if (!empty($projectPaths)) {
                $slugTenantMap = array_merge($slugTenantMap, $this->getSlugLookupService()->getTenantsForSlugs($projectPaths, 'project'));
            }
        }

        // Build a map: date => [sessions, users] with smart tenant filtering
        $dateMap = [];

        foreach ($rowsData as $rowData) {
            $date = $rowData['date'];
            $pagePath = $rowData['pagePath'];
            $recordedTenant = $rowData['recordedTenant'];
            $sessions = $rowData['sessions'];
            $users = $rowData['users'];

            // Smart tenant matching: Check if this row belongs to requested tenant
            $belongsToTenant = false;

            // If tenant_id is recorded and matches, include it
            if (!empty($recordedTenant) && $recordedTenant === $tenantId) {
                $belongsToTenant = true;
            }
            // If tenant_id is empty, use batch lookup result
            elseif (empty($recordedTenant) || $recordedTenant === '(not set)') {
                $slug = $this->extractSlugFromPath($pagePath);
                if ($slug && isset($slugTenantMap[strtolower($slug)])) {
                    $derivedTenant = $slugTenantMap[strtolower($slug)];
                    if ($derivedTenant === $tenantId) {
                        $belongsToTenant = true;
                    }
                }
            }

            if (!$belongsToTenant) {
                continue;
            }

            // Aggregate by date
            if (!isset($dateMap[$date])) {
                $dateMap[$date] = [
                    'date' => Carbon::parse($date),
                    'sessions' => $sessions,
                    'users' => $users,
                ];
            } else {
                $dateMap[$date]['sessions'] += $sessions;
                $dateMap[$date]['users'] += $users;
            }
        }

        // Sort by date and return
        return collect($dateMap)
            ->sortBy(function($item) {
                return $item['date']->timestamp;
            })
            ->values();
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

        $map = [];

        // ===== QUERY 1: Get events WITH matching tenant_id (new data) =====
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name'    => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value'      => $tenantId,
                    'match_type' => MatchType::EXACT,
                ]),
            ]),
        ]);

        $dimensionFilter1 = new FilterExpression([
            'and_group' => new FilterExpressionList([
                'expressions' => [$tenantFilter, $pathsFilter],
            ]),
        ]);

        try {
            $response1 = $this->executeWithRetry(function() use ($startDate, $endDate, $dimensionFilter1, $paths) {
                return $this->client->runReport([
                    'property'        => $this->propertyId,
                    'dateRanges'      => [new DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date'   => $endDate->format('Y-m-d'),
                    ])],
                    'dimensions'      => [new Dimension(['name' => 'pagePath'])],
                    'metrics'         => [new Metric(['name' => 'screenPageViews'])],
                    'dimensionFilter' => $dimensionFilter1,
                    'limit'           => count($paths),
                ]);
            }, 'getPageViewsForPaths_withTenant');

            foreach ($response1->getRows() as $row) {
                $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                if ($path !== '') {
                    $map[$path] = ($map[$path] ?? 0) + $views;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Query 1 (with tenant_id) failed', ['error' => $e->getMessage()]);
        }

        // ===== QUERY 2: Get events WITHOUT tenant filter (recover old data with empty tenant_id) =====
        try {
            $response2 = $this->executeWithRetry(function() use ($startDate, $endDate, $pathsFilter) {
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
                    'dimensionFilter' => $pathsFilter,
                    'limit'           => count($paths),
                ]);
            }, 'getPageViewsForPaths_noTenant');

            foreach ($response2->getRows() as $row) {
                $path  = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);

                if ($path === '') {
                    continue;
                }

                // Only include if tenant_id was empty in GA4 (old data without tracking)
                if (empty($recordedTenant)) {
                    // Verify this path belongs to the requesting tenant by slug lookup
                    $derivedTenant = $this->deriveTenantFromPathSlug($path);

                    // Only include if slug matches requested tenant OR if we can't determine tenant
                    // (in the latter case, include it anyway as fallback for historical data)
                    if ($derivedTenant === null || $derivedTenant === $tenantId) {
                        $map[$path] = ($map[$path] ?? 0) + $views;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Query 2 (without tenant_id filter) failed', ['error' => $e->getMessage()]);
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

            // Step 2: Parse all data and normalize tenant_id
            $allData = [];
            foreach ($response->getRows() as $row) {
                $path      = $this->getSafeValue($row->getDimensionValues(), 0, '');
                $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $views     = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);

                if ($path === '') {
                    continue;
                }

                // Normalize tenant_id: use recorded value if valid, otherwise derive from path slug
                $normalizedTenant = $recordedTenant;
                if (empty($normalizedTenant) || $normalizedTenant === '(not set)') {
                    $normalizedTenant = $this->deriveTenantFromPathSlug($path);
                }

                $allData[] = [
                    'tenant_id' => $normalizedTenant ?: null,  // Always show normalized tenant_id (or null if can't derive)
                    'original_tenant_id' => $recordedTenant,    // Keep original for debugging
                    'path' => $path,
                    'views' => $views,
                    'tenant_id_source' => empty($recordedTenant) || $recordedTenant === '(not set)' ? 'derived_from_slug' : 'recorded',
                ];
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

        // Filter by tenant IDs (now includes smart fallback matching)
        if (!empty($filters['tenant_ids'])) {
            $tenantIds = is_array($filters['tenant_ids'])
                ? $filters['tenant_ids']
                : explode(',', $filters['tenant_ids']);

            $filtered = array_filter($filtered, function($item) use ($tenantIds) {
                // If tenant_id is set and matches, include it
                if (!empty($item['tenant_id']) && in_array($item['tenant_id'], $tenantIds, true)) {
                    return true;
                }

                // If tenant_id is empty/null, check if path belongs to requested tenant by slug
                // This is the FALLBACK strategy for historical data
                if (empty($item['tenant_id']) || $item['tenant_id'] === '(not set)') {
                    // Derive tenant from path slug
                    $derivedTenant = $this->deriveTenantFromPathSlug($item['path']);
                    return $derivedTenant && in_array($derivedTenant, $tenantIds, true);
                }

                return false;
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
                $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);

                if ($pageLocation === '') {
                    continue;
                }

                // Parse URL to extract domain and path
                $parsedUrl = parse_url($pageLocation);

                // Normalize tenant_id: use recorded value, or derive from URL if empty
                $derivedTenant = $recordedTenant;
                if (empty($derivedTenant)) {
                    $derivedTenant = $this->deriveTenantFromUrl($pageLocation);
                }

                // If tenant filter is active, skip rows that don't match
                if ($tenantId && $derivedTenant !== $tenantId) {
                    continue;
                }

                $locations[] = [
                    'full_url' => $pageLocation,
                    'domain' => $parsedUrl['host'] ?? '',
                    'path' => $parsedUrl['path'] ?? '/',
                    'tenant_id' => $derivedTenant,
                    'views' => $views,
                ];
                $totalViews += $views;
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
     * Derive tenant from page location URL
     * Extracts subdomain from taearif.com URLs
     * Returns null if subdomain is www, api, or not found
     */
    protected function deriveTenantFromUrl(string $fullUrl): ?string
    {
        $productionDomain = 'taearif.com';

        try {
            $parsed = parse_url($fullUrl);
            $host = $parsed['host'] ?? '';

            if (!$host) {
                return null;
            }

            // Extract subdomain from taearif.com domain
            if (strpos($host, $productionDomain) !== false) {
                $subdomain = str_replace('.' . $productionDomain, '', $host);

                // Filter out reserved subdomains
                if ($subdomain && $subdomain !== 'www' && $subdomain !== 'api' && !empty($subdomain)) {
                    return $subdomain;
                }
            }
        } catch (\Exception $e) {
            Log::warning('Failed to derive tenant from URL', [
                'url' => $fullUrl,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
    }

    /**
     * Extract slug from path
     */
    protected function extractSlugFromPath(string $path): ?string
    {
        $parts = array_filter(explode('/', trim($path, '/')));
        
        if (count($parts) < 2) {
            return null;
        }
        
        if (in_array($parts[0], ['property', 'project'])) {
            return $parts[1] ?? null;
        } elseif (count($parts) >= 3 && in_array($parts[1], ['property', 'project'])) {
            return $parts[2] ?? null;
        }
        
        return null;
    }
    
    /**
     * Derive tenant from path slug by querying the database
     * Paths like /ar/property/{slug} or /ar/project/{slug} are parsed
     * The slug is looked up in the DB to find the owning tenant (user)
     * Uses cache first, then database lookup
     *
     * @param string $path - GA4 pagePath (e.g., /ar/property/shk-llaygar-fy-sharaa-rkm-399)
     * @return string|null - tenant username or null if not found
     */
    protected function deriveTenantFromPathSlug(string $path): ?string
    {
        try {
            // Check request-level cache first
            if (isset($this->slugTenantMap[$path])) {
                return $this->slugTenantMap[$path];
            }

            // Parse path: /ar/property/{slug} or /en/project/{slug} etc.
            // Remove leading slash and split
            $parts = array_filter(explode('/', trim($path, '/')));

            if (count($parts) < 2) {
                return null;
            }

            // Detect type and slug
            // Paths can be: /property/{slug}, /ar/property/{slug}, /en/property/{slug}
            $type = null;
            $slug = null;

            if (in_array($parts[0], ['property', 'project'])) {
                // Path is /property/{slug} or /project/{slug}
                $type = $parts[0];
                $slug = $parts[1] ?? null;
            } elseif (count($parts) >= 3 && in_array($parts[1], ['property', 'project'])) {
                // Path is /ar/property/{slug} or /en/project/{slug}
                $type = $parts[1];
                $slug = $parts[2] ?? null;
            }

            if (!$type || !$slug) {
                return null;
            }

            // Check cache table first
            $cachedTenant = \App\Models\Analytics\SlugTenantCache::getTenantForSlug($slug, $type);
            if ($cachedTenant !== null) {
                $this->slugTenantMap[$path] = $cachedTenant;
                return $cachedTenant;
            }

            // Query database based on type
            $tenantId = null;
            if ($type === 'property') {
                // Look up property by slug in user_property_contents
                // Use LOWER() for case-insensitive matching to handle Arabic slugs
                $property = \DB::table('user_property_contents as upc')
                    ->join('user_properties as up', 'up.id', '=', 'upc.property_id')
                    ->join('users as u', 'u.id', '=', 'up.user_id')
                    ->whereRaw('LOWER(upc.slug) = ?', [strtolower($slug)])
                    ->select('u.username')
                    ->first();

                if ($property) {
                    $tenantId = $property->username;
                }
            } elseif ($type === 'project') {
                // Look up project by slug in user_project_contents (NOT project_contents)
                // Use LOWER() for case-insensitive matching to handle Arabic slugs
                $project = \DB::table('user_project_contents as upc')
                    ->join('user_projects as p', 'p.id', '=', 'upc.project_id')
                    ->join('users as u', 'u.id', '=', 'p.user_id')
                    ->whereRaw('LOWER(upc.slug) = ?', [strtolower($slug)])
                    ->select('u.username')
                    ->first();

                if ($project) {
                    $tenantId = $project->username;
                }
            }

            // Cache the result
            if ($tenantId) {
                \App\Models\Analytics\SlugTenantCache::cacheSlugTenant($slug, $type, $tenantId);
                $this->slugTenantMap[$path] = $tenantId;
            }

            return $tenantId;
        } catch (\Exception $e) {
            Log::warning('Failed to derive tenant from path slug', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);
        }

        return null;
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
                    new Dimension(['name' => 'pageLocation']),  // Add full URL to derive tenant
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
                $fullUrl = $this->getSafeValue($row->getDimensionValues(), 1, '');
                $recordedTenant = $this->getSafeValue($row->getDimensionValues(), 2, '');
                $views = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
                $users = (int) $this->getSafeValue($row->getMetricValues(), 1, 0);

                if ($path === '') {
                    continue;
                }

                // Normalize tenant_id: use recorded value, or derive from URL if empty
                $derivedTenant = $recordedTenant;
                if (empty($derivedTenant)) {
                    $derivedTenant = $this->deriveTenantFromUrl($fullUrl);
                }

                // If tenant filter is active, skip rows that don't match
                if ($tenantId && $derivedTenant !== $tenantId) {
                    continue;
                }

                $pages[] = [
                    'path' => $path,
                    'tenant_id' => $derivedTenant,
                    'views' => $views,
                    'users' => $users,
                ];
                $totalViews += $views;
                $totalUsers += $users;
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

