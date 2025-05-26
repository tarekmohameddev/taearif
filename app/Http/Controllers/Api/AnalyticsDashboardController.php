<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Services\GoogleAnalyticsService;
use Google\Analytics\Data\V1beta\FilterExpression;
use Google\Analytics\Data\V1beta\Filter\StringFilter;
use Google\Analytics\Data\V1beta\Filter;
use Illuminate\Support\Facades\Log;


class AnalyticsDashboardController extends Controller
{

    public function __construct(protected GoogleAnalyticsService $analytics) {}

    protected function tenantId(Request $request): string
    {
        return $request->user()->username;
    }

    protected function parseRange(Request $req, int $default = 7): array
    {
        $days = (int) $req->input('time_range', $default);
        return [ Carbon::now()->subDays($days), Carbon::now() ];
    }

    public function dashboard(Request $request)
    {
        $tenant = $this->tenantId($request);
        [$start, $end] = $this->parseRange($request, 7);
        $data = $this->analytics->getDashboardData($tenant, $start, $end);

        return response()->json([
            'status'     => 'success',
            'tenant'     => $tenant,
            'start_date' => $start->toDateString(),
            'end_date'   => $end->toDateString(),
            'data'       => $data,
        ]);
    }

    public function visitors(Request $request, GoogleAnalyticsService $analytics)
    {
        // Get the user and tenant ID
        $tenantId = $this->tenantId($request);


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



    public function devices(Request $request, GoogleAnalyticsService $analytics)
    {
        $tenantId = $this->tenantId($request);

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

    public function trafficSources(Request $request, GoogleAnalyticsService $analytics)
    {
        $tenantId = $this->tenantId($request);
        // $tenantId = 'ress';

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $tenantFilter = new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter([
                    'value' => $tenantId,
                    'match_type' => StringFilter\MatchType::CONTAINS,
                ]),
            ]),
        ]);

        $sources = $analytics->getTrafficSources($startDate, $endDate, $tenantFilter);

        return response()->json(['sources' => $sources]);
    }

    public function mostVisitedPages(Request $request, GoogleAnalyticsService $analytics)
    {
        $tenantId = $this->tenantId($request);

        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        $pages = $analytics->getDashboardData($tenantId, $startDate, $endDate)['topPages'];

        $totalViews = collect($pages)->sum('pageViews');

        $formattedPages = collect($pages)->map(function ($page) use ($totalViews) {
            Log::info('Page Data: ' . json_encode($page));

            $percentage = $totalViews > 0 ? round(($page['pageViews'] / $totalViews) * 100, 2) : 0;

            $avgTime = isset($page['averageSessionDuration']) ? $this->formatDuration($page['averageSessionDuration']) : 'N/A';

            $uniqueVisitors = isset($page['users']) ? $page['users'] : 0;

            $bounceRate = isset($page['bounceRate']) ? $page['bounceRate'] : 0.0;

            if (is_numeric($bounceRate)) {
                $bounceRate = (float)$bounceRate;
                $bounceRateFormatted = $bounceRate <= 1.0
                    ? round($bounceRate * 100, 1)
                    : round($bounceRate, 1);
            } else {
                $bounceRateFormatted = 0.0;
            }

            return [
                'path' => $page['path'],
                'views' => $page['pageViews'],
                'unique_visitors' => $uniqueVisitors,
                'bounce_rate' => (float) $bounceRateFormatted,
                'avg_time' => $avgTime,
                'percentage' => $percentage,
            ];
        });

        return response()->json(['pages' => $formattedPages]);
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

    protected function translateSourceName($sourceName)
    {
        $translations = [
            '(direct)' => 'الروابط المباشرة',
            '(none)' => 'غير معرف',
            'google' => 'البحث العضوي',
            'social' => 'وسائل التواصل الاجتماعي',
            'ads' => 'الإعلانات',
            'other' => 'أخرى',
        ];

        return $translations[$sourceName] ?? $sourceName;
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
