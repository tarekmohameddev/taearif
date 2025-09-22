<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\RentalService;

class RentalController extends Controller
{
    protected $rentalService;

    public function __construct(RentalService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    public function index(Request $request)
    {
        // Validate pagination and sorting parameters
        $request->validate([
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'sort_by' => 'nullable|string|in:created_at,updated_at,move_in_date,tenant_full_name,base_rent_amount,status',
            'sort_order' => 'nullable|string|in:asc,desc',
            'q' => 'nullable|string|max:255',
            'status' => 'nullable|string|in:active,inactive,terminated',
            'property_number' => 'nullable|string|max:100',
            'property_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'paying_plan' => 'nullable|string|in:monthly,quarterly,semi_annual,annual',
            'filter_by_month' => 'nullable|integer|min:1|max:12',
            'filter_by_year' => 'nullable|integer|min:2000|max:2100',
            'filter_by_day' => 'nullable|date',
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date|after_or_equal:from_date',
        ]);

        $rentals = $this->rentalService->listRentals($request);

        return response()->json([
            'status' => true,
            'data' => $rentals->items(),
            'pagination' => [
                'current_page' => $rentals->currentPage(),
                'per_page' => $rentals->perPage(),
                'total' => $rentals->total(),
                'last_page' => $rentals->lastPage(),
                'from' => $rentals->firstItem(),
                'to' => $rentals->lastItem(),
                'has_more_pages' => $rentals->hasMorePages(),
                'next_page_url' => $rentals->nextPageUrl(),
                'prev_page_url' => $rentals->previousPageUrl(),
            ]
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenant_full_name' => 'required|string|max:150',
            'tenant_phone' => 'required|string|max:32',
            'tenant_email' => 'nullable|email',
            'tenant_job_title' => 'nullable|string|max:120',
            'tenant_social_status' => 'nullable|in:single,married,divorced,widowed,other',
            'tenant_national_id' => 'nullable|string|max:20',
            'property_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'unit_label' => 'nullable|string|max:100',
            'property_number' => 'nullable|string|max:100',
            'move_in_date' => 'nullable|date',
            'rental_period' => 'nullable|integer',
            'paying_plan' => 'nullable|in:monthly,quarterly,semi_annual,annual',
            'base_rent_amount' => 'nullable|numeric',
            'currency' => 'nullable|string|size:3',
            'deposit_amount' => 'nullable|numeric',
            'platform_fee' => 'nullable|numeric|min:0',
            'water_fee' => 'nullable|numeric|min:0',
            'office_commission_type' => 'nullable|in:percentage,amount',
            'office_commission_value' => 'nullable|numeric|min:0',
            'contract_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $rental = $this->rentalService->createRental(auth()->id(), $data);

        return response()->json(['status' => true, 'data' => $rental], 201);
    }

    public function show($id)
    {
        $rental = $this->rentalService->getRentalDetails(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $rental]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->only([
            'tenant_full_name', 'tenant_phone', 'tenant_email', 'tenant_job_title',
            'tenant_social_status', 'tenant_national_id', 'property_id', 'project_id', 'unit_label', 'property_number',
            'move_in_date', 'rental_period', 'paying_plan',
            'base_rent_amount', 'currency', 'deposit_amount', 'platform_fee', 'water_fee', 
            'office_commission_type', 'office_commission_value', 'contract_number', 'notes'
        ]);

        // Handle payments if included in request
        if ($request->has('payments')) {
            $data['payments'] = $request->input('payments');
        }

        $regenerate = $request->boolean('regenerate_schedule', false);
        $rental = $this->rentalService->updateRental(auth()->id(), $id, $data, $regenerate);

        return response()->json(['status' => true, 'data' => $rental]);
    }

    public function destroy($id)
    {
        $this->rentalService->deleteRental(auth()->id(), $id);
        return response()->json(null, 204);
    }

    public function propertyDetails($id)
    {
        $details = $this->rentalService->getPropertyDetails(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $details]);
    }

    public function currentCollections($id)
    {
        $collections = $this->rentalService->getCurrentCollections(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $collections]);
    }

    public function detailsWithPayments($id)
    {
        $details = $this->rentalService->getRentalDetailsWithPayments(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $details]);
    }

    /**
     * Get payment collection data for a rental
     * Returns installments with amounts and payment status
     */
    public function paymentCollection($id)
    {
        $collectionData = $this->rentalService->getPaymentCollectionData(auth()->id(), $id);
        return response()->json(['status' => true, 'data' => $collectionData]);
    }

    /**
     * Collect payment for specific installments
     * Supports partial payments for individual installments
     */
    public function collectPayment(Request $request, $id)
    {
        $data = $request->validate([
            'payments' => 'required|array|min:1',
            'payments.*.installment_id' => 'nullable|exists:rm_payment_installments,id',
            'payments.*.payment_type' => 'required|in:rent,platform_fee,water_fee,office_fee,deposit',
            'payments.*.amount' => 'required|numeric|min:0.01',
            'payments.*.notes' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cash,bank_transfer,credit_card,online_payment,check,other',
            'payment_date' => 'nullable|date',
            'reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:255'
        ]);

        // Validate installment_ids only for rent payments
        $rentPayments = collect($data['payments'])->where('payment_type', 'rent');
        if ($rentPayments->isNotEmpty()) {
            $installmentIds = $rentPayments->pluck('installment_id')->filter();
            if ($installmentIds->isNotEmpty()) {
                $this->rentalService->validateInstallmentsForRental(auth()->id(), $id, $installmentIds);
            }
        }

        // Process payments using PaymentService
        $paymentService = app(\App\Services\Rms\PaymentService::class);
        
        $processedPayments = [];
        foreach ($data['payments'] as $paymentData) {
            $paymentData['payment_method'] = $data['payment_method'];
            $paymentData['payment_date'] = $data['payment_date'] ?? now()->toDateString();
            $paymentData['reference'] = $data['reference'];
            $paymentData['notes'] = $paymentData['notes'] ?? $data['notes'];
            
            $payment = $paymentService->recordPayment(auth()->id(), $id, $paymentData);
            $processedPayments[] = $payment;
        }

        return response()->json([
            'status' => true,
            'message' => 'Payment collected successfully',
            'data' => [
                'payments' => $processedPayments,
                'total_amount' => collect($processedPayments)->sum('amount'),
                'payment_count' => count($processedPayments)
            ]
        ], 201);
    }
}
