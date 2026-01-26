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
     * GET /api/v1/analytics/ga4/dashboard?days=7|30|90
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

            // Get daily trend (last 7 days or specified period, whichever is smaller)
            $trendDays = min($days, 7);
            $dailyTrend = DB::table('analytics_daily_summary')
                ->where('tenant_id', $tenantId)
                ->whereBetween('date', [
                    Carbon::today()->subDays($trendDays - 1)->toDateString(),
                    $endDate
                ])
                ->select('date', 'total_page_views as views')
                ->orderBy('date')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->date => (int) $item->views];
                })
                ->toArray();

            $result = [
                'total_views' => (int) ($summary->total_views ?? 0),
                'total_sessions' => (int) ($summary->total_sessions ?? 0),
                'total_users' => (int) ($summary->total_users ?? 0),
                'unique_pages' => (int) ($summary->unique_pages ?? 0),
                'active_days' => (int) ($summary->active_days ?? 0),
                'daily_trend' => $dailyTrend,
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
        } catch (\Exception $e) {
            \Log::error('Failed to get GA4 dashboard analytics', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to retrieve dashboard analytics', 500);
        }
    }

    /**
     * Get top pages
     * GET /api/v1/analytics/ga4/top-pages?days=7|30|90&limit=10
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
                    ];
                })
                ->toArray();

            return $this->success(
                Ga4TopPageResource::collection($topPages),
                'Top pages retrieved successfully'
            );
        } catch (\Exception $e) {
            \Log::error('Failed to get GA4 top pages', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to retrieve top pages', 500);
        }
    }

    /**
     * Resolve tenant ID from request
     * For dashboard endpoints, use authenticated user's username
     *
     * @param Request $request
     * @return string
     */
    protected function resolveTenantId(Request $request): string
    {
        // Try to get from authenticated user
        $user = $request->user();
        if ($user && method_exists($user, 'username')) {
            $tenantId = $user->username;
            if (!empty($tenantId)) {
                return $tenantId;
            }
        }

        // Validate tenant ID exists
        abort(422, 'Missing tenant identifier. Ensure the user has a username.');
    }
}
