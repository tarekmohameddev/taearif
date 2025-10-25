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
use App\Models\User\RealestateManagement\Property;
use App\Models\ApiCustomer;
use Illuminate\Support\Facades\DB;

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

        $totalcustomers = ApiCustomer::where('user_id', $user->id)->count();

        $purposeCounts = DB::table('user_properties')
        ->where('user_id', $user->id)
        ->select('purpose', DB::raw('COUNT(*) as total'))
        ->groupBy('purpose')
        ->orderByDesc('total')
        ->get();

        $propertiesTotal = $purposeCounts->sum('total');


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
            'totalcustomers' =>$totalcustomers,
            'properties' => [
                'total'    => $propertiesTotal,
                'properties_purposes' => $purposeCounts,
            ],

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

    //  user customers
    public function userCustomers(Request $request)
    {
        $user = $request->user();

        // Fetch customers associated with the authenticated user
        $customers = ApiCustomer::where('user_id', $user->id)->get();

        return response()->json([
            'status' => 'success',
            'customers' => $customers
        ]);
    }

    // user properties purposes
    public function userPropertiesPurposes(Request $request)
    {
        $user = $request->user();

        // Fetch properties purposes associated with the authenticated user
        $purposes = Property::where('user_id', $user->id)
            ->distinct()
            ->pluck('purpose');

        return response()->json([
            'status' => 'success',
            'purposes' => $purposes
        ]);
    }

    /**
     * Production endpoint - Get tenant-specific analytics views
     *
     * Returns ONLY the specified tenant's paths and views (clean, production-ready)
     *
     * Usage examples:
     * - GET /api/dashboard/debug-ga-views?tenant_id=lira&days=7
     * - GET /api/dashboard/debug-ga-views?days=30
     */
    public function debugGAViews(Request $request)
    {
        // Allow custom tenant_id for testing, otherwise use authenticated user's tenant
        $tenantId = $request->input('tenant_id', $this->tenantId($request));
        $days = (int) $request->input('days', 7);

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        // Get tenant-specific views only
        $tenantViews = $this->analytics->getTenantPageViews($tenantId, $startDate, $endDate);

        return response()->json([
            'status' => 'success',
            'tenant' => $tenantId,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'paths' => $tenantViews['paths'],
            'total_views' => $tenantViews['total_views'],
            'total_paths' => $tenantViews['total_paths'],
        ]);
    }

    /**
     * Get page locations (full URLs) with views
     * 
     * Returns full URLs including domain (like Google Analytics shows)
     * 
     * Usage examples:
     * - GET /api/analytics/page-locations?days=7
     * - GET /api/analytics/page-locations?tenant_id=lira&days=30
     */
    public function getPageLocations(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $tenantId = $request->input('tenant_id', null);
        
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        $result = $this->analytics->getPageLocations($startDate, $endDate, $tenantId);

        return response()->json([
            'status' => 'success',
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'tenant_filter' => $tenantId,
            ...$result,
        ]);
    }

    /**
     * Search/Filter analytics - Get all data with backend filtering
     *
     * Returns ALL GA4 data filtered by your criteria on the backend
     *
     * Usage examples:
     * - GET /api/analytics/search?tenant_ids=lira,john&days=7
     * - GET /api/analytics/search?min_views=10&path_contains=/property/
     * - GET /api/analytics/search?tenant_ids=lira&min_views=5&limit=20
     * - GET /api/analytics/search?group_by_tenant=1
     */
    public function searchAnalytics(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        // Build filters from request
        $filters = [];

        // Tenant IDs filter
        if ($request->has('tenant_ids')) {
            $filters['tenant_ids'] = $request->input('tenant_ids');
        }

        // Views filters
        if ($request->has('min_views')) {
            $filters['min_views'] = (int) $request->input('min_views');
        }
        if ($request->has('max_views')) {
            $filters['max_views'] = (int) $request->input('max_views');
        }

        // Path filters
        if ($request->has('paths')) {
            $filters['paths'] = $request->input('paths');
        }
        if ($request->has('path_prefix')) {
            $filters['path_prefix'] = $request->input('path_prefix');
        }
        if ($request->has('path_contains')) {
            $filters['path_contains'] = $request->input('path_contains');
        }

        // Other filters
        if ($request->has('exclude_empty_tenant')) {
            $filters['exclude_empty_tenant'] = (bool) $request->input('exclude_empty_tenant');
        }
        if ($request->has('limit')) {
            $filters['limit'] = (int) $request->input('limit');
        }
        if ($request->has('group_by_tenant')) {
            $filters['group_by_tenant'] = (bool) $request->input('group_by_tenant');
        }

        // Get filtered data
        $result = $this->analytics->getAllAnalyticsWithFilters($startDate, $endDate, $filters);

        return response()->json([
            'status' => 'success',
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            ...$result,
        ]);
    }

    /**
     * Full diagnostics endpoint - For debugging GA4 issues
     *
     * Returns ALL GA4 data (all tenants, all paths) for troubleshooting
     *
     * Usage examples:
     * - GET /api/dashboard/ga-full-diagnostics?tenant_id=lira&days=7
     * - GET /api/dashboard/ga-full-diagnostics?slug=shk-hdyth-moss
     * - GET /api/dashboard/ga-full-diagnostics?paths=/property/test1,/property/test2
     */
    public function gaFullDiagnostics(Request $request)
    {
        // Allow custom tenant_id for testing, otherwise use authenticated user's tenant
        $tenantId = $request->input('tenant_id', $this->tenantId($request));
        $days = (int) $request->input('days', 30);

        // Option 1: User provides a property slug - auto-generate language variants
        $slug = $request->input('slug', '');

        // Option 2: User provides exact paths
        $pathsInput = $request->input('paths', '');

        // Generate paths based on input
        if (!empty($slug)) {
            // Auto-generate paths with language variants from slug
            $paths = [
                "/property/{$slug}",
                "/ar/property/{$slug}",
                "/en/property/{$slug}"
            ];
        } elseif (!empty($pathsInput)) {
            // Use exact paths provided by user
            $paths = array_map('trim', explode(',', $pathsInput));
        } else {
            // Default: show recent properties from database
            $recentProperties = Property::where('user_id', $request->user()->id)
                ->with('contents')
                ->orderBy('id', 'desc')
                ->limit(3)
                ->get();

            $paths = [];
            foreach ($recentProperties as $property) {
                $content = $property->contents->first();
                if ($content && $content->slug) {
                    $slug = $content->slug;
                    $paths[] = "/property/{$slug}";
                    $paths[] = "/ar/property/{$slug}";
                    $paths[] = "/en/property/{$slug}";
                }
            }

            // If no properties found, use a sample
            if (empty($paths)) {
                $paths = ['/property/sample-slug'];
            }
        }

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        $debugResults = $this->analytics->debugPageViews(
            $tenantId,
            $startDate,
            $endDate,
            $paths
        );

        // Full diagnostic mode - show everything
        return response()->json([
            'status' => 'success',
            'mode' => 'full_diagnostic',
            'tenant' => $tenantId,
            'date_range' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'days' => $days,
            ],
            'paths_tested' => $paths,
            'debug_results' => $debugResults,
            'usage_examples' => [
                'Full diagnostics with slug' => '/api/dashboard/ga-full-diagnostics?tenant_id=lira&slug=shk-hdyth-moss',
                'Test specific paths' => '/api/dashboard/ga-full-diagnostics?paths=/property/test1,/ar/property/test2',
                'Custom tenant and date range' => '/api/dashboard/ga-full-diagnostics?tenant_id=lira&days=7',
            ],
            'instructions' => [
                'all_paths' => 'All page views in GA4 (no filters) - if empty, GA4 has no data at all',
                'tenant_filtered_paths' => 'Page views filtered by tenant_id - if empty, tenant_id parameter is not being sent',
                'specific_paths_no_tenant_filter' => 'Your specific paths without tenant filter - checks if paths exist in GA4',
                'tenant_ids_found' => 'All tenant_ids found in GA4 - helps verify parameter name and values',
            ],
            'diagnosis' => [
                'If all_paths is empty' => 'GA4 is not receiving any data. Check GA4 setup and tracking code.',
                'If tenant_filtered_paths is empty but all_paths has data' => 'The tenant_id custom parameter is not being sent correctly from your frontend.',
                'If specific_paths_no_tenant_filter has data but tenant_filtered_paths is empty' => 'Paths exist but tenant_id is missing or incorrect.',
                'If everything is empty' => 'Wait 24-48 hours for GA4 to process data, or check if GA4 property ID is correct.',
            ]
        ]);
    }

}
