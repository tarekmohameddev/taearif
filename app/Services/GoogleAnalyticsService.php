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

class GoogleAnalyticsService
{
    protected $client;
    protected $propertyId;

    public function __construct()
    {
        $this->client = new BetaAnalyticsDataClient([
            'credentials' => json_decode(file_get_contents(storage_path('app/google/service-account.json')), true),
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

        return $translations[$sourceName] ?? $sourceName;  // Default to sourceName if no translation found
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
                new Dimension(['name' => 'eventName']), // Dimension for event name
            ],
            'metrics' => [
                new Metric(['name' => 'eventCount']), // Metric for event count
            ],
            'orderBys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                    'desc' => true,
                ]),
            ],
            'limit' => 10,
        ];

        // Optional: Apply tenant filter if tenantId is provided
        if ($tenantId) {
            $params['dimensionFilter'] = new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'customEvent:tenant_id',
                    'string_filter' => new StringFilter([
                        'value' => $tenantId,
                    ]),
                ]),
            ]);
        }

        // Run the report using the Google Analytics client
        $response = $this->client->runReport($params);

        // Map the response to return event data
        return collect($response->getRows())->map(function ($row) {
            return [
                'event' => $row->getDimensionValues()[0]->getValue(),
                'count' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        });
    }

    // Helper for safe dimension/metric value access
    protected function getSafeValue($arr, $index, $default = null)
    {
        // Safely return the dimension value, or the default if not found
        return ($arr && isset($arr[$index])) ? $arr[$index]->getValue() : $default;
    }



    // Your existing simple visitors/page views without tenant filter
    public function getVisitorsAndPageViews($startDate, $endDate)
    {
        $response = $this->client->runReport([
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

        $rows = $response->getRows();

        if (count($rows) === 0) {
            Log::info('No GA4 data returned for range: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
            Log::info('GA4 response: ' . json_encode($response->serializeToJsonString()));
            Log::info('GA4 property ID: ' . $this->propertyId);

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
        // Translate device names to Arabic
        $translations = [
            'mobile' => 'الهاتف المحمول',
            'desktop' => 'الحاسوب',
            'tablet' => 'الجهاز اللوحي',
            'other' => 'أخرى',
        ];

        return $translations[$deviceCategory] ?? $deviceCategory; // Default to deviceCategory if no translation found
    }

    public function getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter)
    {
        $response = $this->client->runReport([
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
            'dimensionFilter' => $tenantFilter, // <-- Apply the tenant filter here
        ]);

        return collect($response->getRows())->map(function ($row) {
            $deviceCategory = isset($row->getDimensionValues()[0]) ? $row->getDimensionValues()[0]->getValue() : 'Unknown Device';
            $sessions = isset($row->getMetricValues()[0]) ? (int) $row->getMetricValues()[0]->getValue() : 0;
            $pageViews = isset($row->getMetricValues()[1]) ? (int) $row->getMetricValues()[1]->getValue() : 0;

            // Assign color based on device category
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
    // === MAIN FUNCTION: Pass tenantId filter to each query ===
    public function getDashboardData($tenantId, $startDate, $endDate)
    {
        // IMPORTANT: use customEvent:tenant_id here for filter!
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                    'match_type' => MatchType::CONTAINS,  // <-- specify contains match
                ]),
            ]),
        ]);

        Log::info('tenantFilter: ' . $tenantFilter->serializeToJsonString());

        return [
            'overview' => $this->getOverviewMetrics($startDate, $endDate, $tenantFilter),
            'devices' => $this->getDeviceBreakdown($tenantId,$startDate, $endDate, $tenantFilter),
            'trafficSources' => $this->getTrafficSources($startDate, $endDate, $tenantFilter),
            'topPages' => $this->getTopPages($startDate, $endDate, $tenantFilter),
        ];
    }

    // === Add tenant filter to all runReport calls below ===

    protected function getOverviewMetrics($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->client->runReport([
            'property' => $this->propertyId,
            'dateRanges' => [new DateRange(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')])],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
            ],
            'dimensionFilter' => $tenantFilter,  // <-- FILTER APPLIED HERE
        ]);

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return ['pageViews'=>0, 'sessions'=>0, 'users'=>0, 'bounceRate'=>0, 'averageSessionDuration'=>0];
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
        $response = $this->client->runReport([
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
            'dimensionFilter' => $tenantFilter, // <-- Apply the tenant filter here
        ]);

        // Log the raw response for debugging
        Log::info('GA Response: ' . json_encode($response->serializeToJsonString()));

        return collect($response->getRows())->map(function ($row) {
            // Debugging the row data
            Log::info('Row Data: ' . json_encode($row->getDimensionValues()));

            // Extract session source and medium values safely
            $source = $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'); // Session source (google, direct, etc.)
            $medium = $this->getSafeValue($row->getDimensionValues(), 1, 'unknown'); // Session medium (organic, paid, etc.)

            $sessions = (int) $this->getSafeValue($row->getMetricValues(), 0, 0);
            $users = (int) $this->getSafeValue($row->getMetricValues(), 1, 0);

            // Color-coding for sources
            $color = match ($source) {
                '(direct)' => '#34A853', // Direct traffic
                '(none)' => '#F4B400', // No medium, could be used for organic, or undefined sources
                'google' => '#4285F4', // Google search
                'social' => '#A142F4', // Social media
                'ads' => '#F4B400', // Ads
                default => '#6B7280', // Unknown source
            };

            // Log the source and medium values to ensure they are being retrieved correctly
            Log::info('Source: ' . $source . ' Medium: ' . $medium);

            return [
                'name' => $this->translateSourceName($source),  // Translate source name to Arabic
                'value' => $sessions,
                'color' => $color,
            ];
        });
    }



    protected function getTopPages($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->client->runReport([
            'property' => $this->propertyId,
            'dateRanges' => [new DateRange(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')])],
            'dimensions' => [new Dimension(['name' => 'pagePath']), new Dimension(['name' => 'pageTitle'])],
            'metrics' => [new Metric(['name' => 'screenPageViews']), new Metric(['name' => 'averageSessionDuration']), new Metric(['name' => 'bounceRate'])],
            'dimensionFilter' => $tenantFilter,  // <-- FILTER APPLIED HERE
            'orderBys' => [
                new OrderBy(['metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']), 'desc' => true]),
            ],
            'limit' => 20,
        ]);

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return [];
        }

        return collect($rows)->map(function ($row) {
            return [
                'path' => $this->getSafeValue($row->getDimensionValues(), 0, 'N/A'),
                'title' => $this->getSafeValue($row->getDimensionValues(), 1, 'N/A'),
                'pageViews' => (int)$this->getSafeValue($row->getMetricValues(), 0, 0),
                'avgDuration' => (float)$this->getSafeValue($row->getMetricValues(), 1, 0),
                'bounceRate' => (float)$this->getSafeValue($row->getMetricValues(), 2, 0),
            ];
        })->toArray();
    }

    public function getVisitorData($tenantId, $startDate, $endDate)
    {
        $response = $this->client->runReport([
            'property' => $this->propertyId,
            'dateRanges' => [
                new DateRange([
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                ]),
            ],
            'dimensions' => [
                new Dimension(['name' => 'date']),  // The dimension is "date"
            ],
            'metrics' => [
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'totalUsers']),
            ],
            // Optional: Add filter for tenant if you need it
            // 'dimensionFilter' => $tenantFilter,
        ]);

        return collect($response->getRows())->map(function ($row) {
            return [
                'date' => Carbon::parse($row->getDimensionValues()[0]->getValue()), // Date value (string to Carbon)
                'sessions' => (int) $row->getMetricValues()[0]->getValue(), // Sessions (visits)
                'users' => (int) $row->getMetricValues()[1]->getValue(), // Users (unique visitors)
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
                new Dimension(['name' => 'eventName']), // Event name dimension
            ],
            'metrics' => [
                new Metric(['name' => 'eventCount']), // Event count metric
            ],
            'orderBys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
                    'desc' => true,
                ]),
            ],
            'limit' => 10,
        ];

        // Optional: Apply tenant filter if tenantId is provided
        if ($tenantId) {
            $params['dimensionFilter'] = new FilterExpression([
                'filter' => new Filter([
                    'field_name' => 'customEvent:tenant_id',
                    'string_filter' => new StringFilter([
                        'value' => $tenantId,
                    ]),
                ]),
            ]);
        }

        // Run the report using the Google Analytics client
        $response = $this->client->runReport($params);

        // Map the response to return event data
        return collect($response->getRows())->map(function ($row) {
            return [
                'event' => $row->getDimensionValues()[0]->getValue(),
                'count' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        });
    }


}
