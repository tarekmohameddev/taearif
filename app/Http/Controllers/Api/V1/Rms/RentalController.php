<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Rms\RentalService;
use Illuminate\Support\Facades\Log;

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
            'building_id' => 'nullable',
            'unit_id' => 'nullable|integer',
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
            'unit_id' => 'nullable|integer',
            'project_id' => 'nullable|integer',
            'building_id' => 'nullable',
            'move_in_date' => 'nullable|date',
            'rental_type' => 'required|in:monthly,annual',
            'rental_duration' => 'required|integer|min:1',
            'paying_plan' => 'required|in:monthly,quarterly,semi_annual,annual',
            'total_rental_amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|size:3',
            'contract_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'cost_items' => 'nullable|array',
            'cost_items.*.name' => 'required|string|max:255',
            'cost_items.*.cost' => 'required|numeric|min:0',
            'cost_items.*.type' => 'required|in:fixed,percentage',
            'cost_items.*.payer' => 'required|in:owner,tenant',
            'cost_items.*.payment_frequency' => 'required|in:one_time,per_installment',
            'cost_items.*.percentage_of' => 'nullable|numeric|min:0',
            'cost_items.*.description' => 'nullable|string',
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
            'tenant_social_status', 'tenant_national_id', 'unit_id', 'project_id', 'building_id',
            'move_in_date', 'rental_type', 'rental_duration', 'paying_plan',
            'total_rental_amount', 'currency', 'contract_number', 'notes', 'cost_items'
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
        try {
            $data = $request->validate([
                'payments' => 'required|array|min:1',
                'payments.*.installment_id' => 'nullable|exists:rm_payment_installments,id',
                'payments.*.payment_type' => 'required|in:rent,platform_fee,water_fee,office_fee,deposit',
                'payments.*.amount' => 'required|numeric|min:0.01',
                'payments.*.notes' => 'nullable|string|max:255',
                'payment_method' => 'required|in:cash,bank_transfer,credit_card,online_payment,check,other',
                'payment_date' => 'nullable|date',
                'reference' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:255',
                'bank_name' => 'nullable|string|max:100',
                'receipt_image_path' => 'nullable|string|max:500',
                'transfer_to' => 'required|in:منصة ناجز,المالك,المكتب'
            ]);

            // Validate bank_name is required when payment_method is bank_transfer
            if ($data['payment_method'] === 'bank_transfer' && empty($data['bank_name'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bank name is required when payment method is bank transfer',
                    'errors' => ['bank_name' => ['Bank name is required when payment method is bank transfer']]
                ], 422);
            }

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
                $paymentData['bank_name'] = $data['bank_name'] ?? null;
                $paymentData['receipt_image_path'] = $data['receipt_image_path'] ?? null;
                $paymentData['transfer_to'] = $data['transfer_to'];

                $payment = $paymentService->recordPayment(auth()->id(), $id, $paymentData);
                $processedPayments[] = $payment;
            }

            // Add receipt image URL to payments if available
            $paymentsWithImageUrl = collect($processedPayments)->map(function ($payment) {
                if (!empty($payment->receipt_image_path)) {
                    // Generate URL for receipts stored in public/receipts
                    $payment->receipt_image_url = url($payment->receipt_image_path);
                }
                return $payment;
            });

            return response()->json([
                'status' => true,
                'message' => 'Payment collected successfully',
                'data' => [
                    'payments' => $paymentsWithImageUrl,
                    'total_amount' => collect($processedPayments)->sum('amount'),
                    'payment_count' => count($processedPayments)
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Rental contract or related record not found',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Payment collection failed', [
                'user_id' => auth()->id(),
                'rental_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Payment collection failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Update rental status with validation
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'status' => 'required|string',
                'end_date' => 'nullable|date',
                'notes' => 'nullable|string|max:500'
            ]);

            $rental = $this->rentalService->updateRentalStatus(auth()->id(), $id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Rental status updated successfully',
                'data' => $rental
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Rental not found',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update rental status',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * End rental contract by setting end date and updating status
     */
    public function endContract(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'end_date' => 'required|date|after_or_equal:today',
                'termination_reason' => 'nullable|string|max:255',
                'notes' => 'nullable|string|max:500'
            ]);

            $rental = $this->rentalService->endRentalContract(auth()->id(), $id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Rental contract ended successfully',
                'data' => $rental
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Rental contract not found',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            Log::error('End rental contract failed', [
                'user_id' => auth()->id(),
                'rental_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to end rental contract',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Renew an ended rental by creating a new rental record
     */
    public function renewRental(Request $request, $id)
    {
        try {
            $data = $request->validate([
                'rental_type' => 'required|in:monthly,annual',
                'rental_duration' => 'required|integer|min:1',
                'paying_plan' => 'required|in:monthly,quarterly,semi_annual,annual',
                'total_rental_amount' => 'required|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'notes' => 'nullable|string',
                'cost_items' => 'nullable|array',
                'cost_items.*.name' => 'required|string|max:255',
                'cost_items.*.cost' => 'required|numeric|min:0',
                'cost_items.*.type' => 'required|in:fixed,percentage',
                'cost_items.*.payer' => 'required|in:owner,tenant',
                'cost_items.*.payment_frequency' => 'required|in:one_time,per_installment',
                'cost_items.*.percentage_of' => 'nullable|numeric|min:0',
                'cost_items.*.description' => 'nullable|string',
            ]);

            $newRental = $this->rentalService->renewRental(auth()->id(), $id, $data);

            return response()->json([
                'status' => true,
                'message' => 'Rental renewed successfully',
                'data' => $newRental
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Rental not found',
                'error' => $e->getMessage()
            ], 404);

        } catch (\Exception $e) {
            Log::error('Rental renewal failed', [
                'user_id' => auth()->id(),
                'rental_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to renew rental',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Upload receipt image for payment
     */
    public function uploadReceiptImage(Request $request)
    {
        try {
            $request->validate([
                'receipt_image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB max
            ]);

            // Generate unique filename
            $file = $request->file('receipt_image');
            $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store the file directly in public/receipts folder
            $file->move(public_path('receipts'), $filename);
            $path = 'receipts/' . $filename;

            // Get the full URL - directly accessible from public folder
            $url = url($path);

            return response()->json([
                'status' => true,
                'message' => 'Receipt image uploaded successfully',
                'data' => [
                    'image_path' => $path,
                    'image_url' => $url,
                    'filename' => $filename
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Receipt image upload failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Receipt image upload failed',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    /**
     * Get property payment report
     * Shows collected vs outstanding payments for all properties
     */
    public function paymentReport(Request $request)
    {
        try {
            $validated = $request->validate([
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'property_id' => 'nullable|integer|exists:user_properties,id',
                'project_id' => 'nullable|integer|exists:projects,id',
                'building_id' => 'nullable|integer|exists:buildings,id',
            ]);

            $report = $this->rentalService->getPropertyPaymentReport(auth()->id(), $validated);

            return response()->json([
                'status' => true,
                'message' => 'Payment report retrieved successfully',
                'data' => $report,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Payment report error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to generate payment report',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
