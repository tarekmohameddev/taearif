<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Analytics\Services\AnalyticsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Analytics Controller
 *
 * Provides comprehensive SaaS business analytics endpoints
 */
class AnalyticsController extends BaseController
{
    /**
     * @var AnalyticsService
     */
    protected AnalyticsService $analyticsService;

    /**
     * AnalyticsController constructor.
     *
     * @param AnalyticsService $analyticsService
     */
    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get comprehensive analytics overview.
     * GET /api/v1/admin/analytics/overview
     *
     * @return JsonResponse
     */
    public function overview(): JsonResponse
    {
        try {
            $overview = $this->analyticsService->getOverview();

            $payload = [
                'summary' => [
                    'current_mrr' => $overview['mrr']['current_mrr'] ?? 0,
                    'mrr_growth_rate' => $overview['mrr']['mrr_growth_rate'] ?? 0,
                    'customer_churn_rate' => $overview['churn']['customer_churn_rate'] ?? 0,
                    'renewal_rate' => $overview['lifecycle']['renewal_rate'] ?? 0,
                    'active_tenants' => $overview['activity']['active_tenants'] ?? 0,
                ],
                'revenue' => $overview['mrr'] ?? [],
                'subscriptions' => $overview['lifecycle'] ?? [],
                'lifecycle' => $overview['lifecycle'] ?? [],
                'clv' => $overview['clv'] ?? [],
                'activity' => $overview['activity'] ?? [],
            ];

            return $this->successResponse(
                $payload,
                'Analytics overview retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve analytics overview.');
        }
    }

    /**
     * Get MRR (Monthly Recurring Revenue) analytics.
     * GET /api/v1/admin/analytics/mrr
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function mrr(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
            ]);

            $filters = $request->only(['months']);
            $mrrData = $this->analyticsService->getMrrAnalytics($filters);

            return $this->successResponse(
                $mrrData,
                'MRR analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve MRR analytics.');
        }
    }

    /**
     * Get churn analytics.
     * GET /api/v1/admin/analytics/churn
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function churn(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
            ]);

            $filters = $request->only(['months']);
            $churnData = $this->analyticsService->getChurnAnalytics($filters);

            return $this->successResponse(
                $churnData,
                'Churn analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve churn analytics.');
        }
    }

    /**
     * Get plan performance analytics.
     * GET /api/v1/admin/analytics/plans
     *
     * @return JsonResponse
     */
    public function plans(): JsonResponse
    {
        try {
            $planData = $this->analyticsService->getPlanPerformance();

            return $this->successResponse(
                $planData,
                'Plan performance analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve plan analytics.');
        }
    }

    /**
     * Get subscription lifecycle analytics.
     * GET /api/v1/admin/analytics/lifecycle
     *
     * @return JsonResponse
     */
    public function lifecycle(): JsonResponse
    {
        try {
            $lifecycleData = $this->analyticsService->getLifecycleAnalytics();

            return $this->successResponse(
                $lifecycleData,
                'Lifecycle analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve lifecycle analytics.');
        }
    }

    /**
     * Get Customer Lifetime Value (CLV) analytics.
     * GET /api/v1/admin/analytics/clv
     *
     * @return JsonResponse
     */
    public function clv(): JsonResponse
    {
        try {
            $clvData = $this->analyticsService->getClvAnalytics();

            return $this->successResponse(
                $clvData,
                'CLV analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve CLV analytics.');
        }
    }

    /**
     * Get cohort analysis.
     * GET /api/v1/admin/analytics/cohorts
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function cohorts(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
            ]);

            $filters = $request->only(['months']);
            $cohortData = $this->analyticsService->getCohortAnalytics($filters);

            return $this->successResponse(
                $cohortData,
                'Cohort analysis retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve cohort analytics.');
        }
    }

    /**
     * Get revenue forecast.
     * GET /api/v1/admin/analytics/forecast
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forecast(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'months' => ['sometimes', 'integer', 'min:1', 'max:12'],
            ]);

            $forecastMonths = $request->input('months', 6);
            $forecastData = $this->analyticsService->getRevenueForecast($forecastMonths);

            return $this->successResponse(
                $forecastData,
                'Revenue forecast retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve revenue forecast.');
        }
    }

    /**
     * Get geographic analytics.
     * GET /api/v1/admin/analytics/geography
     *
     * @return JsonResponse
     */
    public function geography(): JsonResponse
    {
        try {
            $geographyData = $this->analyticsService->getGeographicAnalytics();

            return $this->successResponse(
                $geographyData,
                'Geographic analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve geographic analytics.');
        }
    }

