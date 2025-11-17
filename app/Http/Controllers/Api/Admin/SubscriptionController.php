<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Subscription\ChangeSubscriptionPlanRequest;
use App\Http\Resources\Admin\SubscriptionResource;
use App\Http\Resources\Admin\SubscriptionCollection;
use App\Domain\Billing\Models\Plan;
use App\Domain\Billing\Services\SubscriptionService;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Validation\ValidationException;
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
                'search' => $request->input('search'),
                'order_by' => $request->input('order_by', 'created_at'),
                'order_dir' => $request->input('order_dir', 'desc'),
            ];

            $perPage = $request->input('per_page', 15);

            $subscriptions = $this->subscriptionService->getAllSubscriptions($filters, $perPage);
            $plans = Plan::query()
                ->select(['id', 'title', 'slug'])
                ->orderBy('title')
                ->get()
                ->keyBy('id')
                ->map(fn ($plan) => [
                    'title' => $plan->title,
                    'plan_slug' => $plan->slug,
                ]);

            return $this->successResponse(
                [
                    'subscriptions' => new SubscriptionCollection($subscriptions),
                    'all_plans' => $plans,
                ],
                'Subscriptions retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve subscriptions',
                'INTERNAL_ERROR',
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
                'INTERNAL_ERROR',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get latest subscription by user ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function showByUser(int $userId): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->getLatestSubscriptionForUser($userId);

            if (!$subscription) {
                return $this->notFoundResponse('Subscription not found for this user');
            }

            return $this->successResponse(
                new SubscriptionResource($subscription),
                'Subscription details retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to retrieve subscription details',
                'INTERNAL_ERROR',
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
                'INTERNAL_ERROR',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Change subscription plan for the latest membership.
     *
     * @param Request $request
     * @param int $subscriptionId
     * @return JsonResponse
     */
    public function changePlan(ChangeSubscriptionPlanRequest $request, int $subscriptionId): JsonResponse
    {
        try {
            $subscription = $this->subscriptionService->changePlan(
                $subscriptionId,
                $request->validated()
            );

            return $this->successResponse(
                new SubscriptionResource($subscription),
                'Subscription plan changed successfully'
            );
        } catch (ValidationException $e) {
            return $this->errorResponse(
                'Invalid request data',
                'VALIDATION_ERROR',
                422,
                $e->errors()
            );
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Failed to change subscription plan',
                'INTERNAL_ERROR',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }
}

