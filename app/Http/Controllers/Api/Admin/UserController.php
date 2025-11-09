<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\User\StoreUserRequest;
use App\Http\Requests\Admin\User\UpdateUserRequest;
use App\Http\Requests\Admin\User\UpdatePasswordRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Resources\Admin\UserCollection;
use App\Http\Resources\Admin\User\UserActivityCollection;
use App\Http\Resources\Admin\Billing\InvoiceCollection;
use App\Http\Requests\Admin\User\SendWhatsAppRequest;
use App\Http\Requests\Admin\User\PauseUserRequest;
use App\Http\Requests\Admin\User\ChangePlanRequest;
use App\Http\Requests\Admin\User\CancelSubscriptionRequest;
use App\Domain\User\Services\UserManagementService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * User API Controller
 *
 * Handles all tenant user management API endpoints
 */
class UserController extends BaseController
{
    /**
     * @var UserManagementService
     */
    protected UserManagementService $userManagementService;

    /**
     * UserController constructor.
     *
     * @param UserManagementService $userManagementService
     */
    public function __construct(UserManagementService $userManagementService)
    {
        $this->userManagementService = $userManagementService;
    }

    /**
     * Display a listing of the users.
     * GET /api/v1/admin/users
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'status', 'featured', 'plan', 'referred_by',
                'start_date', 'end_date', 'subscription_status', 'sort'
            ]);
            $perPage = $request->query('per_page', 20);

            $users = $this->userManagementService->listUsers($filters, $perPage);

            return $this->successResponse(
                new UserCollection($users),
                'Users retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve users.');
        }
    }

    /**
     * Store a newly created user in storage.
     * POST /api/v1/admin/users
     *
     * @param StoreUserRequest $request
     * @return JsonResponse
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userManagementService->createUser($request->validated());

            return $this->successResponse(
                new UserResource($user),
                'User created successfully.',
                Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create user.');
        }
    }

    /**
     * Display the specified user.
     * GET /api/v1/admin/users/{id}
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        try {
            $user = $this->userManagementService->getUser($userId);

            return $this->successResponse(
                new UserResource($user),
                'User retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve user.');
        }
    }

    /**
     * Display invoices/subscriptions for a user.
     * GET /api/v1/admin/users/{id}/invoices
     */
    public function invoices(Request $request, int $userId): JsonResponse
    {
        try {
            $filters = $request->only(['status', 'from_date', 'to_date', 'plan_id']);
            $perPage = (int) $request->query('per_page', 20);

            $invoices = $this->userManagementService->getUserInvoices($userId, $filters, $perPage);

            return $this->successResponse(
                new InvoiceCollection($invoices),
                'User invoices retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve user invoices.');
        }
    }

    /**
     * Display the activity log for a user.
     * GET /api/v1/admin/users/{id}/activity
     */
    public function activity(Request $request, int $userId): JsonResponse
    {
        try {
            $filters = $request->only(['action', 'from_date', 'to_date', 'admin_id']);
            $perPage = (int) $request->query('per_page', 20);

            $activities = $this->userManagementService->getActivityLog($userId, $filters, $perPage);

            return $this->successResponse(
                new UserActivityCollection($activities),
                'Activity log retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve user activity.');
        }
    }

    /**
     * Send WhatsApp message to a user.
     * POST /api/v1/admin/users/{id}/send-whatsapp
     */
    public function sendWhatsApp(SendWhatsAppRequest $request, int $userId): JsonResponse
    {
        try {
            $data = $request->validated();

            $result = $this->userManagementService->sendWhatsAppMessage(
                $userId,
                $data['message'],
                $data['template_name'] ?? null,
                $data['template_variables'] ?? []
            );

            return $this->successResponse(
                $result,
                'WhatsApp message sent successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to send WhatsApp message.');
        }
    }

    /**
     * Pause user account.
     * POST /api/v1/admin/users/{id}/pause
     */
    public function pause(PauseUserRequest $request, int $userId): JsonResponse
    {
        try {
            $data = $request->validated();

            $updatedUser = $this->userManagementService->pauseUser(
                $userId,
                $data['reason'],
                $data['admin_notes'] ?? null
            );

            return $this->successResponse(
                new UserResource($updatedUser),
                'User account paused successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to pause user.');
        }
    }

