<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter;


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
        // $tenantId = 'ress';
        $tenantId = auth()->user()->username;

        $analyticsData = $analyticsService->getDashboardData($tenantId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'tenant' => $tenantId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'data' => $analyticsData,
        ]);
    }

    protected function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        $seconds = floor($seconds % 60);
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function summary(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        // Current period (last 7 days)
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Previous period (last 14 days to 7 days ago)
        $previousStartDate = Carbon::now()->subDays(14);
        $previousEndDate = Carbon::now()->subDays(7);

        // Get current and previous overview data
        $overview = $analytics->getDashboardData($tenantId, $startDate, $endDate)['overview'];
        $previousOverview = $analytics->getDashboardData($tenantId, $previousStartDate, $previousEndDate)['overview'];

        // Calculate changes
        $visitsChange = $overview['sessions'] - $previousOverview['sessions'];
        $pageViewsChange = $overview['pageViews'] - $previousOverview['pageViews'];
        $bounceRateChange = $overview['bounceRate'] - $previousOverview['bounceRate'];

        // Format average session time
        $formattedAverageTime = $this->formatDuration($overview['averageSessionDuration']);

        return response()->json([
            'status' => 'success',
            'visits' => $overview['sessions'],
            'visits_change' => $visitsChange,
            'page_views' => $overview['pageViews'],
            'page_views_change' => $pageViewsChange,
            'average_time' => $formattedAverageTime,
            'average_time_change' => 0,  // Add logic here if you want to compare average time between periods
            'bounce_rate' => $overview['bounceRate'],
            'bounce_rate_change' => $bounceRateChange,
        ]);
    }

public function visitors(Request $request, GoogleAnalyticsService $analytics)
{
    // Get the user and tenant ID
    $user = $request->user();
    $tenantId = $user->username;

    // Retrieve the time range from the request (default to 30 days if not provided)
    $timeRange = $request->input('time_range', 30);

    // Calculate the start and end dates based on the time_range
    $endDate = Carbon::now();

    // Calculate the start date based on the time_range
    switch ($timeRange) {
        case 7:
            $startDate = $endDate->copy()->subDays(7); // Last 7 days
            break;
        case 30:
            $startDate = $endDate->copy()->subDays(30); // Last 30 days
            break;
        case 90:
            $startDate = $endDate->copy()->subMonths(3); // Last 3 months
            break;
        case 365:
            $startDate = $endDate->copy()->subYear(); // Last 1 year
            break;
        default:
            $startDate = $endDate->copy()->subDays(30); // Default to last 30 days if invalid input
            break;
    }

    // Fetch the visitor data using the dynamic date range
    $visitorData = $analytics->getVisitorData($tenantId, $startDate, $endDate); // Custom method for time series data

    // Format the visitor data
    $visitorDataFormatted = collect($visitorData)->map(function ($item) {
        return [
            'date' => $item['date']->locale('ar')->isoFormat('D MMMM'), // Convert to Arabic date (e.g., '1 يناير')
            'visits' => $item['sessions'],
            'uniqueVisitors' => $item['users']
        ];
    });

    // Calculate total visits and total unique visitors
    $totalVisits = collect($visitorData)->sum('sessions');
    $totalUniqueVisitors = collect($visitorData)->sum('users');

    // Return the response with the dynamic time range
    return response()->json([
        'visitor_data' => $visitorDataFormatted,
        'total_visits' => $totalVisits,
        'total_unique_visitors' => $totalUniqueVisitors,
    ]);
}
    // public function visitors(Request $request)
    // {
    //     return response()->json([
    //         'visitor_data' => [
    //             ['date' => '1 يناير', 'visits' => 450, 'uniqueVisitors' => 320],
    //             ['date' => '5 يناير', 'visits' => 580, 'uniqueVisitors' => 420],
    //             ['date' => '10 يناير', 'visits' => 540, 'uniqueVisitors' => 380],
    //             ['date' => '15 يناير', 'visits' => 750, 'uniqueVisitors' => 560],
    //             ['date' => '20 يناير', 'visits' => 800, 'uniqueVisitors' => 600],
    //             ['date' => '25 يناير', 'visits' => 920, 'uniqueVisitors' => 680],
    //             ['date' => '30 يناير', 'visits' => 1150, 'uniqueVisitors' => 850],
    //         ],
    //         'total_visits' => 5190,
    //         'total_unique_visitors' => 3810,
    //     ]);
    // }

    public function devices(Request $request, GoogleAnalyticsService $analytics)
{
    $user = $request->user();
    $tenantId = $user->username;

    $startDate = Carbon::now()->subDays(7);
    $endDate = Carbon::now();

    // Build the tenant filter
    $tenantFilter = new FilterExpression([
        'filter' => new Filter([
            'field_name' => 'customEvent:tenant_id',
            'string_filter' => new StringFilter([
                'value' => $tenantId,
            ]),
        ]),
    ]);

    // Now pass the tenantFilter as the 4th argument
    $devices = $analytics->getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter);

    return response()->json(['devices' => $devices]);
}


    protected function translateDeviceName($deviceName)
    {
        $translations = [
            'mobile' => 'الهاتف المحمول',
            'desktop' => 'الحاسوب',
            'tablet' => 'الجهاز اللوحي',
            'other' => 'أخرى',
        ];

        return $translations[$deviceName] ?? $deviceName;
    }

    public function trafficSources(Request $request, GoogleAnalyticsService $analytics)
    {
        $user = $request->user();
        $tenantId = $user->username;

        // Set the start and end date for the last 7 days (or any time range you want)
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Build the tenant filter (filter by tenant_id)
        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                    'match_type' => StringFilter\MatchType::CONTAINS,  // <-- specify contains match
                ]),
            ]),
        ]);

        // Pass the tenantFilter as the 4th argument to getDeviceBreakdown()
        $sources = $analytics->getTrafficSources($startDate, $endDate, $tenantFilter);

        return response()->json(['sources' => $sources]);
    }


