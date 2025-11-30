<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use Illuminate\Http\Request;
use App\Services\Rms\DashboardService;

class RmsDashboardController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $dashboardService;

    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function index(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $range = (int) $request->get('range', 7); // 7 or 30 days

            // Validate filter parameters
            $validated = $request->validate([
                'collections_period' => 'nullable|string|in:this_week,this_month,this_year,custom',
                'collections_from_date' => 'nullable|date|required_with:collections_to_date|required_if:collections_period,custom',
                'collections_to_date' => 'nullable|date|after_or_equal:collections_from_date|required_if:collections_period,custom',
                'payments_due_period' => 'nullable|string|in:this_week,this_month,this_year,custom',
                'payments_due_from_date' => 'nullable|date|required_with:payments_due_to_date|required_if:payments_due_period,custom',
                'payments_due_to_date' => 'nullable|date|after_or_equal:payments_due_from_date|required_if:payments_due_period,custom',
            ]);

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
    public function paymentsCollections(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $validated = $request->validate([
                'period' => 'nullable|string|in:this_week,this_month,this_year,custom',
                'from_date' => 'nullable|date|required_if:period,custom',
                'to_date' => 'nullable|date|after_or_equal:from_date|required_if:period,custom',
            ]);

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
    public function paymentsDue(Request $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $validated = $request->validate([
                'period' => 'nullable|string|in:this_week,this_month,this_year,custom',
                'from_date' => 'nullable|date|required_if:period,custom',
                'to_date' => 'nullable|date|after_or_equal:from_date|required_if:period,custom',
            ]);

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

