<?php

namespace App\Http\Controllers\Api\V2\CustomersHub;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Api\ApiController;
use App\Domain\CustomersHub\Services\AnalyticsService;

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
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'nullable|in:metrics,distributions,time_series,activity',
            'timeRange' => 'nullable|array',
            'timeRange.timeRange' => 'nullable|in:today,yesterday,last7days,last30days,thisMonth,lastMonth,thisQuarter,lastQuarter,thisYear,lastYear,custom',
            'timeRange.customStartDate' => 'nullable|date',
            'timeRange.customEndDate' => 'nullable|date',
            'interval' => 'nullable|in:day,week,month',
        ]);

        $userId = $this->getTenantUserId($request);
        $action = $validated['action'] ?? 'metrics';
        $timeRange = $validated['timeRange'] ?? ['timeRange' => 'last30days'];
        $interval = $validated['interval'] ?? 'day';

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
     * Get the tenant user ID from request.
     */
    private function getTenantUserId(Request $request): int
    {
        $user = $request->user();
        return method_exists($user, 'tenantOwnerId') ? $user->tenantOwnerId() : $user->id;
    }
}
