<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Http\Requests\Admin\Auth\ForgotPasswordRequest;
use App\Http\Requests\Admin\Auth\ResetPasswordRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Domain\Admin\Services\AdminAuthService;
use App\Exceptions\AdminAuthException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Auth Controller
 *
 * Handles admin authentication endpoints
 */
class AuthController extends BaseController
{
    /**
     * @var AdminAuthService
     */
    protected AdminAuthService $authService;

    /**
     * AuthController constructor.
     *
     * @param AdminAuthService $authService
     */
    public function __construct(AdminAuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Admin login
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->email,
                $request->password,
                $request->device_name ?? 'web'
            );

            return $this->successResponse([
                'admin' => new AdminResource($result['admin']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ], 'Login successful', 200);

        } catch (AdminAuthException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'AUTH_ERROR',
                $e->getStatusCode(),
                ['error_code' => $e->getErrorCode()]
            );
        }
    }

    /**
     * Admin logout
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $admin = $request->user();

            $this->authService->logout($admin);

            return $this->successResponse(
                null,
                'Logout successful'
            );

        } catch (\Exception $e) {
            return $this->errorResponse(
                'Logout failed',
                'AUTH_ERROR',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Get authenticated admin details
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        try {
            Log::info('admin.me.start', [
                'auth_guard' => config('admin-api.guard'),
                'admin_id' => optional($request->user())->id,
                'admin_class' => $request->user() ? get_class($request->user()) : null,
            ]);

            $admin = $request->user();

            $adminWithRole = $this->authService->me($admin);

            Log::info('admin.me.success', [
                'admin_id' => $adminWithRole->id ?? null,
            ]);

            return $this->successResponse(
                new AdminResource($adminWithRole),
                'Admin details retrieved successfully'
            );

        } catch (\Exception $e) {
            Log::error('admin.me.failed', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve admin details',
                'AUTH_ERROR',
                500,
                ['error' => $e->getMessage()]
            );
        }
    }

    /**
     * Send password reset link
     *
     * @param ForgotPasswordRequest $request
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->forgotPassword($request->email);

            return $this->successResponse(
                ['email' => $request->email],
                'Password reset link sent to your email'
            );

        } catch (AdminAuthException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'AUTH_ERROR',
                $e->getStatusCode(),
                ['error_code' => $e->getErrorCode()]
            );
        }
    }

    /**
     * Reset password with token
     *
     * @param ResetPasswordRequest $request
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->resetPassword(
                $request->email,
                $request->token,
                $request->password
            );

            return $this->successResponse(
                null,
                'Password reset successful. Please login with your new password.'
            );

        } catch (AdminAuthException $e) {
            return $this->errorResponse(
                $e->getMessage(),
                'AUTH_ERROR',
                $e->getStatusCode(),
                ['error_code' => $e->getErrorCode()]
            );
        }
    }
}

