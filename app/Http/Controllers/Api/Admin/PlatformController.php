<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Platform\UpdateSettingsRequest;
use App\Domain\Platform\Services\PlatformSettingsService;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Platform Controller
 *
 * Handles platform-wide system settings API endpoints
 */
class PlatformController extends BaseController
{
    /**
     * @var PlatformSettingsService
     */
    protected PlatformSettingsService $platformService;

    /**
     * PlatformController constructor.
     *
     * @param PlatformSettingsService $platformService
     */
    public function __construct(PlatformSettingsService $platformService)
    {
        $this->platformService = $platformService;
    }

    /**
     * Get all platform settings.
     * GET /api/v1/admin/platform/settings
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $settings = $this->platformService->getAllSettings();

            return $this->successResponse(
                $settings,
                'Settings retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve platform settings.');
        }
    }

    /**
     * Get settings by section.
     * GET /api/v1/admin/platform/settings/{section}
     *
     * @param string $section
     * @return JsonResponse
     */
    public function show(string $section): JsonResponse
    {
        try {
            $settings = $this->platformService->getSettings($section);

            return $this->successResponse(
                $settings,
                ucfirst($section) . ' settings retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve platform settings section.');
        }
    }

    /**
     * Update settings by section.
     * PUT /api/v1/admin/platform/settings/{section}
     *
     * @param UpdateSettingsRequest $request
     * @param string $section
     * @return JsonResponse
     */
    public function update(UpdateSettingsRequest $request, string $section): JsonResponse
    {
        try {
            $settings = $this->platformService->updateSettings($section, $request->validated());

            return $this->successResponse(
                $settings,
                ucfirst($section) . ' settings updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update platform settings.');
        }
    }

    /**
     * Centralized error handling for platform endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof BusinessLogicException) {
            $status = $e->getCode() ?: Response::HTTP_BAD_REQUEST;

            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $status,
                ['error_code' => $e->getErrorCode()]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'PLATFORM_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

