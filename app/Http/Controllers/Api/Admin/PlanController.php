<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Plan\StorePlanRequest;
use App\Http\Requests\Admin\Plan\UpdatePlanRequest;
use App\Http\Resources\Admin\PlanResource;
use App\Http\Resources\Admin\PlanCollection;
use App\Domain\Billing\Services\PlanService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Plan Controller
 *
 * Handles plan/package management endpoints
 */
class PlanController extends BaseController
{
    /**
     * @var PlanService
     */
    protected PlanService $planService;

    /**
     * PlanController constructor.
     *
     * @param PlanService $planService
     */
    public function __construct(PlanService $planService)
    {
        $this->planService = $planService;
    }

    /**
     * Get all plans with filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'is_active' => $request->input('is_active'),
                'featured' => $request->input('featured'),
                'is_trial' => $request->input('is_trial'),
                'order_by' => $request->input('order_by', 'serial_number'),
                'order_dir' => $request->input('order_dir', 'asc'),
            ];

            $perPage = (int) $request->input('per_page', 15);

            $plans = $this->planService->getAllPlans($filters, $perPage);

            return $this->successResponse(
                new PlanCollection($plans),
                'Plans retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve plans.');
        }
    }

    /**
     * Create new plan
     *
     * @param StorePlanRequest $request
     * @return JsonResponse
     */
    public function store(StorePlanRequest $request): JsonResponse
    {
        try {
            $plan = $this->planService->createPlan($request->validated());

            return $this->successResponse(
                new PlanResource($plan),
                'Plan created successfully',
                Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create plan.');
        }
    }

    /**
     * Get plan details by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $plan = $this->planService->getPlanById($id);

            return $this->successResponse(
                new PlanResource($plan),
                'Plan details retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve plan details.');
        }
    }

    /**
     * Update plan
     *
     * @param UpdatePlanRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdatePlanRequest $request, int $id): JsonResponse
    {
        try {
            $plan = $this->planService->updatePlan($id, $request->validated());

            return $this->successResponse(
                new PlanResource($plan),
                'Plan updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update plan.');
        }
    }

    /**
     * Delete plan
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $plan = $this->planService->deletePlan($id);

            return $this->successResponse(
                [
                    'plan_id' => $plan->id,
                    'plan_title' => $plan->title,
                ],
                sprintf('Plan #%d deleted successfully', $plan->id)
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete plan.');
        }
    }

    /**
     * Toggle plan active status
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleActive(int $id): JsonResponse
    {
        try {
            $plan = $this->planService->toggleActive($id);

            $status = $plan->isActive() ? 'activated' : 'deactivated';

            return $this->successResponse(
                new PlanResource($plan),
                "Plan has been {$status} successfully"
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update plan status.');
        }
    }

    /**
     * Toggle plan featured status
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleFeatured(int $id): JsonResponse
    {
        try {
            $plan = $this->planService->toggleFeatured($id);

            $status = $plan->isFeatured() ? 'featured' : 'unfeatured';

            return $this->successResponse(
                new PlanResource($plan),
                "Plan has been marked as {$status}"
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update featured status.');
        }
    }

    /**
     * Centralized error handling for plan endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            $status = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            $errorCode = $e->getErrorCode();

            return $this->errorResponse(
                $e->getMessage(),
                $errorCode,
                $status,
                ['error_code' => $errorCode]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'PLAN_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

