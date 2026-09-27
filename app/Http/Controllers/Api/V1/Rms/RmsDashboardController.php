<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Services\Rms\DashboardService;
use App\Services\Rms\DashboardReadService;
use App\Http\Requests\Api\V1\Rms\RmsDashboardPaginationRequest;
use App\Http\Requests\Api\V1\Rms\RmsDashboardPaymentsDueRequest;
use App\Http\Requests\Api\V1\Rms\RmsDashboardOverduePaymentsRequest;
use App\Http\Requests\Api\V1\Rms\RmsDashboardExpiringContractsRequest;
use App\Http\Requests\Api\V1\Rms\RmsDashboardMaintenanceRequest;
use App\Http\Requests\Api\V1\Rms\RmsDashboardIndexRequest;
use App\Http\Requests\Api\V1\Rms\PaymentsCollectionsRequest;
use App\Http\Requests\Api\V1\Rms\PaymentsDueRequest;

class RmsDashboardController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $dashboardService;
    protected $dashboardReadService;

    public function __construct(DashboardService $dashboardService, DashboardReadService $dashboardReadService)
    {
        $this->dashboardService = $dashboardService;
        $this->dashboardReadService = $dashboardReadService;
    }

    public function stats()
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->stats($this->getUserId())), 'retrieve dashboard stats');
    }

    public function ongoingRentals(RmsDashboardPaginationRequest $request)
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->ongoingRentals($this->getUserId(), $request->validated())), 'retrieve ongoing rentals');
    }

    public function paymentsDueSummary()
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->paymentsDueSummary($this->getUserId())), 'retrieve payments due summary');
    }

    public function paymentsDueList(RmsDashboardPaymentsDueRequest $request)
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->paymentsDueList($this->getUserId(), $request->validated())), 'retrieve payments due');
    }

    public function overduePaymentsSummary()
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->overduePaymentsSummary($this->getUserId())), 'retrieve overdue payments summary');
    }

    public function overduePayments(RmsDashboardOverduePaymentsRequest $request)
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->overduePayments($this->getUserId(), $request->validated())), 'retrieve overdue payments');
    }

    public function expiringContracts(RmsDashboardExpiringContractsRequest $request)
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->expiringContracts($this->getUserId(), $request->validated())), 'retrieve expiring contracts');
    }

    public function maintenance(RmsDashboardMaintenanceRequest $request)
    {
        return $this->executeWithExceptionHandling(fn () => $this->success($this->dashboardReadService->maintenance($this->getUserId(), $request->validated())), 'retrieve maintenance');
    }

    public function index(RmsDashboardIndexRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $range = (int) $request->get('range', 7); // 7 or 30 days
            $validated = $request->validated();

            // Build filters array for collections and payments due
            $filters = $this->buildFilters($request);

            $data = $this->dashboardService->getDashboardData($this->getUserId(), $range, $filters);

            return $this->success($data);
        }, 'retrieve dashboard data');
    }

    /**
     * Get filtered payments collections data
     * Separate endpoint for payments collections with filters
     */
    public function paymentsCollections(PaymentsCollectionsRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $validated = $request->validated();
            $filters = [
                'period' => $validated['period'] ?? null,
                'from_date' => $validated['from_date'] ?? null,
                'to_date' => $validated['to_date'] ?? null,
            ];

            $data = $this->dashboardService->getFilteredPaymentsCollections($this->getUserId(), $filters);

            return $this->success($data);
        }, 'retrieve payments collections');
    }

    /**
     * Get filtered payments due data
     * Separate endpoint for payments due with filters
     */
    public function paymentsDue(PaymentsDueRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $validated = $request->validated();
            $filters = [
                'period' => $validated['period'] ?? null,
                'from_date' => $validated['from_date'] ?? null,
                'to_date' => $validated['to_date'] ?? null,
            ];

            $data = $this->dashboardService->getFilteredPaymentsDue($this->getUserId(), $filters);

            return $this->success($data);
        }, 'retrieve payments due');
    }

    /**
     * Portfolio / sales summary for dashboard cards.
     */
    public function salesStats(Request $request)
    {
        return $this->executeWithExceptionHandling(function () {
            $data = $this->dashboardService->getSalesStats($this->getUserId());

            return $this->success($data);
        }, 'retrieve sales stats');
    }

    /**
     * Build filters array from request parameters
     */
    protected function buildFilters(Request $request): array
    {
        return [
            'collections' => array_filter([
                'period' => $request->get('collections_period'),
                'from_date' => $request->get('collections_from_date'),
                'to_date' => $request->get('collections_to_date'),
            ], function($value) {
                return !is_null($value) && $value !== '';
            }),
            'payments_due' => array_filter([
                'period' => $request->get('payments_due_period'),
                'from_date' => $request->get('payments_due_from_date'),
                'to_date' => $request->get('payments_due_to_date'),
            ], function($value) {
                return !is_null($value) && $value !== '';
            }),
        ];
    }
}