    /**
     * Resume paused user account.
     * POST /api/v1/admin/users/{id}/resume
     */
    public function resume(int $userId): JsonResponse
    {
        try {
            $updatedUser = $this->userManagementService->resumeUser($userId);

            return $this->successResponse(
                new UserResource($updatedUser),
                'User account resumed successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to resume user.');
        }
    }

    /**
     * Change user's subscription plan.
     * POST /api/v1/admin/users/{id}/change-plan
     */
    public function changePlan(ChangePlanRequest $request, int $userId): JsonResponse
    {
        try {
            $data = $request->validated();

            $updatedUser = $this->userManagementService->changePlan(
                $userId,
                (int) $data['new_plan_id'],
                $data['change_type'],
                $data['admin_notes'] ?? null
            );

            return $this->successResponse(
                new UserResource($updatedUser),
                'User plan changed successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to change user plan.');
        }
    }

    /**
     * Cancel user's subscription.
     * POST /api/v1/admin/users/{id}/cancel-subscription
     */
    public function cancelSubscription(CancelSubscriptionRequest $request, int $userId): JsonResponse
    {
        try {
            $data = $request->validated();

            $updatedUser = $this->userManagementService->cancelSubscription(
                $userId,
                $data['cancel_type'],
                $data['reason'],
                $data['admin_notes'] ?? null
            );

            return $this->successResponse(
                new UserResource($updatedUser),
                'User subscription cancellation processed successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to cancel subscription.');
        }
    }

    /**
     * Update the specified user in storage.
     * PUT /api/v1/admin/users/{id}
     *
     * @param UpdateUserRequest $request
     * @param int $userId
     * @return JsonResponse
     */
    public function update(UpdateUserRequest $request, int $userId): JsonResponse
    {
        try {
            $user = $this->userManagementService->updateUser($userId, $request->validated());

            return $this->successResponse(
                new UserResource($user),
                'User updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update user.');
        }
    }

    /**
     * Remove the specified user from storage.
     * DELETE /api/v1/admin/users/{id}
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function destroy(int $userId): JsonResponse
    {
        try {
            $this->userManagementService->deleteUser($userId);

            return $this->noContentResponse();
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete user.');
        }
    }

    /**
     * Update the specified user's password.
     * PUT /api/v1/admin/users/{id}/password
     *
     * @param UpdatePasswordRequest $request
     * @param int $userId
     * @return JsonResponse
     */
    public function updatePassword(UpdatePasswordRequest $request, int $userId): JsonResponse
    {
        try {
            $data = $request->validated();

            $user = $this->userManagementService->updatePassword($userId, $data['password']);

            return $this->successResponse(
                new UserResource($user),
                'User password updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update user password.');
        }
    }

    /**
     * Toggle the ban status of the specified user.
     * POST /api/v1/admin/users/{id}/ban
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function toggleBan(int $userId): JsonResponse
    {
        try {
            $updatedUser = $this->userManagementService->toggleBan($userId);

            return $this->successResponse(
                new UserResource($updatedUser),
                'User ban status updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update user ban status.');
        }
    }

    /**
     * Toggle the featured status of the specified user.
     * POST /api/v1/admin/users/{id}/featured
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function toggleFeatured(int $userId): JsonResponse
    {
        try {
            $updatedUser = $this->userManagementService->toggleFeatured($userId);

            return $this->successResponse(
                new UserResource($updatedUser),
                'User featured status updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update user featured status.');
        }
    }

    /**
     * Centralized error handling for user endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND,
                Response::HTTP_NOT_FOUND,
                ['error_code' => 'NOT_FOUND']
            );
        }

        if ($e instanceof BusinessLogicException) {
            $status = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            $errorCode = $e->getErrorCode() ?? 'USER_BUSINESS_ERROR';

            return $this->errorResponse(
                $e->getMessage(),
                $status,
                $status,
                ['error_code' => $errorCode]
            );
        }

        report($e);

        return $this->errorResponse(
            $fallbackMessage,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            Response::HTTP_INTERNAL_SERVER_ERROR,
            [
                'error_code' => 'USER_ERROR',
                'error' => $e->getMessage(),
            ]
        );
    }
}

