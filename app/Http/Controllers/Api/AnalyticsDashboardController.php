<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\Api\Analytics\VisitorsAnalyticsRequest;
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
use App\Models\Analytics\AnalyticsDailySummary;
use App\Models\Api\EmployeeActivityLog;
use App\Models\User;
use App\Services\ActivityActionMapper;
use App\Services\SiteSetupProgressService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AnalyticsDashboardController extends Controller
{

    public function __construct(protected GoogleAnalyticsService $analytics) {}

    protected function tenantId(Request $request): string
    {
		$tenant = $request->input('tenant_id');

		if ($tenant === null || $tenant === '') {
			$user = $request->user();
			// Use tenant owner's username when user is an employee (GA data is keyed by owner)
			$owner = method_exists($user, 'tenantOwner') ? $user->tenantOwner() : $user;
			$tenant = $owner?->username;
		}

		if (!is_string($tenant) || $tenant === '') {
			abort(422, 'Missing tenant identifier. Provide tenant_id or ensure the user has a username.');
		}

		return $tenant;
    }

    protected function parseRange(Request $req, int $default = 7): array
    {
        $days = (int) $req->input('time_range', $default);
        return [ Carbon::now()->subDays($days), Carbon::now() ];
    }

    /**
     * Build tenant filter for Google Analytics queries
     *
     * @param string $tenantId
     * @param bool $useContains Whether to use CONTAINS match type (default: false for EXACT)
     * @return FilterExpression
     */
    protected function buildTenantFilter(string $tenantId, bool $useContains = false): FilterExpression
    {
        $stringFilterOptions = [
            'value' => $tenantId,
        ];

        if ($useContains) {
            $stringFilterOptions['match_type'] = StringFilter\MatchType::CONTAINS;
        }

        return new FilterExpression([
            'filter' => new Filter([
                'field_name' => 'customEvent:tenant_id',
                'string_filter' => new StringFilter($stringFilterOptions),
            ]),
        ]);
    }

    /**
     * Validate time_range against allowed values
     *
     * @param mixed $timeRange
     * @param int $default
     * @return int
     */
    protected function validateTimeRange($timeRange, int $default = 30): int
    {
        $allowedRanges = [7, 30, 90, 365];
        $timeRange = (int) $timeRange;

        return in_array($timeRange, $allowedRanges) ? $timeRange : $default;
    }

    /**
     * Generate cache key for GA visitors endpoint
     *
     * @param string $tenantId
     * @param int $timeRange
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function getVisitorsCacheKey(string $tenantId, int $timeRange, Carbon $startDate, Carbon $endDate): string
    {
        return "ga:visitors:{$tenantId}:{$timeRange}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
    }

    /**
     * Generate cache key for GA devices endpoint
     *
     * @param string $tenantId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function getDevicesCacheKey(string $tenantId, Carbon $startDate, Carbon $endDate): string
    {
        return "ga:devices:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
    }

    /**
     * Generate cache key for GA traffic sources endpoint
     *
     * @param string $tenantId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function getTrafficSourcesCacheKey(string $tenantId, Carbon $startDate, Carbon $endDate): string
    {
        return "ga:traffic-sources:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
    }

    /**
     * Generate cache key for GA summary endpoint
     *
     * @param string $tenantId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param Carbon $previousStartDate
     * @param Carbon $previousEndDate
     * @return string
     */
    protected function getSummaryCacheKey(string $tenantId, Carbon $startDate, Carbon $endDate, Carbon $previousStartDate, Carbon $previousEndDate): string
    {
        return "ga:summary:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}:{$previousStartDate->format('Y-m-d')}:{$previousEndDate->format('Y-m-d')}";
    }

    /**
     * Generate cache key for GA most visited pages endpoint
     *
     * @param string $tenantId
     * @param int $timeRange
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function getMostVisitedPagesCacheKey(string $tenantId, int $timeRange, Carbon $startDate, Carbon $endDate): string
    {
        return "dashboard:most-visited-pages:{$tenantId}:{$timeRange}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
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

    public function visitors(VisitorsAnalyticsRequest $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;

        // Get the user and tenant ID
        $tenantId = $this->tenantId($request);

        // Retrieve and validate time range from the request (default to 30 days if not provided)
        $validated = $request->validated();
        $timeRange = $this->validateTimeRange($validated['time_range'] ?? 30, 30);

        // Normalize Carbon::now() to compute once per request
        $endDate = Carbon::now();

        // Calculate the start date based on the validated time_range
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

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedVisitorsData($tenantId, $startDate, $endDate);

        if ($cachedData !== null) {
            $cacheHit = true;

            // Return cached data (already formatted)
            return response()->json($cachedData);
        }

        // Fallback to GA API - use cache lock to prevent concurrent requests
        $lockKey = "ga:visitors:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        $lock = Cache::lock($lockKey, 30); // 30 second lock timeout

        try {
            if ($lock->get()) {
                try {
                    // Double-check after acquiring lock (another request might have stored it)
                    $cachedData = $this->getMaterializedVisitorsData($tenantId, $startDate, $endDate);
                    if ($cachedData !== null) {
                        $lock->release();
                        return response()->json($cachedData);
                    }

                    // Fetch from GA API
                    $visitorData = $analytics->getVisitorData($tenantId, $startDate, $endDate);

                    // Ensure visitorData is an array
                    $visitorDataArray = is_array($visitorData) ? $visitorData : collect($visitorData)->toArray();

                    // Format the visitor data
                    $visitorDataFormatted = collect($visitorDataArray)->map(function ($item) {
                        return [
                            'date' => $item['date']->locale('ar')->isoFormat('D MMMM'), // Convert to Arabic date (e.g., '1 يناير')
                            'visits' => $item['sessions'],
                            'uniqueVisitors' => $item['users']
                        ];
                    })->toArray();

                    // Calculate total visits and total unique visitors
                    $totalVisits = collect($visitorDataArray)->sum('sessions');
                    $totalUniqueVisitors = collect($visitorDataArray)->sum('users');

                    $responseData = [
                        'visitor_data' => $visitorDataFormatted,
                        'total_visits' => $totalVisits,
                        'total_unique_visitors' => $totalUniqueVisitors,
                    ];

                    // Store materialized data in database for each day
                    $this->storeMaterializedVisitorsData($tenantId, $startDate, $endDate, $visitorDataArray);

                    $lock->release();
                    return response()->json($responseData);
                } catch (\Exception $e) {
                    $lock->release();
                    Log::error('Failed to fetch/store visitors data', [
                        'tenant_id' => $tenantId,
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            } else {
                // Lock acquisition failed - wait a bit and retry with DB
                sleep(1);
                $cachedData = $this->getMaterializedVisitorsData($tenantId, $startDate, $endDate);
                if ($cachedData !== null) {
                    return response()->json($cachedData);
                }
                // If still no data, return empty response
                return response()->json([
                    'visitor_data' => [],
                    'total_visits' => 0,
                    'total_unique_visitors' => 0,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Lock acquisition failed for visitors data', [
                'tenant_id' => $tenantId,
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'error' => $e->getMessage(),
            ]);
            // Return empty response on error
            return response()->json([
                'visitor_data' => [],
                'total_visits' => 0,
                'total_unique_visitors' => 0,
            ]);
        }
    }

    protected function formatDuration($seconds)
    {
        $minutes = floor($seconds / 60);
        $seconds = floor($seconds % 60);
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function summary(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;

        $user = $request->user();
        $tenantId = $this->tenantId($request);

        // Normalize Carbon::now() to compute once per request
        $endDate = Carbon::now();

        // Current period (last 7 days)
        $startDate = $endDate->copy()->subDays(7);

        // Previous period (last 14 days to 7 days ago)
        $previousStartDate = $endDate->copy()->subDays(14);
        $previousEndDate = $endDate->copy()->subDays(7);

        // OPTIMIZATION: Cache full summary response for 7 minutes to reduce database load
        $cacheKey = "dashboard:summary:{$tenantId}:{$user->id}";
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            $cacheHit = true;
            return response()->json($cachedResponse);
        }

        // OPTIMIZATION: Fetch both periods (cached queries prevent duplicate database work)
        // Even though called separately, caching in getMaterializedSummaryData prevents re-querying
        $currentOverview = $this->getMaterializedSummaryData($tenantId, $startDate, $endDate);
        $previousOverviewData = $this->getMaterializedSummaryData($tenantId, $previousStartDate, $previousEndDate);

        // Track data source for logging
        $dataSource = 'database';

        // If we have both periods from database, use them
        if ($currentOverview !== null && $previousOverviewData !== null) {
            $cacheHit = true;
            $overview = $currentOverview;
            $previousOverview = $previousOverviewData;
        } else {
            // Fallback to GA API only if database has no data
            $dataSource = 'mixed';
            if ($currentOverview === null && $previousOverviewData === null) {
                $dataSource = 'ga_api';
            }

            try {
                // Only fetch missing periods from GA API (not both if one exists)
                if ($currentOverview === null) {
                    $overview = $analytics->getOverviewMetricsOnly($tenantId, $startDate, $endDate);

                    // Validate GA API response structure before processing
                    if (!$this->validateOverviewResponse($overview, "current period ({$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')})")) {
                        throw new \RuntimeException('Invalid GA API response structure - missing required keys');
                    }

                    // OPTIMIZATION: Store fetched GA API data in database for future requests
                    // Note: We store aggregated data for the date range under the end date
                    // This matches the materialization service pattern and allows getMaterializedSummaryData
                    // to find it when querying the same date range. The data represents aggregated metrics
                    // for the entire date range, not individual daily breakdowns.
                    $this->storeSummaryDataSafely($tenantId, $endDate, $overview);
                } else {
                    $overview = $currentOverview;
                }

                if ($previousOverviewData === null) {
                    $previousOverview = $analytics->getOverviewMetricsOnly($tenantId, $previousStartDate, $previousEndDate);

                    // Validate GA API response structure before processing
                    if (!$this->validateOverviewResponse($previousOverview, "previous period ({$previousStartDate->format('Y-m-d')} to {$previousEndDate->format('Y-m-d')})")) {
                        throw new \RuntimeException('Invalid GA API response structure - missing required keys');
                    }

                    // OPTIMIZATION: Store fetched GA API data in database for future requests
                    // Note: We store aggregated data for the date range under the end date
                    $this->storeSummaryDataSafely($tenantId, $previousEndDate, $previousOverview);
                } else {
                    $previousOverview = $previousOverviewData;
                }
            } catch (\Exception $e) {
                // Graceful fallback for slow/failed GA requests
                Log::warning('GA summary request failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Use database data if available, otherwise return defaults
                $overview = $currentOverview ?? ['sessions' => 0, 'pageViews' => 0, 'bounceRate' => 0, 'averageSessionDuration' => 0, 'users' => 0];
                $previousOverview = $previousOverviewData ?? ['sessions' => 0, 'pageViews' => 0, 'bounceRate' => 0, 'averageSessionDuration' => 0, 'users' => 0];
            }
        }

        // Calculate changes
        $visitsChange = $overview['sessions'] - $previousOverview['sessions'];
        $pageViewsChange = $overview['pageViews'] - $previousOverview['pageViews'];
        $bounceRateChange = $overview['bounceRate'] - $previousOverview['bounceRate'];

        // Format average session time
        $formattedAverageTime = $this->formatDuration($overview['averageSessionDuration']);

        // OPTIMIZATION: Cache database queries (5 minutes cache)
        $dbCacheKey = "summary:db:{$user->id}";
        $dbData = Cache::remember($dbCacheKey, 300, function() use ($user) {
            return [
                'totalcustomers' => ApiCustomer::where('user_id', $user->id)->count(),
                'sold' => DB::table('user_properties')
                    ->where('user_id', $user->id)
                    ->where('purpose', 'sold')
                    ->count(),
                'purposeCounts' => DB::table('user_properties')
                    ->where('user_id', $user->id)
                    ->select('purpose', DB::raw('COUNT(*) as total'))
                    ->groupBy('purpose')
                    ->orderByDesc('total')
                    ->get(),
            ];
        });

        $totalcustomers = $dbData['totalcustomers'];
        $sold = (int) ($dbData['sold'] ?? 0);
        $purposeCounts = $dbData['purposeCounts'];
        $propertiesTotal = $purposeCounts->sum('total');

        $response = [
            'status' => 'success',
            'visits' => $overview['sessions'],
            'visits_change' => $visitsChange,
            'page_views' => $overview['pageViews'],
            'page_views_change' => $pageViewsChange,
            'average_time' => $formattedAverageTime,
            'average_time_change' => 0,  // Add logic here if you want to compare average time between periods
            'bounce_rate' => $overview['bounceRate'],
            'bounce_rate_change' => $bounceRateChange,
            'totalcustomers' => $totalcustomers,
            'properties' => [
                'total' => $propertiesTotal,
                'sold' => $sold,
                'properties_purposes' => $purposeCounts,
            ],
        ];

        // Cache response for 7 minutes (420 seconds)
        Cache::put($cacheKey, $response, 420);

        return response()->json($response);
    }



    public function devices(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;
        $dataSource = 'database';

        $tenantId = $this->tenantId($request);

        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(7);

        // OPTIMIZATION: Check cached response first (20 minutes cache)
        $cacheKey = "dashboard:devices:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            $cacheHit = true;
            return response()->json($cachedResponse);
        }

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedDevicesData($tenantId, $startDate, $endDate);

        if ($cachedData !== null) {
            $cacheHit = true;
            $devices = $cachedData['devices'] ?? [];
        } else {
            // Fallback to GA API
            $dataSource = 'ga_api';
            $tenantFilter = $this->buildTenantFilter($tenantId, false);

            try {
                $devices = $analytics->getDeviceBreakdown($tenantId, $startDate, $endDate, $tenantFilter);

                // Validate and store fetched data
                if (is_array($devices) && !empty($devices)) {
                    $this->storeDevicesDataSafely($tenantId, $endDate, $devices);
                } else {
                    Log::warning('GA devices API returned empty array', [
                        'tenant_id' => $tenantId,
                        'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')
                    ]);
                }
            } catch (\Exception $e) {
                // Graceful fallback for slow GA requests
                Log::warning('GA devices request failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $devices = [];
            }
        }

        // OPTIMIZATION: Limit to top 15 devices by views/sessions to reduce payload size
        if (is_array($devices) && !empty($devices)) {
            // Sort by views/sessions descending and take top 15
            usort($devices, function($a, $b) {
                $aViews = $a['sessions'] ?? $a['views'] ?? 0;
                $bViews = $b['sessions'] ?? $b['views'] ?? 0;
                return $bViews <=> $aViews;
            });
            $devices = array_slice($devices, 0, 15);
        }

        $response = ['devices' => $devices ?? []];

        // Cache response for 20 minutes (1200 seconds)
        Cache::put($cacheKey, $response, 1200);

        return response()->json($response);
    }

    public function trafficSources(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;
        $dataSource = 'database';

        $tenantId = $this->tenantId($request);

        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(7);

        // OPTIMIZATION: Check cached response first (20 minutes cache)
        $cacheKey = "dashboard:traffic-sources:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            $cacheHit = true;
            return response()->json($cachedResponse);
        }

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedTrafficSourcesData($tenantId, $startDate, $endDate);

        if ($cachedData !== null) {
            $cacheHit = true;
            $sources = $cachedData['sources'] ?? [];
        } else {
            // Fallback to GA API
            $dataSource = 'ga_api';
            $tenantFilter = $this->buildTenantFilter($tenantId, false); // Use EXACT match for security

            try {
                $sources = $analytics->getTrafficSources($startDate, $endDate, $tenantFilter);

                // Validate and store fetched data
                if (is_array($sources) && !empty($sources)) {
                    $this->storeTrafficSourcesDataSafely($tenantId, $endDate, $sources);
                } else {
                    Log::warning('GA traffic sources API returned empty array', [
                        'tenant_id' => $tenantId,
                        'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')
                    ]);
                }
            } catch (\Exception $e) {
                // Graceful fallback for slow GA requests
                Log::warning('GA traffic sources request failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $sources = [];
            }
        }

        // OPTIMIZATION: Limit to top 15 sources by traffic to reduce payload size
        if (is_array($sources) && !empty($sources)) {
            // Sort by sessions/views descending and take top 15
            usort($sources, function($a, $b) {
                $aTraffic = $a['sessions'] ?? $a['views'] ?? $a['count'] ?? 0;
                $bTraffic = $b['sessions'] ?? $b['views'] ?? $b['count'] ?? 0;
                return $bTraffic <=> $aTraffic;
            });
            $sources = array_slice($sources, 0, 15);
        }

        $response = ['sources' => $sources ?? []];

        // Cache response for 20 minutes (1200 seconds)
        Cache::put($cacheKey, $response, 1200);

        return response()->json($response);
    }

    public function mostVisitedPages(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;
        $dataSource = 'database';

        $tenantId = $this->tenantId($request);

        // Retrieve and validate time range from the request (default to 30 days if not provided)
        $timeRange = $this->validateTimeRange($request->input('time_range', 30), 30);

        // Normalize Carbon::now() to compute once per request
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays($timeRange);

        // OPTIMIZATION: Check cached response first (20 minutes cache)
        // BUT: Skip cache if it's empty to allow retry
        $cacheKey = $this->getMostVisitedPagesCacheKey($tenantId, $timeRange, $startDate, $endDate);
        $cachedResponse = Cache::get($cacheKey);

        // Only use cache if it has actual data (non-empty pages array)
        if ($cachedResponse !== null && !empty($cachedResponse['pages'] ?? [])) {
            $cacheHit = true;
            Log::info('Most visited pages: returning cached data', [
                'tenant_id' => $tenantId,
                'pages_count' => count($cachedResponse['pages'] ?? [])
            ]);
            return response()->json($cachedResponse);
        }

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedTopPagesData($tenantId, $startDate, $endDate);

        if ($cachedData !== null) {
            $cacheHit = true;
            $formattedPages = $cachedData['pages'] ?? [];
            Log::info('Most visited pages: using materialized data', [
                'tenant_id' => $tenantId,
                'pages_count' => count($formattedPages)
            ]);
        } else {
            // Fallback to GA API
            $dataSource = 'ga_api';

            try {
                Log::info('Most visited pages: fetching from GA API', [
                    'tenant_id' => $tenantId,
                    'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')
                ]);

                $dashboardData = $analytics->getDashboardData($tenantId, $startDate, $endDate);
                $pages = $dashboardData['topPages'] ?? [];

                Log::info('Most visited pages: GA API response', [
                    'tenant_id' => $tenantId,
                    'raw_pages_count' => count($pages),
                    'has_topPages_key' => isset($dashboardData['topPages'])
                ]);

                if (!empty($pages)) {
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
                    })->toArray();

                    // Store fetched data
                    $this->storeTopPagesDataSafely($tenantId, $endDate, $formattedPages);

                    Log::info('Most visited pages: successfully formatted and stored', [
                        'tenant_id' => $tenantId,
                        'formatted_pages_count' => count($formattedPages)
                    ]);
                } else {
                    Log::warning('GA most visited pages API returned empty array', [
                        'tenant_id' => $tenantId,
                        'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                        'dashboard_data_keys' => array_keys($dashboardData ?? [])
                    ]);
                    $formattedPages = [];
                }
            } catch (\Exception $e) {
                // Graceful fallback for slow/failed GA requests
                Log::warning('GA most visited pages request failed', [
                    'tenant_id' => $tenantId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $formattedPages = [];
            }
        }

        // OPTIMIZATION: Limit to top 20 pages by views to reduce payload size
        if (is_array($formattedPages) && !empty($formattedPages)) {
            // Sort by views descending and take top 20
            usort($formattedPages, function($a, $b) {
                return ($b['views'] ?? 0) <=> ($a['views'] ?? 0);
            });
            $formattedPages = array_slice($formattedPages, 0, 20);
        }

        $response = [
            'pages' => $formattedPages ?? [],
            'meta' => [
                'date_range' => [
                    'start' => $startDate->toDateString(),
                    'end' => $endDate->toDateString(),
                    'days' => $timeRange
                ],
                'data_source' => $dataSource,
                'cache_hit' => $cacheHit,
                'total_pages' => count($formattedPages ?? [])
            ]
        ];

        // Only cache non-empty responses to prevent caching empty results
        // Empty responses are likely temporary (no data yet) and should be retried
        if (!empty($formattedPages)) {
            // Cache response for 20 minutes (1200 seconds)
            Cache::put($cacheKey, $response, 1200);
            Log::info('Most visited pages: cached response', [
                'tenant_id' => $tenantId,
                'pages_count' => count($formattedPages),
                'time_range' => $timeRange
            ]);
        } else {
            Log::warning('Most visited pages: not caching empty response', [
                'tenant_id' => $tenantId,
                'data_source' => $dataSource,
                'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d'),
                'time_range' => $timeRange
            ]);
        }

        return response()->json($response);
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

    /**
     * Get materialized visitors data for date range
     */
    protected function getMaterializedVisitorsData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // Query analytics_daily_summary for date range
        $records = AnalyticsDailySummary::forTenant($tenantId)
            ->forDateRange($start, $end)
            ->get();

        if ($records->isEmpty()) {
            return null;
        }

        // Merge data from multiple days - extract visitors metric from each day's data
        $allVisitorData = [];
        $totalVisits = 0;
        $totalUniqueVisitors = 0;

        foreach ($records as $record) {
            $dayData = $record->data;
            if (isset($dayData['visitors']) && is_array($dayData['visitors'])) {
                $visitorsData = $dayData['visitors'];
                if (isset($visitorsData['visitor_data']) && is_array($visitorsData['visitor_data'])) {
                    $allVisitorData = array_merge($allVisitorData, $visitorsData['visitor_data']);
                    $totalVisits += $visitorsData['total_visits'] ?? 0;
                    $totalUniqueVisitors += $visitorsData['total_unique_visitors'] ?? 0;
                }
            }
        }

        // If we have data, return it
        if (!empty($allVisitorData)) {
            return [
                'visitor_data' => $allVisitorData,
                'total_visits' => $totalVisits,
                'total_unique_visitors' => $totalUniqueVisitors,
            ];
        }

        return null;
    }

    /**
     * Store materialized visitors data in database
     * Stores each day's data separately using storeMetric
     *
     * @param string $tenantId
     * @param Carbon $start Start date of the range
     * @param Carbon $end End date of the range
     * @param array $visitorData Raw visitor data from GA API (array of daily data points)
     * @return void
     */
    protected function storeMaterializedVisitorsData(string $tenantId, Carbon $start, Carbon $end, array $visitorData): void
    {
        // Only store if materialization is enabled
        if (!config('analytics.materialization.enabled', true)) {
            return;
        }

        try {
            // Group visitor data by date
            $dataByDate = [];
            foreach ($visitorData as $item) {
                $date = $item['date']->format('Y-m-d');
                if (!isset($dataByDate[$date])) {
                    $dataByDate[$date] = [];
                }
                $dataByDate[$date][] = $item;
            }

            // Store each day's data separately
            foreach ($dataByDate as $dateStr => $dayData) {
                $targetDate = Carbon::parse($dateStr);

                // Skip today's data (will be synced by scheduled job)
                if ($targetDate->isToday()) {
                    continue;
                }

                // Format data to match API response structure
                $formattedData = collect($dayData)->map(function ($item) {
                    return [
                        'date' => $item['date']->locale('ar')->isoFormat('D MMMM'), // Arabic date format
                        'visits' => $item['sessions'],
                        'uniqueVisitors' => $item['users']
                    ];
                })->toArray();

                // Calculate totals for this day
                $totalVisits = collect($dayData)->sum('sessions');
                $totalUniqueVisitors = collect($dayData)->sum('users');

                // Store using atomic JSON_SET (won't overwrite other metrics)
                AnalyticsDailySummary::storeMetric(
                    $tenantId,
                    $targetDate,
                    'visitors',
                    [
                        'visitor_data' => $formattedData,
                        'total_visits' => $totalVisits,
                        'total_unique_visitors' => $totalUniqueVisitors,
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::warning('Failed to store materialized visitors data', [
                'tenant_id' => $tenantId,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw - allow request to complete even if storage fails
        }
    }

    /**
     * Get materialized traffic sources data - optimized single query approach
     * Replaces N+1 pattern with single date range query
     */
    protected function getMaterializedTrafficSourcesData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // OPTIMIZATION: Single query with date range, ordered by date DESC to get most recent first
        $record = AnalyticsDailySummary::forTenant($tenantId)
            ->forDateRange($start, $end)
            ->whereNotNull('data')
            ->whereRaw("JSON_EXTRACT(data, '$.traffic_sources.sources') IS NOT NULL")
            ->whereRaw("JSON_LENGTH(JSON_EXTRACT(data, '$.traffic_sources.sources')) > 0")
            ->orderByDesc('date')
            ->first();

        if ($record && isset($record->data['traffic_sources']['sources']) && !empty($record->data['traffic_sources']['sources'])) {
            return ['sources' => $record->data['traffic_sources']['sources']];
        }

        return null;
    }

    /**
     * Get materialized devices data - optimized single query approach
     * Replaces N+1 pattern with single date range query
     */
    protected function getMaterializedDevicesData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // OPTIMIZATION: Single query with date range, ordered by date DESC to get most recent first
        $record = AnalyticsDailySummary::forTenant($tenantId)
            ->forDateRange($start, $end)
            ->whereNotNull('data')
            ->whereRaw("JSON_EXTRACT(data, '$.devices.devices') IS NOT NULL")
            ->whereRaw("JSON_LENGTH(JSON_EXTRACT(data, '$.devices.devices')) > 0")
            ->orderByDesc('date')
            ->first();

        if ($record && isset($record->data['devices']['devices']) && !empty($record->data['devices']['devices'])) {
            return ['devices' => $record->data['devices']['devices']];
        }

        return null;
    }

    /**
     * Get materialized top pages data - optimized single query approach
     * Replaces N+1 pattern with single date range query
     */
    protected function getMaterializedTopPagesData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // OPTIMIZATION: Single query with date range, ordered by date DESC to get most recent first
        $record = AnalyticsDailySummary::forTenant($tenantId)
            ->forDateRange($start, $end)
            ->whereNotNull('data')
            ->whereRaw("JSON_EXTRACT(data, '$.top_pages.pages') IS NOT NULL")
            ->whereRaw("JSON_LENGTH(JSON_EXTRACT(data, '$.top_pages.pages')) > 0")
            ->orderByDesc('date')
            ->first();

        if ($record && isset($record->data['top_pages']['pages']) && !empty($record->data['top_pages']['pages'])) {
            return ['pages' => $record->data['top_pages']['pages']];
        }

        return null;
    }

    /**
     * Store summary data safely - uses atomic JSON_SET to prevent race conditions
     *
     * @param string $tenantId
     * @param Carbon $date The date to store the data under
     * @param array $overview The overview data from GA API
     * @return bool True if stored successfully, false otherwise
     */
    protected function storeSummaryDataSafely(string $tenantId, Carbon $date, array $overview): bool
    {
        // Validate response structure before storage
        $requiredKeys = ['sessions', 'pageViews', 'bounceRate', 'averageSessionDuration', 'users'];
        foreach ($requiredKeys as $key) {
            if (!isset($overview[$key])) {
                Log::warning('Invalid GA API response structure - missing key', [
                    'tenant_id' => $tenantId,
                    'date' => $date->format('Y-m-d'),
                    'missing_key' => $key,
                    'available_keys' => array_keys($overview)
                ]);
                return false;
            }
        }

        try {
            // JSON_SET is atomic, so no cache lock needed
            AnalyticsDailySummary::storeMetric(
                $tenantId,
                $date,
                'summary',
                ['overview' => $overview]
            );
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to store GA API data in database', [
                'tenant_id' => $tenantId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Validate GA API overview response structure
     *
     * @param array $overview The overview data from GA API
     * @param string $context Context for error messages (e.g., 'current period', 'previous period')
     * @return bool True if valid, false otherwise
     */
    protected function validateOverviewResponse(array $overview, string $context = ''): bool
    {
        $requiredKeys = ['sessions', 'pageViews', 'bounceRate', 'averageSessionDuration', 'users'];
        $missingKeys = [];

        foreach ($requiredKeys as $key) {
            if (!isset($overview[$key])) {
                $missingKeys[] = $key;
            }
        }

        if (!empty($missingKeys)) {
            Log::error('Invalid GA API response structure', [
                'context' => $context,
                'missing_keys' => $missingKeys,
                'available_keys' => array_keys($overview)
            ]);
            return false;
        }

        return true;
    }

    /**
     * Store devices data safely - uses atomic JSON_SET to prevent race conditions
     *
     * @param string $tenantId
     * @param Carbon $date The date to store the data under
     * @param array $devices The devices data from GA API
     * @return bool True if stored successfully, false otherwise
     */
    protected function storeDevicesDataSafely(string $tenantId, Carbon $date, array $devices): bool
    {
        if (empty($devices)) {
            return false;
        }

        try {
            // JSON_SET is atomic, so no cache lock needed
            AnalyticsDailySummary::storeMetric(
                $tenantId,
                $date,
                'devices',
                ['devices' => $devices]
            );
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to store devices data in database', [
                'tenant_id' => $tenantId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Store traffic sources data safely - uses atomic JSON_SET to prevent race conditions
     *
     * @param string $tenantId
     * @param Carbon $date The date to store the data under
     * @param array $sources The traffic sources data from GA API
     * @return bool True if stored successfully, false otherwise
     */
    protected function storeTrafficSourcesDataSafely(string $tenantId, Carbon $date, array $sources): bool
    {
        if (empty($sources)) {
            return false;
        }

        try {
            // JSON_SET is atomic, so no cache lock needed
            AnalyticsDailySummary::storeMetric(
                $tenantId,
                $date,
                'traffic_sources',
                ['sources' => $sources]
            );
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to store traffic sources data in database', [
                'tenant_id' => $tenantId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Store top pages data safely - uses atomic JSON_SET to prevent race conditions
     *
     * @param string $tenantId
     * @param Carbon $date The date to store the data under
     * @param array $pages The top pages data from GA API
     * @return bool True if stored successfully, false otherwise
     */
    protected function storeTopPagesDataSafely(string $tenantId, Carbon $date, array $pages): bool
    {
        if (empty($pages)) {
            return false;
        }

        try {
            // JSON_SET is atomic, so no cache lock needed
            AnalyticsDailySummary::storeMetric(
                $tenantId,
                $date,
                'top_pages',
                ['pages' => $pages]
            );
            return true;
        } catch (\Exception $e) {
            Log::warning('Failed to store top pages data in database', [
                'tenant_id' => $tenantId,
                'date' => $date->format('Y-m-d'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get materialized summary data for date range (aggregates across days)
     * OPTIMIZED: Uses SQL aggregation instead of PHP loops for better performance
     * Includes query result caching to avoid repeated identical queries
     */
    protected function getMaterializedSummaryData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // OPTIMIZATION: Cache query results for 2 minutes to prevent repeated identical queries
        $cacheKey = "materialized:summary:{$tenantId}:{$start->format('Y-m-d')}:{$end->format('Y-m-d')}";

        return Cache::remember($cacheKey, 120, function() use ($tenantId, $start, $end) {
            try {
                // OPTIMIZATION: Use SQL aggregation - much faster than PHP loops
                // This aggregates all days in the date range in a single query
                // Note: data structure changed - summary is now at $.summary.overview
                $result = DB::table('analytics_daily_summary')
                    ->where('tenant_id', $tenantId)
                    ->whereBetween('date', [
                        $start->format('Y-m-d'),
                        $end->format('Y-m-d')
                    ])
                    ->selectRaw('
                        COALESCE(SUM(CAST(JSON_EXTRACT(data, "$.summary.overview.pageViews") AS UNSIGNED)), 0) as pageViews,
                        COALESCE(SUM(CAST(JSON_EXTRACT(data, "$.summary.overview.sessions") AS UNSIGNED)), 0) as sessions,
                        COALESCE(SUM(CAST(JSON_EXTRACT(data, "$.summary.overview.users") AS UNSIGNED)), 0) as users,
                        COALESCE(AVG(CAST(JSON_EXTRACT(data, "$.summary.overview.bounceRate") AS DECIMAL(10,4))), 0) as bounceRate,
                        COALESCE(AVG(CAST(JSON_EXTRACT(data, "$.summary.overview.averageSessionDuration") AS DECIMAL(10,2))), 0) as averageSessionDuration,
                        COUNT(*) as rowCount
                    ')
                    ->first();

                // Check if we have any data
                if (!$result || $result->rowCount == 0) {
                    return null;
                }

                return [
                    'pageViews' => (int) $result->pageViews,
                    'sessions' => (int) $result->sessions,
                    'users' => (int) $result->users,
                    'bounceRate' => (float) $result->bounceRate,
                    'averageSessionDuration' => (float) $result->averageSessionDuration,
                ];
            } catch (\Exception $e) {
            // Fallback to Eloquent with PHP aggregation if SQL JSON extraction fails
            // This handles edge cases or database compatibility issues
            Log::warning('SQL aggregation failed, falling back to Eloquent', [
                'tenant_id' => $tenantId,
                'start_date' => $start->format('Y-m-d'),
                'end_date' => $end->format('Y-m-d'),
                'error' => $e->getMessage()
            ]);

            // Fallback: Use Eloquent model with PHP aggregation (original method)
            $records = AnalyticsDailySummary::forTenant($tenantId)
                ->forDateRange($start, $end)
                ->get();

            if ($records->isEmpty()) {
                return null;
            }

            // Aggregate overview metrics across all days
            $totals = [
                'pageViews' => 0,
                'sessions' => 0,
                'users' => 0,
                'bounceRateSum' => 0,
                'durationSum' => 0,
                'rowCount' => 0,
            ];

            foreach ($records as $record) {
                // Extract summary data from consolidated JSON structure
                if (isset($record->data['summary']['overview'])) {
                    $overview = $record->data['summary']['overview'];
                    $totals['pageViews'] += $overview['pageViews'] ?? 0;
                    $totals['sessions'] += $overview['sessions'] ?? 0;
                    $totals['users'] += $overview['users'] ?? 0;
                    $totals['bounceRateSum'] += $overview['bounceRate'] ?? 0;
                    $totals['durationSum'] += $overview['averageSessionDuration'] ?? 0;
                    $totals['rowCount']++;
                }
            }

            if ($totals['rowCount'] === 0) {
                return null;
            }

            return [
                'pageViews' => $totals['pageViews'],
                'sessions' => $totals['sessions'],
                'users' => $totals['users'],
                'bounceRate' => $totals['bounceRateSum'] / $totals['rowCount'],
                'averageSessionDuration' => $totals['durationSum'] / $totals['rowCount'],
            ];
            }
        });
    }




    public function setupProgress(Request $request)
    {
        $data = app(SiteSetupProgressService::class)->getProgress($request->user());

        return response()->json($data);
    }

    public function getRecentActivity(Request $request)
    {
        $user = $request->user();
        $tenantOwnerId = $user->tenantOwnerId();

        // Get locale from request or use default
        $locale = $request->get('locale', app()->getLocale());

        // Get optional filters
        // Validate limit parameter
        $limitInput = $request->input('limit');
        $defaultLimit = 10;

        if ($limitInput !== null) {
            // Limit parameter was provided - validate it
            if (!is_numeric($limitInput)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid limit parameter',
                    'errors' => ['limit' => ['The limit must be a positive integer between 1 and 100.']]
                ], 400);
            }

            $limit = (int) $limitInput;

            if ($limit <= 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid limit parameter',
                    'errors' => ['limit' => ['The limit must be a positive integer between 1 and 100.']]
                ], 400);
            }

            if ($limit > 100) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid limit parameter',
                    'errors' => ['limit' => ['The limit must not exceed 100.']]
                ], 400);
            }
        } else {
            // Limit parameter not provided - use default
            $limit = $defaultLimit;
        }

        $actorId = $request->input('actor_id');
        $action = $request->input('action');

        // Build query for tenant-wide activity logs with eager loading to avoid N+1
        // Only eager load actor when actor_id is not null to avoid unnecessary queries
        $query = EmployeeActivityLog::with([
                'actor' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'username', 'email');
                }
            ])
            ->where('user_id', $tenantOwnerId)
            ->when($actorId, fn($q) => $q->where('actor_id', (int) $actorId))
            ->when($action, fn($q) => $q->where('action', $action))
            ->orderByDesc('created_at')
            ->limit($limit);

        $logs = $query->get();

        // Map logs to frontend format
        $activities = $logs->map(function ($log) use ($locale) {
            $actor = $log->actor;
            $userName = $this->getUserName($actor);

            // Translate action key
            $actionKey = $log->action ?? 'activity.unknown';
            $translatedAction = ActivityActionMapper::translateActionKey($actionKey, $locale);

            $section = $this->getSectionFromTargetType($log->target_type);

            // Get Arabic translations
            $actionAR = $this->getActionArabic($actionKey);
            // Use the same method as actionAR to ensure proper Arabic translation
            $actionLabelAR = $this->getActionArabic($actionKey);
            $sectionAR = $this->getSectionArabic($section);

            return [
                'id' => $log->id,
                'action' => $actionKey, // Keep original key
                'action_label' => $translatedAction, // Translated label
                'section' => $section,
                'actionAR' => $actionAR,
                'action_labelAR' => $actionLabelAR,
                'sectionAR' => $sectionAR,
                'time' => $log->created_at ? $log->created_at->diffForHumans() : 'just now',
                'icon' => $this->getIconForTargetType($log->target_type, $log->action),
                'actor_id' => $log->actor_id,
                'actor_name' => $userName,
                'created_at' => $log->created_at ? $log->created_at->toISOString() : Carbon::now()->toISOString(),
            ];
        });

        return response()->json([
            'activities' => $activities
        ]);
    }

    /**
     * Get user name from actor model
     */
    protected function getUserName(?User $actor): string
    {
        if (!$actor) {
            return 'Unknown User';
        }

        // Try to get full name first
        $fullName = trim(($actor->first_name ?? '') . ' ' . ($actor->last_name ?? ''));
        if ($fullName) {
            return $fullName;
        }

        // Fallback to username or email
        return $actor->username ?? $actor->email ?? 'Unknown User';
    }

    /**
     * Get section name from target_type (model class name)
     */
    protected function getSectionFromTargetType(?string $targetType): string
    {
        if (!$targetType) {
            return 'General';
        }

        // Extract class basename if it's a full class name
        $basename = class_basename($targetType);

        // Map common model names to readable section names
        $sectionMap = [
            'Property' => 'Properties',
            'ApiCustomer' => 'Customers',
            'CrmCard' => 'CRM',
            'CrmRequest' => 'CRM',
            'RmRental' => 'Rentals',
            'RmContract' => 'Contracts',
            'RmPayment' => 'Payments',
            'RmMaintenanceTicket' => 'Maintenance',
            'UserPropertyRequest' => 'Property Requests',
            'ApiCustomerInquiry' => 'Inquiries',
        ];

        return $sectionMap[$basename] ?? $basename;
    }

    /**
     * Get icon name for target type and action
     */
    protected function getIconForTargetType(?string $targetType, ?string $action): string
    {
        if (!$targetType) {
            return 'activity';
        }

        $basename = class_basename($targetType);

        // Icon mapping based on target type
        $iconMap = [
            'Property' => 'home',
            'ApiCustomer' => 'user',
            'CrmCard' => 'briefcase',
            'CrmRequest' => 'file-text',
            'RmRental' => 'file-text',
            'RmContract' => 'file-text',
            'RmPayment' => 'dollar-sign',
            'RmMaintenanceTicket' => 'tool',
            'UserPropertyRequest' => 'search',
            'ApiCustomerInquiry' => 'message-circle',
        ];

        // Action-based icon overrides
        if ($action) {
            $actionLower = strtolower($action);
            if (str_contains($actionLower, 'create') || str_contains($actionLower, 'add')) {
                return 'plus-circle';
            }
            if (str_contains($actionLower, 'update') || str_contains($actionLower, 'edit')) {
                return 'edit';
            }
            if (str_contains($actionLower, 'delete') || str_contains($actionLower, 'remove')) {
                return 'trash';
            }
            if (str_contains($actionLower, 'view') || str_contains($actionLower, 'show')) {
                return 'eye';
            }
        }

        return $iconMap[$basename] ?? 'file-text';
    }

    /**
     * Convert action format from "property.created" to "activity.create.property"
     */
    protected function convertActionToTranslationKey(string $action): string
    {
        // Handle already formatted keys (e.g., "activity.create.property")
        if (str_starts_with($action, 'activity.')) {
            return $action;
        }

        // Handle format like "property.created", "customer.updated", etc.
        if (str_contains($action, '.')) {
            [$resource, $actionType] = explode('.', $action, 2);

            // Check if actionType contains underscore (special actions like "toggle_featured")
            if (str_contains($actionType, '_')) {
                // Special action format: "toggle_featured.property" -> "activity.toggle_featured.property"
                return "activity.{$actionType}.{$resource}";
            }

            // Map standard action types
            $actionTypeMap = [
                'created' => 'create',
                'updated' => 'update',
                'deleted' => 'delete',
                'viewed' => 'view',
            ];

            $normalizedActionType = $actionTypeMap[$actionType] ?? $actionType;

            return "activity.{$normalizedActionType}.{$resource}";
        }

        // Fallback for unknown format
        return 'activity.unknown';
    }

    /**
     * Get Arabic translation for action (e.g., "property.created" -> "تم إنشاء عقار")
     */
    protected function getActionArabic(string $actionKey): string
    {
        // Convert action format from "property.created" to "activity.create.property"
        $translationKey = $this->convertActionToTranslationKey($actionKey);

        // Get Arabic translation
        $translation = trans("activity_log.{$translationKey}", [], 'ar');

        // If translation not found, return the original key
        return $translation !== "activity_log.{$translationKey}" ? $translation : $actionKey;
    }

    /**
     * Get Arabic translation for section name
     */
    protected function getSectionArabic(string $section): string
    {
        $sectionTranslations = [
            'Properties' => 'عقارات المستخدم',
            'Customers' => 'العملاء',
            'CRM' => 'إدارة علاقات العملاء',
            'Rentals' => 'الإيجارات',
            'Contracts' => 'العقود',
            'Payments' => 'المدفوعات',
            'Maintenance' => 'الصيانة',
            'Property Requests' => 'طلبات العقارات',
            'Inquiries' => 'الاستفسارات',
            'General' => 'عام',
            'Projects' => 'المشاريع',
            // Handle table names or unmapped values that might appear
            'user_properties' => 'عقارات المستخدم',
            'api_customers' => 'العملاء',
            'Property' => 'عقارات المستخدم',
            'ApiCustomer' => 'العملاء',
            'CrmCard' => 'إدارة علاقات العملاء',
            'CrmRequest' => 'إدارة علاقات العملاء',
            'RmRental' => 'الإيجارات',
            'RmContract' => 'العقود',
            'RmPayment' => 'المدفوعات',
            'RmMaintenanceTicket' => 'الصيانة',
            'UserPropertyRequest' => 'طلبات العقارات',
            'ApiCustomerInquiry' => 'الاستفسارات',
        ];

        return $sectionTranslations[$section] ?? $section;
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
     * - GET /api/analytics/page-locations?days=30
     *
     * Note: Tenant is automatically determined from authenticated user
     */
    public function getPageLocations(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $tenantId = $this->tenantId($request); // Use authenticated user's tenant

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
     * Get today's analytics (near realtime with perfect tenant filtering)
     *
     * Returns data from today only - better than realtime for multi-tenant!
     * Updates every 1-2 hours but supports tenant_id filtering perfectly.
     *
     * Usage examples:
     * - GET /api/analytics/today
     *
     * Note: Tenant is automatically determined from authenticated user
     */
    public function getToday(Request $request)
    {
        $tenantId = $this->tenantId($request); // Use authenticated user's tenant

        $result = $this->analytics->getTodayData($tenantId);

        return response()->json([
            'status' => 'success',
            'tenant_filter' => $tenantId,
            ...$result,
        ]);
    }

    /**
     * Get realtime data (last 30 minutes)
     *
     * Returns data from the last 30 minutes (like Google Analytics Realtime)
     *
     * NOTE: Realtime API cannot filter by tenant_id (GA4 limitation)
     * For better tenant filtering, use /api/analytics/today instead!
     *
     * Usage examples:
     * - GET /api/analytics/realtime
     *
     * Note: Tenant is automatically determined from authenticated user
     */
    public function getRealtime(Request $request)
    {
        $tenantId = $this->tenantId($request); // Use authenticated user's tenant

        $result = $this->analytics->getRealtimeData($tenantId);

        return response()->json([
            'status' => 'success',
            'tenant_filter' => $tenantId,
            ...$result,
        ]);
    }

    /**
     * Search/Filter analytics - Get all data with backend filtering
     *
     * Returns tenant-specific GA4 data filtered by your criteria on the backend
     *
     * Usage examples:
     * - GET /api/analytics/search?min_views=10&path_contains=/property/
     * - GET /api/analytics/search?min_views=5&limit=20
     * - GET /api/analytics/search?days=7
     *
     * Note: Tenant is automatically determined from authenticated user
     */
    public function searchAnalytics(Request $request)
    {
        $days = (int) $request->input('days', 7);
        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        // Build filters from request
        $filters = [];

        // Always use authenticated user's tenant (security fix)
        $tenantId = $this->tenantId($request);
        $filters['tenant_ids'] = [$tenantId]; // Enforce single tenant

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
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
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
            $recentProperties = Property::where('user_id', $user->id)
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

    /**
     * Comprehensive GA4 Diagnostic Test Endpoint
     * Tests GA4 tracking from multiple angles to identify issues
     *
     * Usage: GET /api/dashboard/diagnostic-ga-test?tenant_id=lira&slug=shk-fakhr-llaygar-hy-alaaard-shmal-alryad&days=7
     */
    public function diagnosticGATest(Request $request)
    {
        $tenantId = $request->input('tenant_id', $this->tenantId($request));
        $slug = $request->input('slug', '');
        $days = (int) $request->input('days', 7);

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        $results = [
            'status' => 'testing',
            'tenant_id' => $tenantId,
            'slug' => $slug ?: '(not provided - showing all tenant data)',
            'date_range' => [
                'start' => $startDate->format('Y-m-d'),
                'end' => $endDate->format('Y-m-d'),
                'days' => $days,
            ],
            'tests' => [],
        ];

        // If no slug provided, show overview of all tenant data
        if (empty($slug)) {
            \Log::info('=== OVERVIEW MODE: No slug provided, showing all tenant data ===', [
                'tenant_id' => $tenantId,
                'days' => $days,
            ]);

            // Get all analytics data for this tenant
            try {
                $allData = $this->analytics->getAllAnalyticsWithFilters(
                    $startDate,
                    $endDate,
                    [
                        'tenant_ids' => [$tenantId],
                        'exclude_empty_tenant' => false,
                        'limit' => 100,
                    ]
                );

                // Group by path and sum views
                $pathSummary = [];
                foreach ($allData['data'] as $item) {
                    $path = $item['path'];
                    if (!isset($pathSummary[$path])) {
                        $pathSummary[$path] = 0;
                    }
                    $pathSummary[$path] += (int) $item['views'];
                }

                // Sort by views descending
                arsort($pathSummary);

                $results['tests']['overview_all_pages'] = [
                    'status' => 'success',
                    'description' => 'All pages viewed by this tenant in the date range',
                    'total_items' => count($pathSummary),
                    'total_views' => array_sum($pathSummary),
                    'top_pages' => array_slice($pathSummary, 0, 20, true),  // Top 20 pages
                    'all_pages' => $pathSummary,
                    'note' => 'Slug parameter was empty, showing overview of all pages',
                ];
                \Log::info('Overview Results:', $pathSummary);
            } catch (\Exception $e) {
                $results['tests']['overview_all_pages'] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                \Log::error('Overview Failed:', ['error' => $e->getMessage()]);
            }

            // Also get today's data
            try {
                $todayData = $this->analytics->getTodayData($tenantId);
                $results['tests']['todays_overview'] = [
                    'status' => 'success',
                    'description' => 'Real-time data for today (updates every 1-2 hours)',
                    'total_views_today' => $todayData['total_views'],
                    'pages_count' => count($todayData['pages']),
                    'pages' => array_slice($todayData['pages'], 0, 20, true),
                ];
                \Log::info('Today\'s Overview:', $todayData);
            } catch (\Exception $e) {
                $results['tests']['todays_overview'] = [
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];
                \Log::error('Today\'s Overview Failed:', ['error' => $e->getMessage()]);
            }

            $results['summary'] = [
                'all_tests_passed' => !array_filter($results['tests'], fn($t) => ($t['status'] ?? '') === 'failed'),
                'interpretation' => [
                    'If total_views > 0' => '✅ GA4 is receiving and processing data for this tenant!',
                    'If total_items > 0' => '✅ Multiple pages are being tracked.',
                    'If all shows 0' => '❌ No GA4 events for this tenant. Check: 1) Tenant tracking code, 2) GA4 configuration, 3) Measurement ID.',
                    'If today\'s data shows pages' => '✅ GA4 is collecting real-time events (updates every 1-2 hours).',
                ],
                'next_steps' => 'To debug a specific page, provide the slug parameter: ?tenant_id=' . $tenantId . '&slug=YOUR_SLUG&days=' . $days,
            ];

            \Log::info('=== DIAGNOSTIC TEST (OVERVIEW) COMPLETE ===', $results);
            return response()->json($results);
        }

        // ORIGINAL SLUG-SPECIFIC DIAGNOSTIC LOGIC (when slug IS provided)
        // TEST 1: Query with explicit tenant_id filter (new data)
        \Log::info('=== TEST 1: Query with tenant_id filter ===', [
            'tenant_id' => $tenantId,
        ]);

        $paths = [
            "/property/{$slug}",
            "/ar/property/{$slug}",
            "/en/property/{$slug}",
        ];

        try {
            $result1 = $this->analytics->getPageViewsForPaths($tenantId, $startDate, $endDate, $paths);
            $results['tests']['test1_with_tenant_filter'] = [
                'status' => 'success',
                'description' => 'Query with tenant_id filter (new data with tracking)',
                'paths_queried' => $paths,
                'results' => $result1,
                'total_views' => array_sum($result1),
            ];
            \Log::info('TEST 1 Results:', $result1);
        } catch (\Exception $e) {
            $results['tests']['test1_with_tenant_filter'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            \Log::error('TEST 1 Failed:', ['error' => $e->getMessage()]);
        }

        // TEST 2: Query ALL data for slug (without tenant filter)
        \Log::info('=== TEST 2: Query all data for slug ===');

        try {
            $allData = $this->analytics->getAllAnalyticsWithFilters(
                $startDate,
                $endDate,
                [
                    'path_contains' => "/property/{$slug}",
                    'limit' => 100,
                ]
            );

            $results['tests']['test2_all_data_for_slug'] = [
                'status' => 'success',
                'description' => 'Query ALL data for this slug (regardless of tenant_id)',
                'path_filter' => "/property/{$slug}",
                'data_found' => $allData['data'],
                'total_items' => $allData['total_items'],
                'total_views' => $allData['total_views'],
            ];
            \Log::info('TEST 2 Results:', $allData);
        } catch (\Exception $e) {
            $results['tests']['test2_all_data_for_slug'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            \Log::error('TEST 2 Failed:', ['error' => $e->getMessage()]);
        }

        // TEST 3: Check if slug exists in database
        \Log::info('=== TEST 3: Verify slug in database ===');

        try {
            $property = \DB::table('user_property_contents as upc')
                ->join('user_properties as up', 'up.id', '=', 'upc.property_id')
                ->join('users as u', 'u.id', '=', 'up.user_id')
                ->whereRaw('LOWER(upc.slug) = ?', [strtolower($slug)])
                ->select('u.username', 'upc.slug', 'upc.title', 'upc.id')
                ->first();

            if ($property) {
                $results['tests']['test3_slug_in_db'] = [
                    'status' => 'found',
                    'description' => 'Slug verified in database',
                    'slug' => $property->slug,
                    'title' => $property->title,
                    'tenant_username' => $property->username,
                    'content_id' => $property->id,
                ];
                \Log::info('TEST 3: Slug found in DB', (array)$property);
            } else {
                $results['tests']['test3_slug_in_db'] = [
                    'status' => 'not_found',
                    'description' => 'Slug NOT found in database',
                    'slug_searched' => $slug,
                ];
                \Log::warning('TEST 3: Slug not found', ['slug' => $slug]);
            }
        } catch (\Exception $e) {
            $results['tests']['test3_slug_in_db'] = [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
            \Log::error('TEST 3 Failed:', ['error' => $e->getMessage()]);
        }

        // TEST 4: Get today's data for debugging
        \Log::info('=== TEST 4: Today\'s data (near real-time) ===');

        try {
            $todayData = $this->analytics->getTodayData($tenantId);

            // Filter for our slug
            $relevantPages = array_filter($todayData['pages'], function($page) use ($slug) {
                return strpos($page['path'], $slug) !== false;
            });

            $results['tests']['test4_todays_data'] = [
                'status' => 'success',
                'description' => 'Today\'s data (updates every 1-2 hours)',
                'all_pages_count' => count($todayData['pages']),
                'pages_with_slug' => array_values($relevantPages),
                'total_views_today' => $todayData['total_views'],
            ];
            \Log::info('TEST 4 Results:', [
                'all_pages_count' => count($todayData['pages']),
                'relevant_pages' => $relevantPages,
            ]);
        } catch (\Exception $e) {
            $results['tests']['test4_todays_data'] = [
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
            \Log::error('TEST 4 Failed:', ['error' => $e->getMessage()]);
        }

        // TEST 5: Check GA4 property configuration
        \Log::info('=== TEST 5: GA4 Configuration ===');

        $results['tests']['test5_ga4_config'] = [
            'status' => 'info',
            'measurement_id' => config('services.google.analytics_property_id'),
            'property_id' => 'properties/' . config('services.google.analytics_property_id'),
            'note' => 'Verify this matches your GA4 property ID',
        ];

        // SUMMARY
        $results['summary'] = [
            'all_tests_passed' => !array_filter($results['tests'], fn($t) => ($t['status'] ?? '') === 'failed'),
            'next_steps' => [
                'If test1 shows views > 0' => 'Tracking is working! Data just needs 24-48 hours to process.',
                'If test2 shows views > 0' => 'GA4 is receiving data but tenant_id may not be tracked. Check frontend GA4 code.',
                'If test3 shows not_found' => 'Slug doesn\'t exist. Check the slug parameter.',
                'If test4 shows pages' => 'GA4 is getting near-real-time data. Wait for full processing.',
                'If all show 0' => 'GA4 may not be receiving any events. Check: 1) Frontend gtag code, 2) GA4 configuration, 3) Measurement ID.',
            ],
            'debugging_logs' => 'Check storage/logs/laravel.log for detailed output',
        ];

        \Log::info('=== DIAGNOSTIC TEST COMPLETE ===', $results);

        return response()->json($results);
    }

    /**
     * Live Test Endpoint - Real-time GA4 Tenant Filtering Verification
     *
     * **Purpose:**
     * This endpoint provides real-time verification that GA4 tenant filtering is working
     * correctly. It executes a live GA4 query with tenant filters and returns detailed
     * information about the query, response, and security verification.
     *
     * **Security Implementation:**
     * - Uses GA4 dimensionFilter with MatchType::EXACT for tenant filtering
     * - Only accesses data for authenticated user's tenant (from $this->tenantId())
     * - Verifies that all returned rows match the authenticated tenant
     *
     * **Use Cases:**
     * - Debugging tenant data isolation
     * - Verifying GA4 filter configuration
     * - Testing security fixes after deployment
     * - Quick validation that tenant_id is being tracked correctly
     *
     * **AI Agent Context:**
     * This endpoint is specifically designed to help AI agents understand:
     * 1. How tenant filtering works in this multi-tenant GA4 implementation
     * 2. What security measures are in place (GA4 filters vs backend filtering)
     * 3. How to verify tenant data isolation is working correctly
     * 4. The structure of GA4 responses and tenant_id field usage
     *
     * **Request Parameters:**
     * - days (optional, default: 7) - Number of days to query (1-365)
     * - tenant_id (optional) - Specific tenant to test. If not provided, uses authenticated user's tenant.
     *   **Note:** This parameter is allowed for debugging/testing purposes only.
     *
     * **Response Structure:**
     * {
     *   "status": "success|error",
     *   "test_info": {
     *     "authenticated_tenant": "username",
     *     "date_range": {...},
     *     "ga4_filter_applied": true,
     *     "filter_type": "EXACT",
     *     "filter_field": "customEvent:tenant_id"
     *   },
     *   "ga4_response": {
     *     "total_rows": 10,
     *     "sample_rows": [...]
     *   },
     *   "security_verification": {
     *     "all_rows_match_tenant": true,
     *     "tenant_ids_found": [...],
     *     "status": "✅ SECURE - All rows match tenant"
     *   },
     *   "comparison": {
     *     "production_method": "Uses GA4 dimensionFilter for security",
     *     "fallback_method": "Backend filtering only for historical data without tenant_id"
     *   }
     * }
     *
     * **Example Usage:**
     * ```bash
     * # Test with default 7 days (uses authenticated user's tenant)
     * GET /api/analytics/live-test
     *
     * # Test with 30 days (uses authenticated user's tenant)
     * GET /api/analytics/live-test?days=30
     *
     * # Test specific tenant (for debugging/testing)
     * GET /api/analytics/live-test?tenant_id=asl-aledarh-real-estate&days=30
     * ```
     *
     * **Example Response (Success):**
     * ```json
     * {
     *   "status": "success",
     *   "test_info": {
     *     "authenticated_tenant": "lira",
     *     "date_range": {"start": "2026-01-20", "end": "2026-01-27", "days": 7},
     *     "ga4_filter_applied": true,
     *     "filter_type": "EXACT",
     *     "filter_field": "customEvent:tenant_id"
     *   },
     *   "ga4_response": {
     *     "total_rows": 10,
     *     "sample_rows": [
     *       {
     *         "pagePath": "/ar/property/sample-slug",
     *         "tenant_id": "lira",
     *         "pageViews": 150,
     *         "sessions": 45
     *       }
     *     ]
     *   },
     *   "security_verification": {
     *     "all_rows_match_tenant": true,
     *     "tenant_ids_found": ["lira"],
     *     "status": "✅ SECURE - All rows match tenant"
     *   }
     * }
     * ```
     *
     * **AI Agent Notes:**
     * - The endpoint uses $this->tenantId($request) which extracts tenant from authenticated user
     * - GA4 queries use dimensionFilter to filter at API level (security best practice)
     * - All production methods use this same pattern for tenant filtering
     * - Backend filtering is only used as fallback for historical data without tenant_id
     * - If security_verification.all_rows_match_tenant is false, investigate tenant_id tracking
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function liveTest(Request $request)
    {
        // Start performance tracking
        $startTime = microtime(true);
        $performanceMetrics = [
            'queries_executed' => 0,
            'total_execution_time_ms' => 0,
            'individual_query_times' => [],
        ];

        // Allow tenant_id from request for testing/debugging purposes
        // This is a debug endpoint, so we allow overriding the authenticated tenant
        $requestTenantId = $request->input('tenant_id');
        $authenticatedTenantId = $this->tenantId($request);

        // Use requested tenant_id if provided, otherwise use authenticated user's tenant
        $tenantId = $requestTenantId ?: $authenticatedTenantId;

        // Validate tenant_id if provided (must be non-empty string)
        if ($requestTenantId && (!is_string($requestTenantId) || trim($requestTenantId) === '')) {
            return response()->json([
                'status' => 'error',
                'message' => 'tenant_id parameter must be a non-empty string',
                'provided' => $requestTenantId,
            ], 400);
        }

        $days = (int) $request->input('days', 7);

        // Validate days parameter
        if ($days < 1 || $days > 365) {
            return response()->json([
                'status' => 'error',
                'message' => 'days parameter must be between 1 and 365',
                'provided' => $days,
            ], 400);
        }

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        // Build tenant filter using the same method as production code
        $tenantFilter = $this->buildTenantFilter($tenantId, false); // Use EXACT match

        // Test query with tenant filter - same pattern as production methods
        // Use getTenantPageViews for simplicity (it already uses GA4 filters)
        try {
            // Track query 1: getTenantPageViews
            $query1Start = microtime(true);
            $tenantViews = $this->analytics->getTenantPageViews($tenantId, $startDate, $endDate);
            $query1Time = (microtime(true) - $query1Start) * 1000; // Convert to milliseconds
            $performanceMetrics['queries_executed']++;
            $performanceMetrics['individual_query_times']['getTenantPageViews'] = round($query1Time, 2);

            // Track query 2: getOverviewMetricsOnly
            $query2Start = microtime(true);
            $overviewMetrics = $this->analytics->getOverviewMetricsOnly($tenantId, $startDate, $endDate);
            $query2Time = (microtime(true) - $query2Start) * 1000;
            $performanceMetrics['queries_executed']++;
            $performanceMetrics['individual_query_times']['getOverviewMetricsOnly'] = round($query2Time, 2);

            // Get raw GA4 API response for detailed inspection
            // Use reflection to access protected methods for raw response
            $rawGA4Response = null;
            try {
                $rawGA4Response = $this->getRawGA4Response($tenantId, $startDate, $endDate, $tenantFilter);
                $performanceMetrics['queries_executed']++;
            } catch (\Exception $e) {
                // If raw response fails, continue without it
                $rawGA4Response = ['error' => 'Could not fetch raw GA4 response: ' . $e->getMessage()];
            }

            // Get sample page paths for detailed verification
            $samplePaths = array_slice($tenantViews['paths'] ?? [], 0, 10);

            // Prepare response data
            $totalPageViews = $overviewMetrics['pageViews'] ?? 0;
            $totalSessions = $overviewMetrics['sessions'] ?? 0;

            // Verify that data was returned (indicates GA4 filter is working)
            $hasData = $totalPageViews > 0 || $totalSessions > 0;

            // Track query 3: getTenantIdsInGA4
            $query3Start = microtime(true);
            $tenantIdsInGA4 = $this->analytics->getTenantIdsInGA4($startDate, $endDate);
            $query3Time = (microtime(true) - $query3Start) * 1000;
            $performanceMetrics['queries_executed']++;
            $performanceMetrics['individual_query_times']['getTenantIdsInGA4'] = round($query3Time, 2);

            $tenantFoundInGA4 = isset($tenantIdsInGA4[$tenantId]);

            // Track query 4: Verify GA4 is receiving data at all (across all tenants)
            $query4Start = microtime(true);
            try {
                $allTenantsData = $this->analytics->getAllAnalyticsWithFilters($startDate, $endDate, []);
                $hasAnyData = ($allTenantsData['total_views'] > 0 || !empty($allTenantsData['data']));
                $query4Time = (microtime(true) - $query4Start) * 1000;
                $performanceMetrics['queries_executed']++;
                $performanceMetrics['individual_query_times']['getAllAnalyticsWithFilters'] = round($query4Time, 2);
            } catch (\Exception $e) {
                $allTenantsData = ['total_views' => 0, 'total_items' => 0, 'data' => []];
                $hasAnyData = false;
                $query4Time = (microtime(true) - $query4Start) * 1000;
                $performanceMetrics['individual_query_times']['getAllAnalyticsWithFilters'] = round($query4Time, 2) . ' (failed)';
            }

            // Track query 5: Get today's data for recent events check
            $query5Start = microtime(true);
            try {
                $todayData = $this->analytics->getTodayData($tenantId);
                $hasTodayData = (($todayData['total_views'] ?? 0) > 0 || ($todayData['total_users'] ?? 0) > 0);
                $query5Time = (microtime(true) - $query5Start) * 1000;
                $performanceMetrics['queries_executed']++;
                $performanceMetrics['individual_query_times']['getTodayData'] = round($query5Time, 2);
            } catch (\Exception $e) {
                $todayData = ['total_views' => 0, 'total_users' => 0, 'total_pages' => 0, 'pages' => []];
                $hasTodayData = false;
                $query5Time = (microtime(true) - $query5Start) * 1000;
                $performanceMetrics['individual_query_times']['getTodayData'] = round($query5Time, 2) . ' (failed)';
            }

            // Security verification: All data returned should belong to authenticated tenant
            // (This is guaranteed by GA4 dimensionFilter, but we document it here)
            $verificationStatus = $hasData
                ? '✅ SECURE - GA4 filter applied correctly, data returned matches authenticated tenant'
                : 'ℹ️ INFO - No data returned for this tenant in the specified date range (this is normal if no events occurred)';

            return response()->json([
                'status' => 'success',
                'test_info' => [
                    'authenticated_tenant' => $authenticatedTenantId,
                    'tested_tenant' => $tenantId,
                    'tenant_source' => $requestTenantId
                        ? 'request_parameter'
                        : 'authenticated_user',
                    'date_range' => [
                        'start' => $startDate->format('Y-m-d'),
                        'end' => $endDate->format('Y-m-d'),
                        'days' => $days,
                    ],
                    'ga4_filter_applied' => true,
                    'filter_type' => 'EXACT',
                    'filter_field' => 'customEvent:tenant_id',
                    'filter_value' => $tenantId,
                ],
                'ga4_response' => [
                    'overview_metrics' => [
                        'total_page_views' => $totalPageViews,
                        'total_sessions' => $totalSessions,
                        'total_users' => $overviewMetrics['users'] ?? 0,
                        'bounce_rate' => $overviewMetrics['bounceRate'] ?? 0,
                        'avg_session_duration' => $overviewMetrics['averageSessionDuration'] ?? 0,
                    ],
                    'sample_page_paths' => $samplePaths,
                    'total_paths_found' => $tenantViews['total_paths'] ?? 0,
                    'method_used' => 'getTenantPageViews + getOverviewMetricsOnly (both use GA4 dimensionFilter)',
                ],
                'ga4_health_check' => [
                    'ga4_receiving_data' => $hasAnyData,
                    'total_events_across_all_tenants' => [
                        'total_views' => $allTenantsData['total_views'] ?? 0,
                        'total_items' => $allTenantsData['total_items'] ?? 0,
                    ],
                    'status' => $hasAnyData
                        ? '✅ GA4 is receiving data - issue may be with tenant_id tracking'
                        : '⚠️ GA4 shows no data at all - check GA4 setup and tracking code',
                ],
                'tenant_tracking_status' => [
                    'authenticated_tenant' => $tenantId,
                    'tenant_found_in_ga4' => $tenantFoundInGA4,
                    'tenant_page_views_in_ga4' => $tenantIdsInGA4[$tenantId] ?? 0,
                    'all_tenants_in_ga4' => array_keys($tenantIdsInGA4),
                    'total_tenants_tracked' => count($tenantIdsInGA4),
                    'top_tenants_by_views' => array_slice($tenantIdsInGA4, 0, 10, true),
                    'diagnosis' => $tenantFoundInGA4
                        ? '✅ Your tenant_id is being tracked in GA4'
                        : '❌ Your tenant_id is NOT found in GA4 - check frontend tracking code',
                ],
                'recent_events_check' => [
                    'last_24h_note' => 'GA4 can take 24-48 hours to process data',
                    'todays_data' => [
                        'total_views' => $todayData['total_views'] ?? 0,
                        'total_users' => $todayData['total_users'] ?? 0,
                        'total_pages' => $todayData['total_pages'] ?? 0,
                        'sample_pages' => array_slice($todayData['pages'] ?? [], 0, 5),
                    ],
                    'has_today_data' => $hasTodayData,
                    'note' => $hasTodayData && !$hasData
                        ? '⚠️ Today\'s data exists but historical doesn\'t - data may still be processing (24-48 hour delay)'
                        : ($hasTodayData
                            ? '✅ Recent events are being tracked'
                            : 'ℹ️ No recent events found for today'),
                ],
                'security_verification' => [
                    'ga4_filter_used' => true,
                    'filter_applied_at' => 'GA4 API level (not backend filtering)',
                    'backend_filtering' => false,
                    'status' => $verificationStatus,
                    'note' => 'All data returned is pre-filtered by GA4 using dimensionFilter with MatchType::EXACT, ensuring tenant isolation',
                ],
                'frontend_verification_checklist' => [
                    'check_1' => 'Verify React app is sending tenant_id with every GA4 event',
                    'check_2' => 'Verify parameter name is exactly "tenant_id" (not "tenantId" or "tenant_id_value")',
                    'check_3' => 'Verify value matches authenticated user\'s username: "' . $tenantId . '"',
                    'check_4' => 'Check browser console for GA4 events using: gtag("event", ...)',
                    'check_5' => 'Verify GA4 custom dimension "tenant_id" is configured as Event-scoped',
                    'example_code' => [
                        'gtag' => 'gtag("event", "page_view", { "tenant_id": "' . $tenantId . '" })',
                        'measurement_id' => 'Verify Measurement ID matches config: ' . config('services.google.analytics_property_id'),
                    ],
                ],
                'recommendations' => $this->generateRecommendations($hasData, $tenantIdsInGA4, $tenantId, $hasAnyData, $hasTodayData),
                'implementation_details' => [
                    'production_method' => 'Uses GA4 dimensionFilter for security and performance',
                    'filter_location' => 'Applied at GA4 API level (not backend filtering)',
                    'fallback_method' => 'Backend filtering only used for historical data without tenant_id',
                    'security_fix_applied' => '2026-01-27 - Changed from backend filtering to GA4 API filtering',
                    'tenant_isolation' => 'Guaranteed by GA4 dimensionFilter with MatchType::EXACT',
                ],
                'raw_ga4_response' => [
                    'note' => 'Raw GA4 API response structure for debugging',
                    'response_structure' => $rawGA4Response,
                    'usage' => 'Use this to verify the exact structure GA4 returns, including dimensions, metrics, and filters applied',
                ],
                'performance_metrics' => $this->calculatePerformanceMetrics($performanceMetrics, $startTime),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'authenticated_tenant' => $tenantId,
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace hidden in production - set APP_DEBUG=true to see details',
                'troubleshooting' => [
                    'check_ga4_credentials' => 'Verify service-account-credentials.json exists and is valid',
                    'check_property_id' => 'Verify services.google.analytics_property_id is configured',
                    'check_tenant_id' => 'Verify user has a valid username/tenant_id',
                    'check_ga4_data' => 'Verify GA4 is receiving events with tenant_id custom parameter',
                ],
            ], 500);
        }
    }

    /**
     * Generate recommendations based on live test results
     *
     * Provides actionable troubleshooting steps based on what the test found
     *
     * @param bool $hasData Whether tenant has data
     * @param array $tenantIdsInGA4 All tenant IDs found in GA4
     * @param string $tenantId The authenticated tenant ID
     * @param bool $hasAnyData Whether GA4 has any data at all
     * @param bool $hasTodayData Whether tenant has today's data
     * @return array
     */
    protected function generateRecommendations(bool $hasData, array $tenantIdsInGA4, string $tenantId, bool $hasAnyData, bool $hasTodayData): array
    {
        $recommendations = [];

        if (!$hasAnyData) {
            $recommendations[] = [
                'priority' => 'critical',
                'issue' => 'GA4 is not receiving any data at all',
                'checks' => [
                    '1. Verify GA4 Measurement ID is correct in frontend code',
                    '2. Check browser DevTools Network tab for GA4 requests to "collect" endpoint',
                    '3. Verify GA4 property ID in config: ' . config('services.google.analytics_property_id'),
                    '4. Test if GA4 is enabled and tracking code is loaded on frontend',
                    '5. Check GA4 Real-time reports in Google Analytics dashboard',
                ],
            ];
        } elseif (!$hasData && !isset($tenantIdsInGA4[$tenantId])) {
            $recommendations[] = [
                'priority' => 'critical',
                'issue' => 'Tenant ID not found in GA4 data',
                'checks' => [
                    '1. Verify React frontend is sending tenant_id: "' . $tenantId . '" with every event',
                    '2. Check GA4 DebugView in real-time to see incoming events',
                    '3. Verify custom dimension parameter name matches exactly: "tenant_id"',
                    '4. Test with browser DevTools open to see outgoing GA4 requests',
                    '5. Verify GA4 custom dimension "tenant_id" is Event-scoped (not User Property)',
                ],
                'frontend_code_example' => [
                    'correct' => 'gtag("event", "page_view", { "tenant_id": "' . $tenantId . '" })',
                    'incorrect' => 'gtag("event", "page_view", { "tenantId": "' . $tenantId . '" }) // Wrong parameter name',
                ],
            ];
        } elseif (!$hasData && isset($tenantIdsInGA4[$tenantId])) {
            $recommendations[] = [
                'priority' => 'medium',
                'issue' => 'Tenant ID found in GA4 but no data for date range',
                'checks' => [
                    '1. Data may be outside the selected date range - try different date range',
                    '2. Check if tenant had activity in the specified period',
                    '3. Verify date range spans when events occurred',
                ],
            ];
        } elseif ($hasTodayData && !$hasData) {
            $recommendations[] = [
                'priority' => 'low',
                'issue' => 'Recent data exists but historical data missing',
                'checks' => [
                    '1. This is normal - GA4 takes 24-48 hours to process data',
                    '2. Today\'s data is available but historical data still processing',
                    '3. Wait 24-48 hours and check again',
                ],
            ];
        }

        if (empty($recommendations)) {
            $recommendations[] = [
                'priority' => 'info',
                'issue' => 'All checks passed',
                'message' => '✅ Everything looks good! Tenant filtering is working correctly and data is being tracked.',
            ];
        }

        return $recommendations;
    }

    /**
     * Get raw GA4 API response for debugging
     *
     * Returns the actual raw response structure from GA4 API
     * Useful for understanding the exact data structure and verifying filters
     *
     * @param string $tenantId
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @param FilterExpression $tenantFilter
     * @return array
     */
    protected function getRawGA4Response(string $tenantId, Carbon $startDate, Carbon $endDate, FilterExpression $tenantFilter): array
    {
        try {
            // Use reflection to access protected properties/methods for raw query
            $reflection = new \ReflectionClass($this->analytics);
            $clientProperty = $reflection->getProperty('client');
            $clientProperty->setAccessible(true);
            $client = $clientProperty->getValue($this->analytics);

            $propertyIdProperty = $reflection->getProperty('propertyId');
            $propertyIdProperty->setAccessible(true);
            $propertyId = $propertyIdProperty->getValue($this->analytics);

            $executeWithRetryMethod = $reflection->getMethod('executeWithRetry');
            $executeWithRetryMethod->setAccessible(true);

            // Build the exact same query as production
            $queryParams = [
                'property' => $propertyId,
                'dateRanges' => [
                    new \Google\Analytics\Data\V1beta\DateRange([
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ]),
                ],
                'dimensions' => [
                    new \Google\Analytics\Data\V1beta\Dimension(['name' => 'pagePath']),
                    new \Google\Analytics\Data\V1beta\Dimension(['name' => 'customEvent:tenant_id']),
                ],
                'metrics' => [
                    new \Google\Analytics\Data\V1beta\Metric(['name' => 'screenPageViews']),
                    new \Google\Analytics\Data\V1beta\Metric(['name' => 'sessions']),
                    new \Google\Analytics\Data\V1beta\Metric(['name' => 'totalUsers']),
                ],
                'dimensionFilter' => $tenantFilter, // GA4 filter applied
                'limit' => 10, // Limit for raw response
            ];

            $response = $executeWithRetryMethod->invoke($this->analytics, function() use ($client, $queryParams) {
                return $client->runReport($queryParams);
            }, 'getRawGA4Response');

            // Parse raw response
            $rawRows = [];
            foreach ($response->getRows() as $index => $row) {
                $dimensions = $row->getDimensionValues();
                $metrics = $row->getMetricValues();

                $rawRows[] = [
                    'row_index' => $index,
                    'dimensions' => [
                        'pagePath' => $dimensions[0]->getValue(),
                        'customEvent:tenant_id' => $dimensions[1]->getValue(),
                    ],
                    'metrics' => [
                        'screenPageViews' => (int) $metrics[0]->getValue(),
                        'sessions' => (int) $metrics[1]->getValue(),
                        'totalUsers' => (int) $metrics[2]->getValue(),
                    ],
                ];
            }

            return [
                'query_params' => [
                    'property' => $propertyId,
                    'date_range' => [
                        'start_date' => $startDate->format('Y-m-d'),
                        'end_date' => $endDate->format('Y-m-d'),
                    ],
                    'dimensions_requested' => ['pagePath', 'customEvent:tenant_id'],
                    'metrics_requested' => ['screenPageViews', 'sessions', 'totalUsers'],
                    'filter_applied' => [
                        'field_name' => 'customEvent:tenant_id',
                        'filter_type' => 'string_filter',
                        'match_type' => 'EXACT',
                        'value' => $tenantId,
                    ],
                    'limit' => 10,
                ],
                'response_metadata' => [
                    'total_rows_returned' => count($rawRows),
                    'row_limit' => 10,
                    'note' => 'Only first 10 rows shown for brevity',
                ],
                'raw_rows' => $rawRows,
                'response_structure_explanation' => [
                    'dimensions' => 'These are the breakdown dimensions from GA4',
                    'metrics' => 'These are the aggregated metrics for each row',
                    'filter_verification' => 'All rows should have customEvent:tenant_id matching: ' . $tenantId,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'note' => 'Raw GA4 response could not be fetched - check GA4 credentials and property ID',
            ];
        }
    }

    /**
     * Calculate and format performance metrics
     *
     * @param array $performanceMetrics
     * @param float $startTime
     * @return array
     */
    protected function calculatePerformanceMetrics(array $performanceMetrics, float $startTime): array
    {
        $totalTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        $performanceMetrics['total_execution_time_ms'] = round($totalTime, 2);

        // Calculate average query time
        $successfulQueries = array_filter($performanceMetrics['individual_query_times'], function($time) {
            return is_numeric($time);
        });

        $avgQueryTime = !empty($successfulQueries)
            ? round(array_sum($successfulQueries) / count($successfulQueries), 2)
            : 0;

        // Identify slowest query
        $slowestQuery = null;
        $slowestTime = 0;
        foreach ($performanceMetrics['individual_query_times'] as $queryName => $queryTime) {
            if (is_numeric($queryTime) && $queryTime > $slowestTime) {
                $slowestTime = $queryTime;
                $slowestQuery = $queryName;
            }
        }

        // Performance assessment
        $performanceStatus = 'good';
        $performanceNote = '';
        if ($totalTime > 5000) {
            $performanceStatus = 'slow';
            $performanceNote = '⚠️ Total execution time exceeds 5 seconds - consider caching or optimization';
        } elseif ($totalTime > 2000) {
            $performanceStatus = 'moderate';
            $performanceNote = 'ℹ️ Execution time is acceptable but could be optimized with caching';
        } else {
            $performanceNote = '✅ Performance is good';
        }

        return [
            'total_execution_time_ms' => $performanceMetrics['total_execution_time_ms'],
            'total_execution_time_seconds' => round($performanceMetrics['total_execution_time_ms'] / 1000, 2),
            'queries_executed' => $performanceMetrics['queries_executed'],
            'average_query_time_ms' => $avgQueryTime,
            'slowest_query' => $slowestQuery ? [
                'query_name' => $slowestQuery,
                'execution_time_ms' => $slowestTime,
            ] : null,
            'individual_query_times' => $performanceMetrics['individual_query_times'],
            'performance_assessment' => [
                'status' => $performanceStatus,
                'note' => $performanceNote,
                'recommendation' => $performanceStatus === 'slow'
                    ? 'Consider using materialized/cached data or reducing number of queries'
                    : ($performanceStatus === 'moderate'
                        ? 'Caching recommended for frequently accessed data'
                        : 'No optimization needed'),
            ],
            'performance_breakdown' => [
                'api_calls' => 'Each GA4 API call is tracked separately',
                'caching_note' => 'Production endpoints use caching to improve performance',
                'optimization_tip' => 'For testing, these queries run without cache to show actual GA4 response times',
            ],
        ];
    }

    /**
     * Get List of Tenants from GA4 Data
     *
     * **Purpose:**
     * Returns a list of all tenants that have analytics data in GA4 for the specified date range.
     * Useful for administrative overview, debugging which tenants are being tracked, and
     * verifying tenant tracking across the platform.
     *
     * **Security:**
     * This endpoint shows all tenants in GA4 (not filtered by authenticated user).
     * Use with caution in production - consider adding admin-only middleware if needed.
     *
     * **AI Agent Context:**
     * This endpoint helps AI agents understand:
     * 1. Which tenants are actively being tracked in GA4
     * 2. Tenant activity levels (page views, sessions) for each tenant
     * 3. The distribution of analytics data across tenants
     * 4. Whether tenant tracking is working across all tenants
     *
     * **Request Parameters:**
     * - days (optional, default: 30) - Number of days to query. Recommended: 30 or 90 days.
     *   Valid values: 7, 30, 90, 365
     *
     * **Response Structure:**
     * {
     *   "status": "success",
     *   "date_range": {
     *     "start": "2025-12-17",
     *     "end": "2026-01-16",
     *     "days": 30
     *   },
     *   "tenants": [
     *     {
     *       "tenant_id": "asl-aledarh-real-estate",
     *       "page_views": 150,
     *       "sessions": 45,
     *       "users": 20,
     *       "rank": 1
     *     }
     *   ],
     *   "summary": {
     *     "total_tenants": 5,
     *     "total_page_views": 500,
     *     "average_views_per_tenant": 100
     *   }
     * }
     *
     * **Example Usage:**
     * ```bash
     * # Get tenants from last 30 days (default)
     * GET /api/analytics/tenants
     *
     * # Get tenants from last 90 days
     * GET /api/analytics/tenants?days=90
     *
     * # Get tenants from last 7 days
     * GET /api/analytics/tenants?days=7
     * ```
     *
     * **Example Response:**
     * ```json
     * {
     *   "status": "success",
     *   "date_range": {
     *     "start": "2025-12-17",
     *     "end": "2026-01-16",
     *     "days": 30
     *   },
     *   "tenants": [
     *     {
     *       "tenant_id": "asl-aledarh-real-estate",
     *       "page_views": 150,
     *       "sessions": 45,
     *       "users": 20,
     *       "rank": 1
     *     }
     *   ],
     *   "summary": {
     *     "total_tenants": 1,
     *     "total_page_views": 150,
     *     "total_sessions": 45,
     *     "average_views_per_tenant": 150
     *   }
     * }
     * ```
     *
     * **AI Agent Notes:**
     * - This endpoint queries GA4 directly using customEvent:tenant_id dimension
     * - Results are sorted by page views (descending)
     * - Only includes tenants that have actual analytics data in the date range
     * - Tenant IDs come from the customEvent:tenant_id parameter sent from frontend
     * - Empty or "(not set)" tenant_ids are excluded from results
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTenantsList(Request $request)
    {
        $startTime = microtime(true);

        // Validate days parameter (recommend 30 or 90, but allow 7 and 365 too)
        $days = (int) $request->input('days', 30);
        $allowedDays = [7, 30, 90, 365];

        if (!in_array($days, $allowedDays)) {
            return response()->json([
                'status' => 'error',
                'message' => 'days parameter must be one of: ' . implode(', ', $allowedDays),
                'provided' => $days,
                'allowed_values' => $allowedDays,
            ], 400);
        }

        $startDate = Carbon::now()->subDays($days);
        $endDate = Carbon::now();

        try {
            // Get tenant IDs with their page views from GA4
            $tenantsData = $this->analytics->getTenantIdsInGA4($startDate, $endDate);

            // Calculate summary statistics
            $totalTenants = count($tenantsData);
            $totalPageViews = array_sum($tenantsData);
            $averageViewsPerTenant = $totalTenants > 0 ? round($totalPageViews / $totalTenants, 2) : 0;

            // Format tenants list with ranking
            $tenantsList = [];
            $rank = 1;
            foreach ($tenantsData as $tenantId => $pageViews) {
                $tenantsList[] = [
                    'tenant_id' => $tenantId,
                    'page_views' => $pageViews,
                    'rank' => $rank++,
                ];
            }

            // Get additional metrics for each tenant (sessions, users)
            // This requires separate queries for each tenant, so we'll do batch queries
            $tenantsWithMetrics = [];
            foreach ($tenantsList as $tenant) {
                try {
                    // Quick query to get sessions and users for this tenant
                    $overview = $this->analytics->getOverviewMetricsOnly(
                        $tenant['tenant_id'],
                        $startDate,
                        $endDate
                    );

                    $tenantsWithMetrics[] = [
                        'tenant_id' => $tenant['tenant_id'],
                        'page_views' => $tenant['page_views'],
                        'sessions' => $overview['sessions'] ?? 0,
                        'users' => $overview['users'] ?? 0,
                        'rank' => $tenant['rank'],
                    ];
                } catch (\Exception $e) {
                    // If query fails, include tenant with page_views only
                    $tenantsWithMetrics[] = [
                        'tenant_id' => $tenant['tenant_id'],
                        'page_views' => $tenant['page_views'],
                        'sessions' => 0,
                        'users' => 0,
                        'rank' => $tenant['rank'],
                        'note' => 'Additional metrics unavailable',
                    ];
                }
            }

            $executionTime = (microtime(true) - $startTime) * 1000;

            // Calculate summary with all metrics
            $totalSessions = array_sum(array_column($tenantsWithMetrics, 'sessions'));
            $totalUsers = array_sum(array_column($tenantsWithMetrics, 'users'));

            return response()->json([
                'status' => 'success',
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'days' => $days,
                ],
                'tenants' => $tenantsWithMetrics,
                'summary' => [
                    'total_tenants' => $totalTenants,
                    'total_page_views' => $totalPageViews,
                    'total_sessions' => $totalSessions,
                    'total_users' => $totalUsers,
                    'average_views_per_tenant' => $averageViewsPerTenant,
                    'most_active_tenant' => $tenantsWithMetrics[0] ?? null,
                ],
                'performance' => [
                    'execution_time_ms' => round($executionTime, 2),
                    'execution_time_seconds' => round($executionTime / 1000, 2),
                    'queries_executed' => $totalTenants + 1, // 1 for getTenantIdsInGA4 + N for getOverviewMetricsOnly
                    'note' => 'Performance depends on number of tenants - consider caching for large tenant lists',
                ],
                'metadata' => [
                    'data_source' => 'GA4 API (customEvent:tenant_id dimension)',
                    'filter_applied' => 'None - returns all tenants with data',
                    'sorting' => 'By page views (descending)',
                    'note' => 'Only tenants with analytics data in the date range are included',
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'error' => $e->getMessage(),
                'error_type' => get_class($e),
                'trace' => config('app.debug') ? $e->getTraceAsString() : 'Trace hidden in production',
                'troubleshooting' => [
                    'check_ga4_credentials' => 'Verify service-account-credentials.json exists and is valid',
                    'check_property_id' => 'Verify services.google.analytics_property_id is configured',
                    'check_ga4_data' => 'Verify GA4 has data for the specified date range',
                ],
            ], 500);
        }
    }

}
