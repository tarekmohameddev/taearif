<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Admin\Billing\ApproveInvoiceRequest;
use App\Http\Requests\Admin\Billing\RejectInvoiceRequest;
use App\Http\Resources\Admin\Billing\InvoiceResource;
use App\Http\Resources\Admin\Billing\InvoiceCollection;
use App\Http\Resources\Admin\Billing\InvoiceStatisticsResource;
use App\Domain\Billing\Services\InvoiceService;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Billing Controller
 *
 * Handles invoice/billing management API endpoints
 */
class BillingController extends BaseController
{
    /**
     * @var InvoiceService
     */
    protected InvoiceService $invoiceService;

    /**
     * BillingController constructor.
     *
     * @param InvoiceService $invoiceService
     */
    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Display a listing of invoices.
     * GET /api/v1/admin/billing/invoices
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'payment_method', 'user_id', 'package_id',
                'is_trial', 'start_date', 'end_date', 'search',
                'order_by', 'order_dir'
            ]);
            $perPage = $request->query('per_page', 20);

            $invoices = $this->invoiceService->getAllInvoices($filters, $perPage);

            return $this->successResponse(
                new InvoiceCollection($invoices),
                'Invoices retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve invoices.');
        }
    }

    /**
     * Display the specified invoice.
     * GET /api/v1/admin/billing/invoices/{id}
     *
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->getInvoiceById($id);

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Invoice retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve invoice.');
        }
    }

    /**
     * Approve invoice and activate subscription.
     * POST /api/v1/admin/billing/invoices/{id}/approve
     *
     * @param int $id
     * @param ApproveInvoiceRequest $request
     * @return JsonResponse
     */
    public function approve(int $id, ApproveInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->approveInvoice($id, $request->validated());

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Invoice approved successfully. Subscription activated.',
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to approve invoice.');
        }
    }

    /**
     * Reject invoice.
     * POST /api/v1/admin/billing/invoices/{id}/reject
     *
     * @param int $id
     * @param RejectInvoiceRequest $request
     * @return JsonResponse
     */
    public function reject(int $id, RejectInvoiceRequest $request): JsonResponse
    {
        try {
            $invoice = $this->invoiceService->rejectInvoice($id, $request->validated());

            return $this->successResponse(
                new InvoiceResource($invoice),
                'Invoice rejected successfully.',
                Response::HTTP_OK
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to reject invoice.');
        }
    }

    /**
     * Get invoice statistics.
     * GET /api/v1/admin/billing/statistics
     *
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $statistics = $this->invoiceService->getStatistics();

            return $this->successResponse(
                new InvoiceStatisticsResource($statistics),
                'Statistics retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve billing statistics.');
        }
    }

    /**
     * Get revenue for date range.
     * GET /api/v1/admin/billing/revenue
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function revenue(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'from' => ['required', 'date'],
                'to' => ['required', 'date', 'after_or_equal:from'],
            ]);

            $revenue = $this->invoiceService->getRevenue(
                $request->input('from'),
                $request->input('to')
            );

            return $this->successResponse(
                $revenue,
                'Revenue retrieved successfully.'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve revenue.');
        }
    }

    /**
     * Centralized error handling for billing endpoints.
     */
    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                404,
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            return $this->errorResponse(
                $e->getMessage(),
                $e->getCode() ?: Response::HTTP_BAD_REQUEST,
                $e->getCode() ?: Response::HTTP_BAD_REQUEST,
                [
                    'error_code' => $e->getErrorCode(),
                ]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'BILLING_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}

