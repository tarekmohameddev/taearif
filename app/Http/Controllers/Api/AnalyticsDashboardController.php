<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;


class AnalyticsDashboardController extends Controller
{
    public function dashboard(Request $request, GoogleAnalyticsService $analyticsService)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // $tenantId = $user->username ?? explode('.', request()->getHost())[0];
        $tenantId = $user->username;

        $analyticsData = $analyticsService->getDashboardData($tenantId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'tenant' => $tenantId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $analyticsData,
        ]);
    }

    public function summary(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $overview = $analytics->getDashboardData($tenantId, $startDate, $endDate)['overview'];

        return response()->json([
            'visits' => $overview['sessions'],
            'visits_change' => 0, // Placeholder for future comparison
            'page_views' => $overview['pageViews'],
            'page_views_change' => 0,
            'average_time' => number_format($overview['averageSessionDuration'], 1),
            'average_time_change' => 0,
            'bounce_rate' => $overview['bounceRate'],
            'bounce_rate_change' => 0,
        ]);
    }

    public function visitors(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $overview = $analytics->getDashboardData($tenantId, $startDate, $endDate)['overview'];

        return response()->json([
            'visitor_data' => [], // Optionally implement time series sessions here
            'total_visits' => $overview['sessions'],
            'total_unique_visitors' => $overview['users'],
        ]);
    }

    public function devices(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $devices = $analytics->getDashboardData($tenantId, $startDate, $endDate)['devices'];

        return response()->json(['devices' => $devices]);
    }

    public function trafficSources(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $sources = $analytics->getDashboardData($tenantId, $startDate, $endDate)['trafficSources'];

        return response()->json(['sources' => $sources]);
    }

    public function mostVisitedPages(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $pages = $analytics->getDashboardData($tenantId, $startDate, $endDate)['topPages'];

        return response()->json(['pages' => $pages]);
    }

    public function setupProgress()
    {
        return response()->json([
            'progress_percentage' => 60,
            'completed_steps' => [
                ['id' => 1, 'name' => 'إنشاء الموقع', 'completed' => true],
                ['id' => 2, 'name' => 'اختيار القالب', 'completed' => true],
                ['id' => 3, 'name' => 'تخصيص الشعار', 'completed' => true],
                ['id' => 4, 'name' => 'إضافة المحتوى', 'completed' => false],
                ['id' => 5, 'name' => 'ربط المجال', 'completed' => false],
            ]
        ]);
    }

    // public function getRecentEvents($startDate, $endDate, $tenantId = null)
    // {
    //     $params = [
    //         'property' => $this->propertyId,
    //         'dateRanges' => [
    //             new DateRange([
    //                 'start_date' => $startDate->format('Y-m-d'),
    //                 'end_date' => $endDate->format('Y-m-d'),
    //             ]),
    //         ],
    //         'dimensions' => [
    //             new Dimension(['name' => 'eventName']),
    //         ],
    //         'metrics' => [
    //             new Metric(['name' => 'eventCount']),
    //         ],
    //         'orderBys' => [
    //             new OrderBy([
    //                 'metric' => new MetricOrderBy(['metric_name' => 'eventCount']),
    //                 'desc' => true,
    //             ]),
    //         ],
    //         'limit' => 10,
    //     ];

    //     if ($tenantId) {
    //         $params['dimensionFilter'] = new FilterExpression([
    //             'filter' => new Filter([
    //                 'field_name' => 'tenant_id',
    //                 'string_filter' => new StringFilter([
    //                     'value' => $tenantId,
    //                 ]),
    //             ]),
    //         ]);
    //     }

    //     $response = $this->client->runReport($params);

    //     return collect($response->getRows())->map(function ($row) {
    //         return [
    //             'event' => $row->getDimensionValues()[0]->getValue(),
    //             'count' => (int) $row->getMetricValues()[0]->getValue(),
    //         ];
    //     });
    // }


    public function getRecentActivity(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $events = $analytics->getRecentEvents($startDate, $endDate, $tenantId);

        return response()->json([
            'activities' => $events,
        ]);
    }

}
