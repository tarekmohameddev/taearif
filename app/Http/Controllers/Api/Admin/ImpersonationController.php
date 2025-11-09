<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Impersonation\StartImpersonationRequest;
use App\Http\Resources\Admin\ImpersonationResource;
use App\Http\Resources\Admin\ImpersonationCollection;
use App\Domain\Admin\Services\ImpersonationService;
use App\Exceptions\ImpersonationException;
use App\Exceptions\ResourceNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Impersonation Controller
 *
 * Handles admin impersonation endpoints
 */
class ImpersonationController extends BaseController
{
    /**
     * @var ImpersonationService
     */
    protected ImpersonationService $impersonationService;

    /**
     * ImpersonationController constructor.
     *
     * @param ImpersonationService $impersonationService
     */
    public function __construct(ImpersonationService $impersonationService)
    {
        $this->impersonationService = $impersonationService;
    }

    /**
     * Start impersonation session for a user.
     *
     * @param StartImpersonationRequest $request
     * @param int $userId
     * @return JsonResponse
     */
    public function start(StartImpersonationRequest $request, int $userId): JsonResponse
    {
        try {
            $admin = auth('admin-sanctum')->user();
            $adminId = $admin?->id;
            $validated = $request->validated();

            $result = $this->impersonationService->startImpersonation(
                $admin,
                $userId,
                $validated['reason'] ?? null,
                request()->getClientIp(),
                request()->userAgent()
            );

            return $this->successResponse([
                'impersonation' => new ImpersonationResource($result['impersonation']),
                'impersonation_token' => $result['token'],
                'token_type' => 'Bearer',
                'expires_at' => $result['expires_at']->toIso8601String(),
                'warning' => 'All actions performed during this session will be logged for audit purposes.',
            ], 'Impersonation session started successfully', 201);

        } catch (ImpersonationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->errorCode,
                $e->getCode() ?: 400,
                ['error_code' => $e->errorCode]
            );
        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            Log::error('Impersonation start failed', [
                'admin' => $adminId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to start impersonation session', 500);
        }
    }

    /**
     * End the current impersonation session.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function exit(Request $request): JsonResponse
    {
        try {
            $admin = auth('admin-sanctum')->user();
            $adminId = $admin?->id;

            // Optional: allow targeting a specific session via header
            $headerToken = $request->header('X-Impersonation-Token');
            $tokenId = null;
            if (!empty($headerToken)) {
                $tokenIdPart = explode('|', $headerToken)[0] ?? null;
                if ($tokenIdPart !== null && $tokenIdPart !== '') {
                    $tokenId = (int) $tokenIdPart;
                }
            }

            $impersonation = $this->impersonationService->endImpersonationForAdmin($admin, $tokenId);

            return $this->successResponse([
                'impersonation' => new ImpersonationResource($impersonation),
                'message' => 'Impersonation session ended successfully',
            ], 'Impersonation session ended');

        } catch (ImpersonationException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->errorCode,
                $e->getCode() ?: 400,
                ['error_code' => $e->errorCode]
            );
        } catch (\Exception $e) {
            Log::error('Impersonation exit failed', [
                'admin' => $adminId ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->errorResponse('Failed to end impersonation session', 500);
        }
    }

    /**
     * Get all active impersonation sessions.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $impersonations = $this->impersonationService->getActiveImpersonations();

            return $this->successResponse(
                new ImpersonationCollection($impersonations),
                'Active impersonation sessions retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve active impersonations', 500);
        }
    }

    /**
     * Get impersonation history with filters.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        try {
            $filters = [
                'admin_id' => $request->input('admin_id'),
                'user_id' => $request->input('user_id'),
                'status' => $request->input('status'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'active_only' => $request->boolean('active_only'),
                'ended_only' => $request->boolean('ended_only'),
                'order_by' => $request->input('order_by', 'started_at'),
                'order_dir' => $request->input('order_dir', 'desc'),
            ];

            $perPage = $request->input('per_page', 15);

            $history = $this->impersonationService->getImpersonationHistory($filters, $perPage);

            return $this->successResponse(
                new ImpersonationCollection($history),
                'Impersonation history retrieved successfully'
            );

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve impersonation history', 500);
        }
    }

    /**
     * Get impersonation history for a specific user.
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function userHistory(Request $request, int $userId): JsonResponse
    {
        try {
            $perPage = $request->input('per_page', 15);

            $history = $this->impersonationService->getUserImpersonationHistory($userId, $perPage);

            return $this->successResponse(
                new ImpersonationCollection($history),
                'User impersonation history retrieved successfully'
            );

        } catch (ResourceNotFoundException $e) {
            return $this->notFoundResponse($e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve user impersonation history', 500);
        }
    }
}

