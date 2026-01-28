<?php

namespace App\Http\Controllers\Api\V1\Analytics;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\V1\Analytics\TrackPageViewRequest;
use App\Http\Resources\Api\V1\Analytics\DashboardResource;
use App\Http\Resources\Api\V1\Analytics\PageviewResource;
use App\Http\Resources\Api\V1\Analytics\TopPageResource;
use App\Services\Analytics\PageviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PageviewController extends BaseApiController
{
    public function __construct(
        protected PageviewService $pageviewService
    ) {}

    /**
     * Track a page view
     * POST /api/v1/analytics/page-view
     */
    public function track(TrackPageViewRequest $request)
    {
        try {
            $userAgent = $request->userAgent();
            $viewsCount = $this->pageviewService->trackPageView(
                tenantId: $request->input('tenant_id'),
                slug: $request->input('slug'),
                dynamicSlug: $request->input('dynamic_slug'),
                path: $request->input('path'),
                pageType: $request->input('page_type'),
                userAgent: $userAgent
            );

            return $this->success([
                'views_count' => $viewsCount,
            ], 'Page view tracked successfully');
        } catch (\Exception $e) {
            \Log::error('Failed to track pageview', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to track page view', 500);
        }
    }

    /**
     * Get dashboard analytics summary
     * GET /api/v1/analytics/dashboard
     */
    public function dashboard(Request $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $days = (int) $request->input('days', 30);

            // Validate days parameter
            if (!in_array($days, [7, 30, 90])) {
                return $this->validationError([
                    'days' => ['The days parameter must be 7, 30, or 90.'],
                ]);
            }

            $summary = $this->pageviewService->getDashboardSummary($tenantId, $days);

            return $this->success(
                new DashboardResource($summary),
                'Dashboard analytics retrieved successfully'
            );
        } catch (\Exception $e) {
            \Log::error('Failed to get dashboard analytics', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to retrieve dashboard analytics', 500);
        }
    }

    /**
     * Get top pages
     * GET /api/v1/analytics/top-pages
     */
    public function topPages(Request $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $days = (int) $request->input('days', 30);
            $limit = (int) $request->input('limit', 10);

            // Validate parameters
            $validator = Validator::make($request->all(), [
                'days' => 'nullable|integer|min:1|max:365',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $topPages = $this->pageviewService->getTopPages($tenantId, $days, $limit);

            return $this->success(
                TopPageResource::collection($topPages),
                'Top pages retrieved successfully'
            );
        } catch (\Exception $e) {
            \Log::error('Failed to get top pages', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to retrieve top pages', 500);
        }
    }

    /**
     * Get top posts
     * GET /api/v1/analytics/top-posts
     */
    public function topPosts(Request $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);
            $days = (int) $request->input('days', 30);
            $limit = (int) $request->input('limit', 10);

            // Validate parameters
            $validator = Validator::make($request->all(), [
                'days' => 'nullable|integer|min:1|max:365',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $topPosts = $this->pageviewService->getTopPosts($tenantId, $days, $limit);

            return $this->success(
                TopPageResource::collection($topPosts),
                'Top posts retrieved successfully'
            );
        } catch (\Exception $e) {
            \Log::error('Failed to get top posts', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to retrieve top posts', 500);
        }
    }

    /**
     * Get views summary by date range
     * GET /api/v1/analytics/views-summary
     */
    public function summary(Request $request)
    {
        try {
            $tenantId = $this->resolveTenantId($request);

            // Validate date parameters
            $validator = Validator::make($request->all(), [
                'start_date' => 'required|date|date_format:Y-m-d',
                'end_date' => 'required|date|date_format:Y-m-d|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return $this->validationError($validator->errors()->toArray());
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');

            $summary = $this->pageviewService->getViewsSummary($tenantId, $startDate, $endDate);

            return $this->success(
                $summary,
                'Views summary retrieved successfully'
            );
        } catch (\Exception $e) {
            \Log::error('Failed to get views summary', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return $this->error('Failed to retrieve views summary', 500);
        }
    }

    /**
     * Resolve tenant ID from request
     * For dashboard endpoints, use authenticated user's username
     * For tracking endpoint, use provided tenant_id
     *
     * @param Request $request
     * @return string
     */
    protected function resolveTenantId(Request $request): string
    {
        // First, try to get from request input (for tracking endpoint)
        $tenantId = $request->input('tenant_id');

        // If not provided, try to get from authenticated user
        if (empty($tenantId)) {
            $user = $request->user();
            if ($user && method_exists($user, 'username')) {
                $tenantId = $user->username;
            }
        }

        // Validate tenant ID exists
        if (empty($tenantId)) {
            abort(422, 'Missing tenant identifier. Provide tenant_id or ensure the user has a username.');
        }

        return $tenantId;
    }
}
