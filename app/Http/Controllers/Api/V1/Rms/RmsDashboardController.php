<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Services\Rms\DashboardService;
use App\Http\Requests\Api\V1\Rms\RmsDashboardIndexRequest;
use App\Http\Requests\Api\V1\Rms\PaymentsCollectionsRequest;
use App\Http\Requests\Api\V1\Rms\PaymentsDueRequest;

class RmsDashboardController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
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

