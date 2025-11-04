<?php

namespace App\Http\Controllers\Api\V1\Rms;

use App\Http\Controllers\Api\BaseApiController;
use App\Traits\HandlesApiExceptions;
use App\Constants\RmsConstants;
use App\Http\Requests\Rms\Rental\ListRentalsRequest;
use App\Http\Requests\Rms\Rental\StoreRentalRequest;
use App\Http\Requests\Rms\Rental\UpdateRentalRequest;
use App\Http\Requests\Rms\Rental\CollectPaymentRequest;
use App\Http\Requests\Rms\Rental\UpdateRentalStatusRequest;
use App\Http\Requests\Rms\Rental\EndContractRequest;
use App\Http\Requests\Rms\Rental\RenewRentalRequest;
use App\Http\Requests\Rms\Rental\UploadReceiptImageRequest;
use App\Http\Resources\Rms\RentalResource;
use App\Http\Resources\Rms\PaymentResource;
use Illuminate\Http\Request;
use App\Services\Rms\RentalService;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Exceptions\PaymentException;
use App\Exceptions\Api\ApiException;
use Illuminate\Support\Facades\Log;

class RentalController extends BaseApiController
{
    use HandlesApiExceptions;

    protected $rentalService;

    public function __construct(RentalService $rentalService)
    {
        $this->rentalService = $rentalService;
    }

