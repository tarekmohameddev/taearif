<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Admin\DashboardResource;
use App\Domain\Analytics\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

/**
 * Dashboard API Controller
 *
 * Handles dashboard metrics and analytics API endpoints
 */
class DashboardController extends BaseController
{
    /**
     * @var DashboardService
     */
    protected DashboardService $dashboardService;

    /**
     * DashboardController constructor.
     *
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    /**
     * Get dashboard metrics
     * GET /api/v1/admin/dashboard
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Validate inputs
            $metric = $request->input('metric'); // null, 'properties', 'revenue', 'users', 'subscriptions'
            $period = (int) $request->input('period', 30); // days

            // Validate period
            if ($period < 1 || $period > 365) {
                return $this->errorResponse(
                    'Period must be between 1 and 365 days',
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Validate metric if provided
            $validMetrics = ['properties', 'revenue', 'users', 'subscriptions'];
            if ($metric && !in_array($metric, $validMetrics)) {
                return $this->errorResponse(
                    'Invalid metric. Valid values: ' . implode(', ', $validMetrics),
                    Response::HTTP_BAD_REQUEST
                );
            }

            // Get metrics
            $metrics = $this->dashboardService->getDashboardMetrics($metric, $period);

            return $this->successResponse(
                new DashboardResource($metrics),
                'Dashboard metrics retrieved successfully'
            );

        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to retrieve dashboard metrics',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get quick stats summary
     * GET /api/v1/admin/dashboard/quick-stats
     *
     * @return JsonResponse
     */
    public function quickStats(): JsonResponse
    {
        try {
            $stats = $this->dashboardService->getQuickStats();

            return $this->successResponse(
                $stats,
                'Quick stats retrieved successfully'
            );

        } catch (Throwable $e) {
            return $this->errorResponse(
                'Failed to retrieve quick stats',
                Response::HTTP_INTERNAL_SERVER_ERROR,
                ['error' => $e->getMessage()]
            );
        }
    }
}

