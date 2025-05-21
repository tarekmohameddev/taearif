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

    // Helper for safe dimension/metric value access
    protected function getSafeValue($arr, $index, $default = null)
    {
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
            'devices' => $this->getDeviceBreakdown($startDate, $endDate, $tenantFilter),
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

    protected function getDeviceBreakdown($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->client->runReport([
            'property' => $this->propertyId,
            'dateRanges' => [new DateRange(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')])],
            'dimensions' => [new Dimension(['name' => 'deviceCategory'])],
            'metrics' => [new Metric(['name' => 'sessions']), new Metric(['name' => 'screenPageViews'])],
            'dimensionFilter' => $tenantFilter,  // <-- FILTER APPLIED HERE
        ]);

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return [];
        }

        return collect($rows)->map(function ($row) {
            return [
                'deviceCategory' => $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'),
                'sessions' => (int)$this->getSafeValue($row->getMetricValues(), 0, 0),
                'pageViews' => (int)$this->getSafeValue($row->getMetricValues(), 1, 0),
            ];
        })->toArray();
    }

    protected function getTrafficSources($startDate, $endDate, FilterExpression $tenantFilter)
    {
        $response = $this->client->runReport([
            'property' => $this->propertyId,
            'dateRanges' => [new DateRange(['start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')])],
            'dimensions' => [new Dimension(['name' => 'sessionSource']), new Dimension(['name' => 'sessionMedium'])],
            'metrics' => [new Metric(['name' => 'sessions']), new Metric(['name' => 'totalUsers'])],
            'dimensionFilter' => $tenantFilter,  // <-- FILTER APPLIED HERE
        ]);

        $rows = $response->getRows();

        if (count($rows) === 0) {
            return [];
        }

        return collect($rows)->map(function ($row) {
            return [
                'source' => $this->getSafeValue($row->getDimensionValues(), 0, 'unknown'),
                'medium' => $this->getSafeValue($row->getDimensionValues(), 1, 'unknown'),
                'sessions' => (int)$this->getSafeValue($row->getMetricValues(), 0, 0),
                'users' => (int)$this->getSafeValue($row->getMetricValues(), 1, 0),
            ];
        })->toArray();
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
}