protected function translateSourceName($sourceName)
{
    $translations = [
        'google' => 'البحث العضوي',  // 'google' becomes 'البحث العضوي' (organic search)
        'direct' => 'الروابط المباشرة',  // 'direct' becomes 'الروابط المباشرة' (direct)
        'social' => 'وسائل التواصل',  // 'social' becomes 'وسائل التواصل' (social media)
        'ads' => 'الإعلانات',  // 'ads' becomes 'الإعلانات' (ads)
        'other' => 'أخرى',  // 'other' becomes 'أخرى' (other)
    ];

    return $translations[$sourceName] ?? $sourceName;  // Default to sourceName if no translation found
}

public function mostVisitedPages(Request $request, GoogleAnalyticsService $analytics)
{
    $user = $request->user();
    $tenantId = $user->username;

    $startDate = Carbon::now()->subDays(7);
    $endDate = Carbon::now();

    // Fetch the top pages data from Google Analytics
    $pages = $analytics->getDashboardData($tenantId, $startDate, $endDate)['topPages'];

    // Format the pages data to match your required structure
    $totalViews = collect($pages)->sum('pageViews'); // Calculate total views for percentage calculation

    $formattedPages = collect($pages)->map(function ($page) use ($totalViews) {
        // Calculate the percentage of total views
        $percentage = $totalViews > 0 ? round(($page['pageViews'] / $totalViews) * 100, 2) : 0;

        // Safely get average session duration, if it exists
        $avgTime = isset($page['averageSessionDuration']) ? $this->formatDuration($page['averageSessionDuration']) : 'N/A';

        // Safely get unique visitors (users), defaulting to 0 if not available
        $uniqueVisitors = isset($page['users']) ? $page['users'] : 0;

        // Safely get bounce rate, defaulting to 0 if not available
        $bounceRate = isset($page['bounceRate']) ? (float) $page['bounceRate'] : 0;

        // Apply the .toFixed() if the bounceRate is a number
        $bounceRateFormatted = is_numeric($bounceRate) ? number_format($bounceRate, 1) : 0;

        return [
            'path' => $page['path'],
            'views' => $page['pageViews'],
            'unique_visitors' => $uniqueVisitors, // Use the safe value for unique visitors
            'bounce_rate' => $bounceRateFormatted, // Format bounce rate
            'avg_time' => $avgTime,
            'percentage' => $percentage,
        ];
    });

    return response()->json(['pages' => $formattedPages]);
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

    // Get the recent events data from Google Analytics
    $events = $analytics->getRecentEvents($startDate, $endDate, $tenantId);

    // Map over the events to match your desired format
    $activities = collect($events)->map(function ($event, $key) {
        // Simulate the time calculation (this needs to be replaced with your actual logic)
        // You can use event['created_at'] for actual date difference calculations
        $created_at = Carbon::parse($event['created_at'] ?? now());
        $time = $created_at->diffForHumans();

        // Ensure the 'users' key exists, otherwise, use a default value of 0
        $uniqueVisitors = $event['users'] ?? 0;

        return [
            'id' => $event['id'] ?? $key + 1, // Default ID if not present
            'action' => $event['action'] ?? 'No Action', // Default action if not present
            'section' => $event['section'] ?? 'No Section', // Default section if not present
            'time' => $time,
            'icon' => $event['icon'] ?? 'file-text', // Default icon if not present
            'user_id' => $event['user_id'] ?? 1, // Default user_id if not present
            'created_at' => $event['created_at'] ?? Carbon::now()->toISOString(), // Default created_at if not present
        ];
    });

    return response()->json([
        'activities' => $activities
    ]);
}





}
