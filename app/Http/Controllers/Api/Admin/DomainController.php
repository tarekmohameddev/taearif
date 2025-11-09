<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Domain\Domain\Services\CustomDomainService;
use App\Http\Requests\Admin\Domain\StoreDomainRequest;
use App\Http\Requests\Admin\Domain\UpdateDomainRequest;
use App\Http\Resources\Admin\CustomDomainResource;
use App\Http\Resources\Admin\CustomDomainCollection;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Domain Controller
 * 
 * Handles custom domain management endpoints
 */
class DomainController extends BaseController
{
    /**
     * @var CustomDomainService
     */
    protected $domainService;

    /**
     * DomainController constructor.
     *
     * @param CustomDomainService $domainService
     */
    public function __construct(CustomDomainService $domainService)
    {
        $this->domainService = $domainService;
    }

    /**
     * Get paginated list of domains
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search',
                'status',
                'user_id',
                'start_date',
                'end_date',
                'order_by',
                'order_dir',
            ]);

            $perPage = min($request->input('per_page', 20), 100);
            $domains = $this->domainService->getDomains($filters, $perPage);

            return $this->successResponse(
                new CustomDomainCollection($domains),
                'Domains retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve domains.');
        }
    }

    /**
     * Create a new custom domain
     * 
     * @param StoreDomainRequest $request
     * @return JsonResponse
     */
    public function store(StoreDomainRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();
            $domain = $this->domainService->createDomain($data);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain created successfully',
                201
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create domain.');
        }
    }

    /**
     * Get domain by ID
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->getDomainById($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve domain.');
        }
    }

    /**
     * Update existing domain
     * 
     * @param UpdateDomainRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateDomainRequest $request, int $id): JsonResponse
    {
        try {
            $data = $request->validated();
            $domain = $this->domainService->updateDomain($id, $data);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain updated successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update domain.');
        }
    }

    /**
     * Delete domain
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->domainService->deleteDomain($id);

            return $this->successResponse(
                null,
                'Domain deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete domain.');
        }
    }

    /**
     * Approve domain request
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function approve(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->approveDomain($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain approved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to approve domain.');
        }
    }

    /**
     * Reject domain request
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function reject(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->rejectDomain($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain rejected successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to reject domain.');
        }
    }

    /**
     * Toggle domain status
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $domain = $this->domainService->toggleStatus($id);

            return $this->successResponse(
                new CustomDomainResource($domain),
                'Domain status toggled successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to toggle domain status.');
        }
    }

    /**
     * Get domain statistics
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = $this->domainService->getDomainStatistics();

            return $this->successResponse(
                $stats,
                'Domain statistics retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve domain statistics.');
        }
    }

    /**
     * Centralized error handling for domain endpoints.
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
            'DOMAIN_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

