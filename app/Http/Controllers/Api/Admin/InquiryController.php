<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Inquiry\StoreInquiryRequest;
use App\Http\Requests\Admin\Inquiry\UpdateInquiryRequest;
use App\Http\Resources\Admin\Inquiry\InquiryResource;
use App\Http\Resources\Admin\Inquiry\InquiryCollection;
use App\Domain\Support\Services\InquiryService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Inquiry Controller
 *
 * Handles customer inquiry/support ticket API endpoints
 */
class InquiryController extends BaseController
{
    /**
     * @var InquiryService
     */
    protected InquiryService $inquiryService;

    /**
     * InquiryController constructor.
     *
     * @param InquiryService $inquiryService
     */
    public function __construct(InquiryService $inquiryService)
    {
        $this->inquiryService = $inquiryService;
    }

    /**
     * Display a listing of inquiries.
     * GET /api/v1/admin/inquiries
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'user_id', 'customer_id', 'inquiry_type', 
                'property_type', 'min_budget', 'max_budget', 'location',
                'from_date', 'to_date', 'status', 'assigned_to',
                'order_by', 'order_dir'
            ]);
            $perPage = $request->query('per_page', 20);

            $inquiries = $this->inquiryService->getAllInquiries($filters, $perPage);

            return $this->successResponse(
                new InquiryCollection($inquiries),
                'Inquiries retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve inquiries.');
        }
    }

    /**
     * Store a newly created inquiry.
     * POST /api/v1/admin/inquiries
     *
     * @param StoreInquiryRequest $request
     * @return JsonResponse
     */
    public function store(StoreInquiryRequest $request): JsonResponse
    {
        try {
            $inquiry = $this->inquiryService->createInquiry($request->validated());

            return $this->successResponse(
                new InquiryResource($inquiry),
                'Inquiry created successfully.',
                Response::HTTP_CREATED
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create inquiry.');
        }
    }

    /**
     * Display the specified inquiry.
     * GET /api/v1/admin/inquiries/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $inquiry = $this->inquiryService->getInquiryById($id);

            return $this->successResponse(
                new InquiryResource($inquiry),
                'Inquiry retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve inquiry.');
        }
    }

    /**
     * Update the specified inquiry.
     * PUT /api/v1/admin/inquiries/{id}
     *
     * @param UpdateInquiryRequest $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(UpdateInquiryRequest $request, int $id): JsonResponse
    {
        try {
            $inquiry = $this->inquiryService->updateInquiry($id, $request->validated());

            return $this->successResponse(
                new InquiryResource($inquiry),
                'Inquiry updated successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update inquiry.');
        }
    }

    /**
     * Remove the specified inquiry.
     * DELETE /api/v1/admin/inquiries/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->inquiryService->deleteInquiry($id);

            return $this->successResponse(
                null,
                'Inquiry deleted successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete inquiry.');
        }
    }

    /**
     * Get inquiry statistics.
     * GET /api/v1/admin/inquiries/statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->inquiryService->getStatistics();

            return $this->successResponse(
                $statistics,
                'Statistics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve inquiry statistics.');
        }
    }

    /**
     * Get inquiries by tenant.
     * GET /api/v1/admin/inquiries/tenant/{userId}
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function byTenant(Request $request, int $userId): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'inquiry_type', 'property_type', 
                'min_budget', 'max_budget', 'location',
                'from_date', 'to_date', 'order_by', 'order_dir'
            ]);
            $perPage = $request->query('per_page', 20);

            $inquiries = $this->inquiryService->getInquiriesByTenant($userId, $filters, $perPage);

            return $this->successResponse(
                new InquiryCollection($inquiries),
                'Inquiries retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve tenant inquiries.');
        }
    }

    /**
     * Get inquiries by customer.
     * GET /api/v1/admin/inquiries/customer/{customerId}
     *
     * @param Request $request
     * @param int $customerId
     * @return JsonResponse
     */
    public function byCustomer(Request $request, int $customerId): JsonResponse
    {
        try {
            $filters = $request->only([
                'search', 'inquiry_type', 'property_type',
                'from_date', 'to_date', 'order_by', 'order_dir'
            ]);
            $perPage = $request->query('per_page', 20);

            $inquiries = $this->inquiryService->getInquiriesByCustomer($customerId, $filters, $perPage);

            return $this->successResponse(
                new InquiryCollection($inquiries),
                'Inquiries retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve customer inquiries.');
        }
    }

    /**
     * Bulk delete inquiries.
     * POST /api/v1/admin/inquiries/bulk-delete
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'ids' => ['required', 'array', 'min:1'],
                'ids.*' => ['required', 'integer', 'exists:api_customer_inquiry,id'],
            ]);

            $count = $this->inquiryService->bulkDeleteInquiries($request->ids);

            return $this->successResponse(
                ['deleted_count' => $count],
                "{$count} inquiries deleted successfully."
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to bulk delete inquiries.');
        }
    }

    /**
     * Export inquiries.
     * GET /api/v1/admin/inquiries/export
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'user_id', 'customer_id', 'inquiry_type', 
                'property_type', 'from_date', 'to_date'
            ]);

            $data = $this->inquiryService->exportInquiries($filters);

            return $this->successResponse(
                ['data' => $data, 'count' => count($data)],
                'Inquiries exported successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to export inquiries.');
        }
    }

    /**
     * Centralized error handling for inquiry endpoints.
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
            'INQUIRY_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

