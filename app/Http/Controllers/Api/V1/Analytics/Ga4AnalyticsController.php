<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Analytics\GetDashboardRequest;
use App\Http\Requests\Api\V1\Analytics\GetTopPagesRequest;
use App\Http\Resources\Api\V1\Analytics\Ga4DashboardResource;
use App\Http\Resources\Api\V1\Analytics\Ga4TopPageResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Ga4AnalyticsController extends BaseApiController
{
    /**
     * Get dashboard analytics summary
     * GET /api/v1/analytics/ga4/dashboard?days=7|30|90|365
     *
     * @param GetDashboardRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function dashboard(GetDashboardRequest $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $days = (int) $request->input('days', 30);

            $startDate = Carbon::today()->subDays($days)->toDateString();
            $endDate = Carbon::today()->toDateString();

            // Get aggregated data from analytics_daily_summary
            $summary = DB::table('analytics_daily_summary')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->selectRaw('
                    SUM(total_page_views) as total_views,
                    SUM(total_sessions) as total_sessions,
                    SUM(total_users) as total_users,
                    MAX(unique_pages) as unique_pages,
                    COUNT(DISTINCT date) as active_days
                ')
                ->first();

            // Get visitor_data (daily breakdown with date, visits, uniqueVisitors)
            $visitorData = DB::table('analytics_daily_summary')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [$startDate, $endDate])
                ->select('date', 'total_sessions as visits', 'total_users as uniqueVisitors')
                ->orderBy('date')
                ->get()
                ->map(function ($item) {
                    return [
                        'date' => $item->date,
                        'visits' => (int) $item->visits,
                        'uniqueVisitors' => (int) $item->uniqueVisitors,
                    ];
                })
                ->toArray();

            // Get most visited pages (top 10)
            $totalViews = (int) ($summary->total_views ?? 0);
            $mostVisitedPages = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('page_path')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->select(
                    'page_path',
                    'page_title',
                    DB::raw('SUM(views_count) as views'),
                    DB::raw('SUM(sessions_count) as sessions'),
                    DB::raw('SUM(users_count) as users')
                )
                ->groupBy('page_path', 'page_title')
                ->orderByDesc('views')
                ->limit(10)
                ->get()
                ->map(function ($item) use ($totalViews) {
                    $percentage = $totalViews > 0 
                        ? round(($item->views / $totalViews) * 100, 2) 
                        : 0;

                    return [
                        'page_path' => $item->page_path,
                        'page_title' => $item->page_title,
                        'views' => (int) $item->views,
                        'sessions' => (int) $item->sessions,
                        'users' => (int) $item->users,
                        'percentage' => $percentage,
                        // Note: bounce_rate and avg_time require additional GA4 metrics in sync
                        'bounce_rate' => null,
                        'avg_time' => null,
                    ];
                })
                ->toArray();

            // Get properties visits (filter by page_type = 'property')
            $propertiesVisits = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->where('page_type', 'property')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->sum('views_count');

            $result = [
                'pages' => (int) ($summary->unique_pages ?? 0),
                'views' => (int) ($summary->total_views ?? 0),
                'unique_visitors' => (int) ($summary->total_users ?? 0),
                'total_visits' => (int) ($summary->total_sessions ?? 0),
                'total_unique_visitors' => (int) ($summary->total_users ?? 0),
                'active_days' => (int) ($summary->active_days ?? 0),
                'visitor_data' => $visitorData,
                'most_visited_pages' => $mostVisitedPages,
                'properties_visits' => (int) $propertiesVisits,
                'time_range' => $days,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                ],
            ];

            return $this->success(
                new Ga4DashboardResource($result),
                'Dashboard analytics retrieved successfully'
            );
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            // Re-throw HTTP response exceptions (from abort()) so they return proper JSON
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to get GA4 dashboard analytics', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to retrieve dashboard analytics: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get top pages
     * GET /api/v1/analytics/ga4/top-pages?days=7|30|90|365&limit=10
     *
     * @param GetTopPagesRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function topPages(GetTopPagesRequest $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $days = (int) $request->input('days', 30);
            $limit = (int) $request->input('limit', 10);

            $startDate = Carbon::today()->subDays($days)->toDateString();
            $endDate = Carbon::today()->toDateString();

            // Get total views for percentage calculation
            $totalViews = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('page_path')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->sum('views_count');

            // Get top pages
            $topPages = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->whereNotNull('page_path')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->select(
                    'page_path',
                    'page_title',
                    DB::raw('SUM(views_count) as views'),
                    DB::raw('SUM(sessions_count) as sessions'),
                    DB::raw('SUM(users_count) as users')
                )
                ->groupBy('page_path', 'page_title')
                ->orderByDesc('views')
                ->limit($limit)
                ->get()
                ->map(function ($item) use ($totalViews) {
                    $percentage = $totalViews > 0 
                        ? round(($item->views / $totalViews) * 100, 2) 
                        : 0;

                    return [
                        'page_path' => $item->page_path,
                        'page_title' => $item->page_title,
                        'views' => (int) $item->views,
                        'sessions' => (int) $item->sessions,
                        'users' => (int) $item->users,
                        'percentage' => $percentage,
                        // Note: bounce_rate and avg_time require additional GA4 metrics in sync
                        'bounce_rate' => null,
                        'avg_time' => null,
                    ];
                })
                ->toArray();

            return $this->success(
                Ga4TopPageResource::collection($topPages),
                'Top pages retrieved successfully'
            );
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            // Re-throw HTTP response exceptions (from abort()) so they return proper JSON
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to get GA4 top pages', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to retrieve top pages: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Get properties visits
     * GET /api/v1/analytics/ga4/properties-visits?days=7|30|90|365
     *
     * @param GetDashboardRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function propertiesVisits(GetDashboardRequest $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $days = (int) $request->input('days', 30);

            $startDate = Carbon::today()->subDays($days)->toDateString();
            $endDate = Carbon::today()->toDateString();

            $propertiesVisits = DB::table('pageview_analytics')
                ->where('tenant_id', $tenantId)
                ->where('page_type', 'property')
                ->whereBetween('date_bucket', [$startDate, $endDate])
                ->sum('views_count');

            return $this->success([
                'properties_visits' => (int) $propertiesVisits,
                'time_range' => $days,
                'period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'days' => $days,
                ],
            ], 'Properties visits retrieved successfully');
        } catch (\Illuminate\Http\Exceptions\HttpResponseException $e) {
            // Re-throw HTTP response exceptions (from abort()) so they return proper JSON
            throw $e;
        } catch (\Exception $e) {
            \Log::error('Failed to get properties visits', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->error('Failed to retrieve properties visits: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Resolve tenant ID from request
     * For dashboard endpoints, use authenticated user's username
     * Handles both tenants and employees (employees get tenant owner's username)
     *
     * @param Request $request
     * @return string
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function resolveTenantId(Request $request): string
    {
        $user = $request->user();
        
        if (!$user) {
            abort(401, 'Unauthenticated. Please provide a valid authentication token.');
        }

        // Handle employees: get tenant owner's username
        if (method_exists($user, 'isEmployee') && $user->isEmployee()) {
            // Load tenant relationship if not already loaded
            if (!$user->relationLoaded('tenant')) {
                $user->load('tenant');
            }
            
            $owner = $user->tenant;
            if ($owner && isset($owner->username) && $owner->username !== null && $owner->username !== '') {
                return $owner->username;
            }
            
            // If employee but no tenant owner found
            abort(422, 'Employee account is not associated with a tenant. Please contact support.');
        }

        // For tenants, use their own username
        // Username is a column/attribute, not a method, so access it directly
        if (isset($user->username) && $user->username !== null && $user->username !== '') {
            return $user->username;
        }

        // If we get here, the user doesn't have a username
        abort(422, 'Missing tenant identifier. The authenticated user does not have a username set. Please contact support.');
    }
}
