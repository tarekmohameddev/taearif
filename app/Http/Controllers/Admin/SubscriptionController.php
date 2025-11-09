<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Admin\SubscriptionResource;
use App\Http\Resources\Admin\SubscriptionCollection;
use App\Domain\Billing\Services\SubscriptionService;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subscription Controller
 *
 * Handles subscription viewing endpoints (read-only)
 */
class SubscriptionController extends BaseController
{
    /**
     * @var SubscriptionService
     */
    protected SubscriptionService $subscriptionService;

    /**
     * SubscriptionController constructor.
     *
     * @param SubscriptionService $subscriptionService
     */
    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    /**
     * Get all subscriptions with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'status' => $request->input('status'), // active, expired, trial
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'expire_start' => $request->input('expire_start'),
                'expire_end' => $request->input('expire_end'),
                'package_id' => $request->input('package_id'),
                'user_id' => $request->input('user_id'),
                'order_by' => $request->input('order_by', 'created_at'),
                'order_dir' => $request->input('order_dir', 'desc'),
            ];

            $perPage = $request->input('per_page', 15);

            $subscriptions = $this->subscriptionService->getAllSubscriptions($filters, $perPage);

            return $this->successResponse(
                new SubscriptionCollection($subscriptions),
                'Subscriptions retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve subscriptions',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get subscription details by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->getSubscriptionById($id);

            return $this->successResponse(
                new SubscriptionResource($subscription),
                'Subscription details retrieved successfully'
            );

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve subscription details',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get subscription statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->subscriptionService->getStatistics();

            return $this->successResponse(
                $stats,
                'Subscription statistics retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve subscription statistics',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }
}