    /**
     * Get tenant activity metrics.
     * GET /api/v1/admin/analytics/activity
     *
     * @return JsonResponse
     */
    public function activity(): JsonResponse
    {
        try {
            $activityData = $this->analyticsService->getActivityMetrics();

            return $this->successResponse(
                $activityData,
                'Activity metrics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve activity metrics.');
        }
    }

    /**
     * Get referral program analytics.
     * GET /api/v1/admin/analytics/referrals
     *
     * @return JsonResponse
     */
    public function referrals(): JsonResponse
    {
        try {
            $referralData = $this->analyticsService->getReferralAnalytics();

            return $this->successResponse(
                $referralData,
                'Referral analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve referral analytics.');
        }
    }

    /**
     * Compare metrics between periods.
     * GET /api/v1/admin/analytics/compare
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function compare(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'metric' => ['required', 'string', 'in:revenue,users,subscriptions'],
                'period1_start' => ['required', 'date'],
                'period1_end' => ['required', 'date', 'after_or_equal:period1_start'],
                'period2_start' => ['required', 'date'],
                'period2_end' => ['required', 'date', 'after_or_equal:period2_start'],
            ]);

            $metric = $request->input('metric');
            
            // Get data for both periods
            $period1Data = $this->getMetricForPeriod($metric, $request->period1_start, $request->period1_end);
            $period2Data = $this->getMetricForPeriod($metric, $request->period2_start, $request->period2_end);

            $change = $period2Data > 0 
                ? (($period1Data - $period2Data) / $period2Data) * 100 
                : 0;

            return $this->successResponse(
                [
                    'metric' => $metric,
                    'period1' => [
                        'start' => $request->period1_start,
                        'end' => $request->period1_end,
                        'value' => $period1Data,
                    ],
                    'period2' => [
                        'start' => $request->period2_start,
                        'end' => $request->period2_end,
                        'value' => $period2Data,
                    ],
                    'change_percentage' => round($change, 2),
                ],
                'Comparison analytics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to compare analytics metrics.');
        }
    }

    /**
     * Export analytics data.
     * POST /api/v1/admin/analytics/export
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'type' => ['required', 'string', 'in:mrr,churn,plans,cohorts,overview'],
                'format' => ['sometimes', 'string', 'in:json,csv'],
                'months' => ['sometimes', 'integer', 'min:1', 'max:24'],
            ]);

            $type = $request->input('type');
            $format = $request->input('format', 'json');
            $filters = $request->only(['months']);

            $data = match($type) {
                'mrr' => $this->analyticsService->getMrrAnalytics($filters),
                'churn' => $this->analyticsService->getChurnAnalytics($filters),
                'plans' => $this->analyticsService->getPlanPerformance(),
                'cohorts' => $this->analyticsService->getCohortAnalytics($filters),
                'overview' => $this->analyticsService->getOverview(),
            };

            return $this->successResponse(
                [
                    'type' => $type,
                    'format' => $format,
                    'data' => $data,
                    'exported_at' => now()->toIso8601String(),
                ],
                'Analytics data exported successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to export analytics data.');
        }
    }

    /**
     * Get metric value for a specific period
     */
    protected function getMetricForPeriod(string $metric, string $start, string $end): float
    {
        return match($metric) {
            'revenue' => (float) DB::table('memberships')
                ->where('status', 1)
                ->whereBetween('created_at', [$start, $end])
                ->sum('price'),
            'users' => (float) DB::table('users')
                ->where('account_type', 'tenant')
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            'subscriptions' => (float) DB::table('memberships')
                ->where('status', 1)
                ->whereBetween('created_at', [$start, $end])
                ->count(),
            default => 0,
        };
    }

    /**
     * Handle analytics exceptions and format response.
     */
    protected function handleException(Throwable $e, string $message): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        return $this->errorResponse(
            $message,
            'ANALYTICS_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

