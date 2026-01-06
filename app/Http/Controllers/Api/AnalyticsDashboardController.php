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
use App\Models\Analytics\AnalyticsDailySummary;
use App\Models\Api\EmployeeActivityLog;
use App\Models\User;
use App\Services\ActivityActionMapper;
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
			$tenant = $user?->username;
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
     * @param Carbon $startDate
     * @param Carbon $endDate
     * @return string
     */
    protected function getMostVisitedPagesCacheKey(string $tenantId, Carbon $startDate, Carbon $endDate): string
    {
        return "ga:most-visited-pages:{$tenantId}:{$startDate->format('Y-m-d')}:{$endDate->format('Y-m-d')}";
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
        $startTime = microtime(true);
        $cacheHit = false;
        
        // Get the user and tenant ID
        $tenantId = $this->tenantId($request);

        // Retrieve and validate time range from the request (default to 30 days if not provided)
        $timeRange = $this->validateTimeRange($request->input('time_range', 30), 30);

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
        $tenantId = $user->username;

        // Normalize Carbon::now() to compute once per request
        $endDate = Carbon::now();

        // Current period (last 7 days)
        $startDate = $endDate->copy()->subDays(7);

        // Previous period (last 14 days to 7 days ago)
        $previousStartDate = $endDate->copy()->subDays(14);
        $previousEndDate = $endDate->copy()->subDays(7);

        // OPTIMIZATION: Always query database first (materialized data)
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
                'purposeCounts' => DB::table('user_properties')
                    ->where('user_id', $user->id)
                    ->select('purpose', DB::raw('COUNT(*) as total'))
                    ->groupBy('purpose')
                    ->orderByDesc('total')
                    ->get(),
            ];
        });

        $totalcustomers = $dbData['totalcustomers'];
        $purposeCounts = $dbData['purposeCounts'];
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
            'totalcustomers' => $totalcustomers,
            'properties' => [
                'total' => $propertiesTotal,
                'properties_purposes' => $purposeCounts,
            ],
        ]);
    }



    public function devices(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;
        $dataSource = 'database';
        
        $tenantId = $this->tenantId($request);

        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(7);

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedDevicesData($tenantId, $startDate, $endDate);
        
        if ($cachedData !== null) {
            $cacheHit = true;
            
            return response()->json($cachedData);
        }

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

        return response()->json(['devices' => $devices ?? []]);
    }

    public function trafficSources(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;
        $dataSource = 'database';
        
        $tenantId = $this->tenantId($request);

        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(7);

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedTrafficSourcesData($tenantId, $startDate, $endDate);
        
        if ($cachedData !== null) {
            $cacheHit = true;
            
            return response()->json($cachedData);
        }

        // Fallback to GA API
        $dataSource = 'ga_api';
        $tenantFilter = $this->buildTenantFilter($tenantId, true);
        
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

        return response()->json(['sources' => $sources ?? []]);
    }

    public function mostVisitedPages(Request $request, GoogleAnalyticsService $analytics)
    {
        $startTime = microtime(true);
        $cacheHit = false;
        $dataSource = 'database';
        
        $tenantId = $this->tenantId($request);

        // Normalize Carbon::now() to compute once per request
        $endDate = Carbon::now();
        $startDate = $endDate->copy()->subDays(7);

        // Try to get from materialized data first
        $cachedData = $this->getMaterializedTopPagesData($tenantId, $startDate, $endDate);
        
        if ($cachedData !== null) {
            $cacheHit = true;
            
            return response()->json($cachedData);
        }

        // Fallback to GA API
        $dataSource = 'ga_api';
        
        try {
            $dashboardData = $analytics->getDashboardData($tenantId, $startDate, $endDate);
            $pages = $dashboardData['topPages'] ?? [];
            
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
            } else {
                Log::warning('GA most visited pages API returned empty array', [
                    'tenant_id' => $tenantId,
                    'date_range' => $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d')
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

        return response()->json(['pages' => $formattedPages ?? []]);
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
     * Get materialized traffic sources data - improved to check date range
     */
    protected function getMaterializedTrafficSourcesData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // Try to get most recent data within the date range
        // Check yesterday first, then today, then any date in range
        $datesToCheck = [
            $end->copy()->subDay(), // Yesterday
            $end->copy(), // Today
        ];
        
        // Also check a few days back in the range
        for ($i = 2; $i <= 7 && $i <= $start->diffInDays($end); $i++) {
            $datesToCheck[] = $end->copy()->subDays($i);
        }
        
        foreach ($datesToCheck as $checkDate) {
            $record = AnalyticsDailySummary::forTenant($tenantId)
                ->forDate($checkDate)
                ->first();
                
            if ($record && isset($record->data['traffic_sources']['sources']) && !empty($record->data['traffic_sources']['sources'])) {
                return ['sources' => $record->data['traffic_sources']['sources']];
            }
        }
        
        return null;
    }

    /**
     * Get materialized devices data - improved to check date range
     */
    protected function getMaterializedDevicesData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // Try to get most recent data within the date range
        // Check yesterday first, then today, then any date in range
        $datesToCheck = [
            $end->copy()->subDay(), // Yesterday
            $end->copy(), // Today
        ];
        
        // Also check a few days back in the range
        for ($i = 2; $i <= 7 && $i <= $start->diffInDays($end); $i++) {
            $datesToCheck[] = $end->copy()->subDays($i);
        }
        
        foreach ($datesToCheck as $checkDate) {
            $record = AnalyticsDailySummary::forTenant($tenantId)
                ->forDate($checkDate)
                ->first();
                
            if ($record && isset($record->data['devices']['devices']) && !empty($record->data['devices']['devices'])) {
                return ['devices' => $record->data['devices']['devices']];
            }
        }
        
        return null;
    }

    /**
     * Get materialized top pages data - improved to check date range
     */
    protected function getMaterializedTopPagesData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
        // Try to get most recent data within the date range
        // Check yesterday first, then today, then any date in range
        $datesToCheck = [
            $end->copy()->subDay(), // Yesterday
            $end->copy(), // Today
        ];
        
        // Also check a few days back in the range
        for ($i = 2; $i <= 7 && $i <= $start->diffInDays($end); $i++) {
            $datesToCheck[] = $end->copy()->subDays($i);
        }
        
        foreach ($datesToCheck as $checkDate) {
            $record = AnalyticsDailySummary::forTenant($tenantId)
                ->forDate($checkDate)
                ->first();
                
            if ($record && isset($record->data['top_pages']['pages']) && !empty($record->data['top_pages']['pages'])) {
                return ['pages' => $record->data['top_pages']['pages']];
            }
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
     */
    protected function getMaterializedSummaryData(string $tenantId, Carbon $start, Carbon $end): ?array
    {
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

    public function getRecentActivity(Request $request)
    {
        $user = $request->user();
        $tenantOwnerId = $user->tenantOwnerId();
        
        // Get locale from request or use default
        $locale = $request->get('locale', app()->getLocale());

        // Get optional filters
        $limit = max(1, min(100, (int) $request->input('limit', 50)));
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

            return [
                'id' => $log->id,
                'action' => $actionKey, // Keep original key
                'action_label' => $translatedAction, // Translated label
                'section' => $this->getSectionFromTargetType($log->target_type),
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
     * Get today's analytics (near realtime with perfect tenant filtering)
     * 
     * Returns data from today only - better than realtime for multi-tenant!
     * Updates every 1-2 hours but supports tenant_id filtering perfectly.
     * 
     * Usage examples:
     * - GET /api/analytics/today
     * - GET /api/analytics/today?tenant_id=lira
     */
    public function getToday(Request $request)
    {
        $tenantId = $request->input('tenant_id', null);

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
     * - GET /api/analytics/realtime?tenant_id=lira (limited filtering)
     */
    public function getRealtime(Request $request)
    {
        $tenantId = $request->input('tenant_id', null);

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

}