    public function index(ListRentalsRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $rentals = $this->rentalService->listRentals($request);

            return $this->paginated($rentals, RentalResource::class);
        }, 'list rentals');
    }

    public function store(StoreRentalRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            $rental = $this->rentalService->createRental(
                $this->getUserId(),
                $request->validated()
            );

            return $this->created(
                RentalResource::make($rental),
                'Rental created successfully'
            );
        }, 'create rental');
    }

    public function show($id)
    {
        return $this->executeWithExceptionHandling(function () use ($id) {
            $rental = $this->rentalService->getRentalDetails($this->getUserId(), $id);
            return $this->success(RentalResource::make($rental));
        }, 'retrieve rental details');
    }

    public function update(UpdateRentalRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $data = $request->validated();
            $regenerate = $request->boolean('regenerate_schedule', false);

            $rental = $this->rentalService->updateRental(
                $this->getUserId(),
                $id,
                $data,
                $regenerate
            );

            return $this->success(
                RentalResource::make($rental),
                'Rental updated successfully'
            );
        }, 'update rental');
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
     * Supports both manual selection and auto-selection modes
     *
     * Manual Mode: Client specifies exact installments to pay
     * Auto-Select Mode: System automatically selects installments based on strategy
     *
     * NOTE: Phase 4 TODO - Move business logic (lines 275-376) to PaymentService
     */
    public function collectPayment(CollectPaymentRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $data = $request->validated();
            $ownerId = $this->getUserId();

            // SMART DETECTION: If payments is empty but amount is provided, use auto-select
            if (empty($data['payments']) && !empty($data['amount'])) {
                $data['auto_select'] = true;
                $data['auto_select_amount'] = $data['amount'];
                $data['auto_select_strategy'] = $data['auto_select_strategy'] ?? 'overdue_first';
            }

            // FEATURE: Auto-Selection Mode
            if (!empty($data['auto_select'])) {
                return $this->handleAutoSelectPayment($data, $id, $ownerId);
            }

            // EXISTING: Manual Selection Mode
            return $this->handleManualPayment($data, $id, $ownerId);
        }, 'collect payment');
    }

    /**
     * Handle auto-selection payment workflow
     *
     * @param array $data Validated request data
     * @param int $rentalId
     * @param int $ownerId
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleAutoSelectPayment(array $data, $rentalId, $ownerId)
    {
        $paymentService = app(\App\Services\Rms\PaymentService::class);

        // Step 1: Get auto-selected installments
        $autoSelection = $paymentService->autoSelectInstallments(
            $ownerId,
            $rentalId,
            $data['auto_select_amount'],
            $data['auto_select_strategy'] ?? 'overdue_first'
        );

        // Step 2: Check if any installments were selected
        if (empty($autoSelection['selected_installments'])) {
            return $this->success([
                'message' => 'No outstanding installments to pay',
                'auto_selection_preview' => $autoSelection,
            ]);
        }

        // Step 3: Convert auto-selected installments to payment format
        $data['payments'] = collect($autoSelection['selected_installments'])
            ->map(function ($item) {
                return [
                    'installment_id' => $item['installment_id'],
                    'payment_type' => 'rent', // Auto-selection only handles rent payments
                    'amount' => $item['pay_amount'],
                ];
            })->toArray();

        // Step 4: Process payments using existing manual payment logic
        $processedPayments = $this->processPayments($data, $rentalId, $ownerId);

        // Step 5: Return response with auto-selection details
        return $this->created([
            'payments' => PaymentResource::collection($processedPayments),
            'total_amount' => collect($processedPayments)->sum('amount'),
            'payment_count' => count($processedPayments),
            'auto_selected' => true,
            'selection_details' => $autoSelection,
        ], 'Payment collected successfully using auto-selection');
    }

    /**
     * Handle manual payment workflow
     *
     * @param array $data Validated request data
     * @param int $rentalId
     * @param int $ownerId
     * @return \Illuminate\Http\JsonResponse
     */
    private function handleManualPayment(array $data, $rentalId, $ownerId)
    {
        $processedPayments = $this->processPayments($data, $rentalId, $ownerId);

        return $this->created([
            'payments' => PaymentResource::collection($processedPayments),
            'total_amount' => collect($processedPayments)->sum('amount'),
            'payment_count' => count($processedPayments),
            'auto_selected' => false,
        ], 'Payment collected successfully');
    }

    /**
     * Process payments (shared logic for both manual and auto-select modes)
     * Extracts business logic for reusability
     *
     * @param array $data Payment data
     * @param int $rentalId
     * @param int $ownerId
     * @return array Processed payments
     */
    private function processPayments(array $data, $rentalId, $ownerId)
    {
        // Get rental for validation
        $rental = RmRental::with(['activeContract', 'tenantCostItems', 'ownerCostItems'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        // Enhanced installment validation (fixes Bug #3: Missing Installment Ownership Validation)
        // Collect ALL installment_ids from any payment type (not just rent)
        $installmentIds = collect($data['payments'])
            ->pluck('installment_id')
            ->filter()
            ->unique();

        if ($installmentIds->isNotEmpty()) {
            // Validate that installments belong to this rental and user owns the rental
            $this->rentalService->validateInstallmentsForRental($ownerId, $rentalId, $installmentIds);

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
                $costItem = \App\Models\Api\Rms\RentalCostItem::where('rental_id', $rentalId)
                    ->where('id', $payment['cost_item_id'])
                    ->first();

                if (!$costItem) {
                    throw new \InvalidArgumentException('Cost item does not belong to this rental');
                }

                // Check if one_time cost item already paid
                if ($costItem->payment_frequency === 'one_time') {
                    $alreadyPaid = \App\Models\Api\Rms\RmPayment::where('rental_id', $rentalId)
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
                    $alreadyPaidForInstallment = \App\Models\Api\Rms\RmPayment::where('rental_id', $rentalId)
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
        return $paymentService->recordMultiplePayments($ownerId, $rentalId, $paymentsData);
    }

    /**
     * Update rental status with validation
     */
    public function updateStatus(UpdateRentalStatusRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $rental = $this->rentalService->updateRentalStatus(
                $this->getUserId(),
                $id,
                $request->validated()
            );

            return $this->success(
                RentalResource::make($rental),
                'Rental status updated successfully'
            );
        }, 'update rental status');
    }

    /**
     * End rental contract by setting end date and updating status
     */
    public function endContract(EndContractRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $rental = $this->rentalService->endRentalContract(
                $this->getUserId(),
                $id,
                $request->validated()
            );

            return $this->success(
                RentalResource::make($rental),
                'Rental contract ended successfully'
            );
        }, 'end rental contract');
    }

    /**
     * Renew an ended rental by creating a new rental record
     */
    public function renewRental(RenewRentalRequest $request, $id)
    {
        return $this->executeWithExceptionHandling(function () use ($request, $id) {
            $newRental = $this->rentalService->renewRental(
                $this->getUserId(),
                $id,
                $request->validated()
            );

            return $this->created(
                RentalResource::make($newRental),
                'Rental renewed successfully'
            );
        }, 'renew rental');
    }

    /**
     * Upload receipt image for payment
     */
    public function uploadReceiptImage(UploadReceiptImageRequest $request)
    {
        return $this->executeWithExceptionHandling(function () use ($request) {
            // Generate unique filename
            $file = $request->file('receipt_image');
            $filename = 'receipt_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

            // Store the file directly in public/receipts folder
            $file->move(public_path('receipts'), $filename);
            $path = 'receipts/' . $filename;

            // Get the full URL - directly accessible from public folder
            $url = url($path);

            return $this->created([
                'image_path' => $path,
                'image_url' => $url,
                'filename' => $filename
            ], 'Receipt image uploaded successfully');
        }, 'upload receipt image');
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
                'from_date' => 'nullable|date|before_or_equal:today',
                'to_date' => 'nullable|date|after_or_equal:from_date|before_or_equal:today',
                'building_id' => 'nullable|integer|exists:buildings,id',
                'status' => 'nullable|string|in:overdue,due_today,upcoming',
            ]);

            $result = $this->rentalService->getDailyFollowUp($validated);

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

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 403);

        } catch (\Exception $e) {
            Log::error('Daily follow-up error: ' . $e->getMessage(), [
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to retrieve daily follow-up',
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
