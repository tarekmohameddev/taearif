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

    // protected function getSafeValue($arr, $index, $default = null)
    // {
    //     return isset($arr[$index]) ? $arr[$index]->getValue() : $default;
    // }
    protected function getSafeValue($arr, $index, $default = null)
    {
        return ($arr && isset($arr[$index])) ? $arr[$index]->getValue() : $default;
    }


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
            \Log::info('No GA4 data returned for range: ' . $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'));
            \Log::info('GA4 response: ' . json_encode($response->serializeToJsonString()));
            \Log::info('GA4 property ID: ' . $this->propertyId);
            \Log::info('GA4 client: ' . json_encode($this->client));

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

    public function getDashboardData($tenantId, $startDate, $endDate)
    {
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                ]),
            ]),
        ]);

        return [
            'overview' => $this->getOverviewMetrics($tenantId, $startDate, $endDate, $tenantFilter),
            'devices' => $this->getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter),
            'trafficSources' => $this->getTrafficSources($tenantId, $startDate, $endDate, $tenantFilter),
            'topPages' => $this->getTopPages($tenantId, $startDate, $endDate, $tenantFilter),
        ];
    }

    protected function getOverviewMetrics($tenantId, $startDate, $endDate, $tenantFilter)
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
                new Metric(['name' => 'totalUsers']),
                new Metric(['name' => 'bounceRate']),
                new Metric(['name' => 'averageSessionDuration']),
            ],
            // 'dimensionFilter' => $tenantFilter, // add this later when tenant_id is ready
        ]);

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return [
                'sessions' => 0,
                'pageViews' => 0,
                'users' => 0,
                'bounceRate' => 0,
                'averageSessionDuration' => 0,
            ];
        }

        $metrics = $rows[0]->getMetricValues();

        return [
            'pageViews' => isset($metrics[0]) ? (int) $metrics[0]->getValue() : 0,
            'sessions' => isset($metrics[1]) ? (int) $metrics[1]->getValue() : 0,
            'users' => isset($metrics[2]) ? (int) $metrics[2]->getValue() : 0,
            'bounceRate' => isset($metrics[3]) ? (float) $metrics[3]->getValue() : 0,
            'averageSessionDuration' => isset($metrics[4]) ? (float) $metrics[4]->getValue() : 0,
        ];

    }



    protected function getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter)
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
        ]);

        return collect($response->getRows())->map(function ($row) {
            return [
                'path' => $row->getDimensionValues()[0]->getValue(),
                'deviceCategory' => $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'),

                'pageViews' => (int) $row->getMetricValues()[0]->getValue(),
                'avgDuration' => (float) $row->getMetricValues()[1]->getValue(),
                // 'bounceRate' => (float) $row->getMetricValues()[2]->getValue(),
            ];
        });

    }


    protected function getTrafficSources($tenantId, $startDate, $endDate, $tenantFilter)
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
        ]);

        return collect($response->getRows())->map(function ($row) {
            return [
                'path' => $row->getDimensionValues()[0]->getValue(),
                'deviceCategory' => $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'),


                'pageViews' => (int) $row->getMetricValues()[0]->getValue(),
                'avgDuration' => (float) $row->getMetricValues()[1]->getValue(),
                // 'bounceRate' => (float) $row->getMetricValues()[2]->getValue(),
            ];
        });

    }


    protected function getTopPages($tenantId, $startDate, $endDate, $tenantFilter)
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
                new Dimension(['name' => 'pagePath']),
                new Dimension(['name' => 'pageTitle']),
            ],
            'metrics' => [
                new Metric(['name' => 'screenPageViews']),
                new Metric(['name' => 'averageSessionDuration']),
                new Metric(['name' => 'bounceRate']),
            ],
            'orderBys' => [
                new OrderBy([
                    'metric' => new MetricOrderBy(['metric_name' => 'screenPageViews']),
                    'desc' => true,
                ]),
            ],
            'limit' => 20,
        ]);

        return collect($response->getRows())->map(function ($row) {
            return [
                'path' => $row->getDimensionValues()[0]->getValue(),
                'deviceCategory' => $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'),

                'pageViews' => (int) $row->getMetricValues()[0]->getValue(),
                'avgDuration' => (float) $row->getMetricValues()[1]->getValue(),
                // 'bounceRate' => (float) $row->getMetricValues()[2]->getValue(),
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
                    'field_name' => 'tenant_id',
                    'string_filter' => new StringFilter([
                        'value' => $tenantId,
                    ]),
                ]),
            ]);
        }

        $response = $this->client->runReport($params);

        return collect($response->getRows())->map(function ($row) {
            return [
                'event' => $row->getDimensionValues()[0]->getValue(),
                'count' => (int) $row->getMetricValues()[0]->getValue(),
            ];
        });
    }

}
