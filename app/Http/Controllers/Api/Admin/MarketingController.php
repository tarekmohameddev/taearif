<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Marketing\Services\MarketingService;
use App\Http\Requests\Admin\Marketing\StoreWhatsAppTemplateRequest;
use App\Http\Requests\Admin\Marketing\UpdateWhatsAppTemplateRequest;
use App\Http\Resources\Admin\WhatsAppTemplateResource;
use App\Http\Resources\Admin\WhatsAppTemplateCollection;
use App\Http\Resources\Admin\MarketingOverviewResource;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Marketing Controller
 *
 * Handles marketing and automation endpoints
 */
class MarketingController extends BaseController
{
    /**
     * @var MarketingService
     */
    protected $marketingService;

    /**
     * MarketingController constructor.
     *
     * @param MarketingService $marketingService
     */
    public function __construct(MarketingService $marketingService)
    {
        $this->marketingService = $marketingService;
    }

    /**
     * Get marketing overview/dashboard
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            $overview = $this->marketingService->getMarketingOverview();

            return $this->successResponse(
                new MarketingOverviewResource($overview),
                'Marketing overview retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve marketing overview.');
        }
    }

    /**
     * Get marketing statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->marketingService->getStatistics();

            return $this->successResponse(
                $stats,
                'Marketing statistics retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve marketing statistics.');
        }
    }

    /**
     * Get paginated list of WhatsApp templates
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function templates(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'type',
                'language',
                'start_date',
                'end_date',
                'order_by',
                'order_dir',
            ]);

            $perPage = min($request->input('per_page', 20), 100);
            $templates = $this->marketingService->getTemplates($filters, $perPage);

            return $this->successResponse(
                new WhatsAppTemplateCollection($templates),
                'Templates retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve templates.');
        }
    }

    /**
     * Create a new WhatsApp template
     *
     * @param StoreWhatsAppTemplateRequest $request
     * @return JsonResponse
     */
    public function storeTemplate(StoreWhatsAppTemplateRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $template = $this->marketingService->createTemplate($data);

            return $this->successResponse(
                new WhatsAppTemplateResource($template),
                'Template created successfully',
                Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create template.');
        }
    }

    /**
     * Get WhatsApp template by ID
     *
     * @param int $id
     * @return JsonResponse
     */
    public function showTemplate(int $id): JsonResponse
    {
        try {
            $template = $this->marketingService->getTemplateById($id);

            return $this->successResponse(
                new WhatsAppTemplateResource($template),
                'Template retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve template.');
        }
    }

    /**
     * Update existing WhatsApp template
     *
     * @param UpdateWhatsAppTemplateRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateTemplate(UpdateWhatsAppTemplateRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $template = $this->marketingService->updateTemplate($id, $data);

            return $this->successResponse(
                new WhatsAppTemplateResource($template),
                'Template updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update template.');
        }
    }

    /**
     * Delete WhatsApp template
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroyTemplate(int $id): JsonResponse
    {
        try {
            $this->marketingService->deleteTemplate($id);

            return $this->successResponse(
                null,
                'Template deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete template.');
        }
    }

    /**
     * Toggle template status
     *
     * @param int $id
     * @return JsonResponse
     */
    public function toggleTemplateStatus(int $id): JsonResponse
    {
        try {
            $template = $this->marketingService->toggleTemplateStatus($id);

            return $this->successResponse(
                new WhatsAppTemplateResource($template),
                'Template status toggled successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to toggle template status.');
        }
    }

    /**
     * Get all automated messages settings
     *
     * @return JsonResponse
     */
    public function getAutomatedMessages(): JsonResponse
    {
        try {
            $messages = $this->marketingService->getAutomatedMessages();

            return $this->successResponse(
                $messages,
                'Automated messages retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve automated messages.');
        }
    }

    /**
     * Get automated message by type
     *
     * @param string $type
     * @return JsonResponse
     */
    public function getAutomatedMessage(string $type): JsonResponse
    {
        try {
            $message = $this->marketingService->getAutomatedMessageByType($type);

            if (!$message) {
                return $this->errorResponse(
                    'Automated message type not found',
                    'AUTOMATED_MESSAGE_NOT_FOUND',
                    Response::HTTP_NOT_FOUND
                );
            }

            return $this->successResponse(
                $message,
                'Automated message retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve automated message.');
        }
    }

    /**
     * Update automated message settings
     *
     * @param string $type
     * @param Request $request
     * @return JsonResponse
     */
    public function updateAutomatedMessage(string $type, Request $request): JsonResponse
    {
        try {
            $validTypes = ['welcome', 'subscription_expiring', 'subscription_expired', 'password_reset'];

            if (!in_array($type, $validTypes)) {
                return $this->errorResponse(
                    'Invalid automated message type',
                    'INVALID_AUTOMATED_MESSAGE_TYPE',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $message = $this->marketingService->updateAutomatedMessage($type, $request->all());

            return $this->successResponse(
                $message,
                'Automated message updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update automated message.');
        }
    }

    /**
     * Get WhatsApp settings
     *
     * @return JsonResponse
     */
    public function getWhatsAppSettings(): JsonResponse
    {
        try {
            $settings = $this->marketingService->getWhatsAppSettings();

            return $this->successResponse(
                $settings,
                'WhatsApp settings retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve WhatsApp settings.');
        }
    }

    /**
     * Update WhatsApp settings
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function updateWhatsAppSettings(Request $request): JsonResponse
    {
        try {
            $settings = $this->marketingService->updateWhatsAppSettings($request->all());

            return $this->successResponse(
                $settings,
                'WhatsApp settings updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update WhatsApp settings.');
        }
    }

    /**
     * Centralized error handling for marketing endpoints.
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
            return $this->errorResponse(
                $e->getMessage(),
                $e->getErrorCode(),
                $e->getCode() ?: Response::HTTP_UNPROCESSABLE_ENTITY,
                ['error_code' => $e->getErrorCode()]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'MARKETING_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

