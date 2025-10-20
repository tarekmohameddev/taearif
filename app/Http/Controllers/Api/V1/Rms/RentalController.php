<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Services\Rms\RentalService;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Exceptions\PaymentException;
use App\Exceptions\Api\ApiException;
use Illuminate\Support\Facades\Log;

class RentalController extends BaseApiController
{
    protected $rentalService;

    public function __construct(RentalService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    public function index(Request $request)
    {
        try {
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

            // CLEAN: Use success helper with pagination meta
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
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function store(Request $request)
    {
        try {
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

            return $this->created($rental, 'Rental created successfully');
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function show($id)
    {
        try {
            $rental = $this->rentalService->getRentalDetails(auth()->id(), $id);
            return $this->success($rental);
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function update(Request $request, $id)
    {
        try {
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

            return $this->success($rental, 'Rental updated successfully');
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Delete a rental
     *
     * @param int $id Rental ID
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            // Get rental details before deletion for response
            $rental = $this->rentalService->getRentalDetails(auth()->id(), $id);

            // Store important info before deletion
            $deletedInfo = [
                'id' => $rental->id,
                'tenant_name' => $rental->tenant_full_name,
                'property' => $rental->property?->property_name ?? null,
                'deleted_at' => now()->toISOString()
            ];

            // Delete the rental
            $this->rentalService->deleteRental(auth()->id(), $id);

            // Return frontend-friendly response
            return response()->json([
                'status' => true,
                'message' => 'Rental deleted successfully',
                'data' => $deletedInfo
            ], 200);

        } catch (ApiException $e) {
            // CLEAN: Custom exceptions render themselves with error codes
            return $e->render();

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            // Handle not found error
            return response()->json([
                'status' => false,
                'message' => 'Rental not found or already deleted',
                'error_code' => 'RENTAL_NOT_FOUND'
            ], 404);

        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Rental deletion failed', [
                'user_id' => auth()->id(),
                'rental_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // SECURITY: Generic exceptions handled safely
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete rental',
                'error_code' => 'DELETION_FAILED',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred while deleting the rental'
            ], 500);
        }
    }

    public function propertyDetails($id)
    {
        try {
            $details = $this->rentalService->getPropertyDetails(auth()->id(), $id);
            return $this->success($details);
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function currentCollections($id)
    {
        try {
            $collections = $this->rentalService->getCurrentCollections(auth()->id(), $id);
            return $this->success($collections);
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    public function detailsWithPayments($id)
    {
        try {
            $details = $this->rentalService->getRentalDetailsWithPayments(auth()->id(), $id);
            return $this->success($details);
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get payment collection data for a rental
     * Returns installments with amounts and payment status
     */
    public function paymentCollection($id)
    {
        try {
            $collectionData = $this->rentalService->getPaymentCollectionData(auth()->id(), $id);
            return $this->success($collectionData);
        } catch (ApiException $e) {
            return $e->render();
        } catch (\Exception $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get payment collection data for all rentals of the authenticated user
     * Returns summary and paginated list of rentals with payment collection data
     */
    public function allPaymentCollections(Request $request)
    {
        try {
            $validated = $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'status' => 'nullable|string|in:active,ended,terminated,cancelled,draft',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'property_id' => 'nullable|integer|exists:user_properties,id',
                'project_id' => 'nullable|integer|exists:projects,id',
            ]);

            $result = $this->rentalService->getAllPaymentCollections(auth()->id(), $validated);

            return response()->json([
                'status' => true,
                'data' => $result
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Get all payment collections failed', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve payment collections',
                'error' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
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
                'payments.*.installment_id' => 'required|exists:rm_payment_installments,id',
                'payments.*.payment_type' => 'required|in:rent,cost_item,deposit',
                'payments.*.cost_item_id' => 'required_if:payments.*.payment_type,cost_item|exists:rental_cost_items,id',
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

            // Get the tenant owner ID (handles both direct owners and sub-users)
            $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

            // Get rental for validation
            $rental = RmRental::with(['activeContract', 'tenantCostItems', 'ownerCostItems'])
                ->where('user_id', $ownerId)
                ->findOrFail($id);

            // Enhanced installment validation (fixes Bug #3: Missing Installment Ownership Validation)
            // Collect ALL installment_ids from any payment type (not just rent)
            $installmentIds = collect($data['payments'])
                ->pluck('installment_id')
                ->filter()
                ->unique();

            if ($installmentIds->isNotEmpty()) {
                // Validate that installments belong to this rental and user owns the rental
                $this->rentalService->validateInstallmentsForRental($ownerId, $id, $installmentIds);

                // Additional security: Verify installments belong to ACTIVE contract only
                // This prevents payment to old/terminated contract installments
                if ($rental->activeContract) {
                    $validInstallmentIds = RmPaymentInstallment::where('contract_id', $rental->activeContract->id)
                        ->whereIn('id', $installmentIds)
                        ->pluck('id');

                    $invalidInstallments = $installmentIds->diff($validInstallmentIds);

                    if ($invalidInstallments->isNotEmpty()) {
                        throw new \InvalidArgumentException(
                            'Installments do not belong to active contract: ' . $invalidInstallments->implode(', ')
                        );
                    }
                }
            }

            // Validate cost_item payments
            foreach ($data['payments'] as $payment) {
                if ($payment['payment_type'] === 'cost_item' && !empty($payment['cost_item_id'])) {
                    // Find the cost item
                    $costItem = \App\Models\Api\Rms\RentalCostItem::where('rental_id', $id)
                        ->where('id', $payment['cost_item_id'])
                        ->first();

                    if (!$costItem) {
                        throw new \InvalidArgumentException('Cost item does not belong to this rental');
                    }

                    // Check if one_time cost item already paid
                    if ($costItem->payment_frequency === 'one_time') {
                        $alreadyPaid = \App\Models\Api\Rms\RmPayment::where('rental_id', $id)
                            ->where('cost_item_id', $payment['cost_item_id'])
                            ->where('payment_type', 'cost_item')
                            ->exists();

                        if ($alreadyPaid) {
                            throw new \InvalidArgumentException(
                                "Cost item '{$costItem->name}' (one_time) has already been paid!"
                            );
                        }
                    }

                    // Check if per_installment cost item already paid for this installment
                    if ($costItem->payment_frequency === 'per_installment' && !empty($payment['installment_id'])) {
                        $alreadyPaidForInstallment = \App\Models\Api\Rms\RmPayment::where('rental_id', $id)
                            ->where('cost_item_id', $payment['cost_item_id'])
                            ->where('installment_id', $payment['installment_id'])
                            ->where('payment_type', 'cost_item')
                            ->exists();

                        if ($alreadyPaidForInstallment) {
                            throw new \InvalidArgumentException(
                                "Cost item '{$costItem->name}' has already been paid for this installment!"
                            );
                        }
                    }
                }
            }

            // Process payments using PaymentService
            $paymentService = app(\App\Services\Rms\PaymentService::class);

            // Prepare all payment data with common fields
            $paymentsData = collect($data['payments'])->map(function ($paymentData, $index) use ($data) {
                // Generate unique reference for each payment if base reference provided
                // Format: PAY-{timestamp}-{index} to ensure uniqueness within batch
                $uniqueReference = !empty($data['reference'])
                    ? $data['reference'] . '-' . ($index + 1)
                    : null;

                return array_merge($paymentData, [
                    'payment_method' => $data['payment_method'],
                    'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                    'reference' => $uniqueReference,
                    'notes' => $paymentData['notes'] ?? $data['notes'],
                    'bank_name' => $data['bank_name'] ?? null,
                    'receipt_image_path' => $data['receipt_image_path'] ?? null,
                    'transfer_to' => $data['transfer_to'],
                ]);
            })->toArray();

            // Process all payments in a single transaction (fixes Bug #2: Partial Transaction Failure)
            // If any payment fails, all previous payments will be rolled back
            // Use $ownerId instead of auth()->id() to handle sub-users correctly
            $processedPayments = $paymentService->recordMultiplePayments($ownerId, $id, $paymentsData);

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

        } catch (PaymentException $e) {
            // Handle custom payment exceptions with structured error response
            Log::warning('Payment validation failed', [
                'user_id' => auth()->id(),
                'rental_id' => $id,
                'error_code' => $e->getErrorCode(),
                'error_message' => $e->getMessage(),
                'error_data' => $e->getErrorData()
            ]);

            return $e->render($request);

        } catch (\InvalidArgumentException $e) {
            // Handle business logic validation errors
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
                'error_code' => 'INVALID_ARGUMENT'
            ], 422);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Rental contract or related record not found',
                'error_code' => 'NOT_FOUND',
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
                'error_code' => 'INTERNAL_ERROR',
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
                'termination_reason' => 'nullable|string|max:500',
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

    /**
     * Get daily follow-up for rentals with payments due
     * Shows payments due today by default, with optional filters
     */
    public function dailyFollowUp(Request $request)
    {
        try {
            $validated = $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'building_id' => 'nullable|integer',
                'status' => 'nullable|string|in:overdue,due_today,upcoming',
            ]);

            $result = $this->rentalService->getDailyFollowUp(auth()->id(), $validated);

            return response()->json([
                'status' => true,
                'message' => 'Daily follow-up retrieved successfully',
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'summary' => $result['summary'],
                'filters' => $result['filters'],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Daily follow-up error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve daily follow-up',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * List all contracts with detailed information
     *
     * GET /api/v1/rms/rentals/contracts
     *
     * Optional filters:
     * - building_id: Filter by building
     * - payment_status: Filter by payment status (paid, pending, overdue, not_due)
     * - rental_method: Filter by rental method (monthly, quarterly, semi_annual, annual)
     * - from_date: Filter contracts starting from this date
     * - to_date: Filter contracts ending before this date
     * - contract_status: Filter by contract status (active, expired, pending, terminated)
     * - per_page: Number of results per page (default: 15, max: 100)
     */
    public function allContracts(Request $request)
    {
        try {
            $validated = $request->validate([
                'per_page' => 'nullable|integer|min:1|max:100',
                'page' => 'nullable|integer|min:1',
                'building_id' => 'nullable|integer',
                'payment_status' => 'nullable|in:paid,pending,overdue,not_due',
                'rental_method' => 'nullable|in:monthly,quarterly,semi_annual,annual',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'contract_status' => 'nullable|in:active,expired,pending,terminated',
            ]);

            $result = $this->rentalService->listAllContracts($request);

            return response()->json($result);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error retrieving all contracts', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve contracts',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}
