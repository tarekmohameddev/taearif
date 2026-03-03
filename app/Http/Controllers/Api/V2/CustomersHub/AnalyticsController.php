<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\AnalyticsService;
use App\Http\Requests\Api\V2\CustomersHub\AnalyticsIndexRequest;
use App\Http\Requests\Api\V2\CustomersHub\AnalyticsTrendsRequest;
use App\Http\Requests\Api\V2\CustomersHub\AnalyticsSourcesRequest;
use App\Http\Requests\Api\V2\CustomersHub\AnalyticsPerformanceRequest;

/**
 * AnalyticsController
 * 
 * API endpoints for Customers Hub Analytics dashboard.
 * 
 * Routes:
 * - POST /api/v2/customers-hub/analytics
 */
class AnalyticsController extends ApiController
{
    private AnalyticsService $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * POST /api/v2/customers-hub/analytics
     * 
     * Get analytics data with various metrics.
     */
    public function index(AnalyticsIndexRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $action = $validated['action'] ?? 'metrics';
        $timeRange = $validated['timeRange'] ?? ['timeRange' => 'last30days'];
        if (isset($timeRange['range']) && !isset($timeRange['timeRange'])) {
            $timeRange['timeRange'] = $timeRange['range'];
        }
        $interval = $validated['interval'] ?? 'day';
        $filters = $validated['filters'] ?? [];

        $response = [];

        switch ($action) {
            case 'metrics':
                $response['keyMetrics'] = $this->analyticsService->getKeyMetrics($userId, $timeRange);
                break;

            case 'distributions':
                $response['stageDistribution'] = $this->analyticsService->getStageDistribution($userId, $timeRange);
                $response['sourceDistribution'] = $this->analyticsService->getSourceDistribution($userId, $timeRange);
                break;

            case 'time_series':
                $response['timeSeries'] = $this->analyticsService->getTimeSeries($userId, $timeRange, $interval);
                break;

            case 'activity':
                $response['activityMetrics'] = $this->analyticsService->getActivityMetrics($userId, $timeRange);
                break;

            case 'pipeline_health':
                $pipelineResult = $this->analyticsService->getPipelineHealth($userId, $timeRange, $filters);
                $response['pipelineHealth'] = $pipelineResult['pipelineHealth'];
                $response['timeRange'] = $pipelineResult['timeRange'];
                break;

            default:
                // Return all data
                $response = [
                    'keyMetrics' => $this->analyticsService->getKeyMetrics($userId, $timeRange),
                    'stageDistribution' => $this->analyticsService->getStageDistribution($userId, $timeRange),
                    'sourceDistribution' => $this->analyticsService->getSourceDistribution($userId, $timeRange),
                    'timeSeries' => $this->analyticsService->getTimeSeries($userId, $timeRange, $interval),
                    'activityMetrics' => $this->analyticsService->getActivityMetrics($userId, $timeRange),
                ];
        }

        return $this->success($response);
    }

    /**
     * POST /api/v2/customers-hub/analytics/trends
     * 
     * Get analytics trends data.
     */
    public function trends(AnalyticsTrendsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $timeRange = $validated['timeRange'] ?? ['timeRange' => 'last30days'];
        $metrics = $validated['metrics'] ?? ['newCustomers', 'completedTasks', 'appointments'];

        $trends = $this->analyticsService->getTrends($userId, $timeRange, $metrics);

        return $this->success([
            'trends' => $trends,
            'timeRange' => $this->analyticsService->getTimeRangeDates($timeRange),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/analytics/sources
     * 
     * Get analytics by sources.
     */
    public function sources(AnalyticsSourcesRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $timeRange = $validated['timeRange'] ?? ['timeRange' => 'last30days'];

        $sources = $this->analyticsService->getSources($userId, $timeRange);

        return $this->success([
            'sources' => $sources,
            'timeRange' => $this->analyticsService->getTimeRangeDates($timeRange),
        ]);
    }

    /**
     * POST /api/v2/customers-hub/analytics/performance
     * 
     * Get performance analytics.
     */
    public function performance(AnalyticsPerformanceRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $userId = $this->getTenantUserId($request);
        $timeRange = $validated['timeRange'] ?? ['timeRange' => 'last30days'];

        $performance = $this->analyticsService->getPerformance($userId, $timeRange);

        return $this->success([
            'employees' => $performance,
            'timeRange' => $this->analyticsService->getTimeRangeDates($timeRange),
        ]);
    }

    /**
     * Get the tenant user ID from request.
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }
}
