<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmPayment;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\Language as UserLanguage;
use App\Services\Rms\PaymentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RentalService
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Get property content (name and address) in user's default language
     *
     * @param Property|null $property
     * @param int $userId
     * @return array ['name' => string, 'address' => string]
     */
    protected function getPropertyContent($property, $userId)
    {
        if (!$property) {
            return ['name' => 'N/A', 'address' => 'N/A'];
        }

        // Get user's default language
        $userLanguage = UserLanguage::where('user_id', $userId)
            ->where('is_default', 1)
            ->first();

        $languageId = $userLanguage ? $userLanguage->id : 1; // Fallback to language ID 1

        // Try to get content in user's language
        $content = $property->contents()
            ->where('language_id', $languageId)
            ->first();

        // If not found, get the first available content
        if (!$content) {
            $content = $property->contents()->first();
        }

        return [
            'name' => $content?->title ?? 'N/A',
            'address' => $content?->address ?? 'N/A'
        ];
    }

    /**
     * Get project name in user's default language
     *
     * @param \App\Models\User\RealestateManagement\Project|null $project
     * @param int $userId
     * @return string
     */
    protected function getProjectName($project, $userId)
    {
        if (!$project) {
            return 'N/A';
        }

        // Get user's default language
        $userLanguage = UserLanguage::where('user_id', $userId)
            ->where('is_default', 1)
            ->first();

        $languageId = $userLanguage ? $userLanguage->id : 1; // Fallback to language ID 1

        // Try to get content in user's language
        $content = $project->contents()
            ->where('language_id', $languageId)
            ->first();

        // If not found, get the first available content
        if (!$content) {
            $content = $project->contents()->first();
        }

        return $content?->title ?? 'N/A';
    }
    public function listRentals($request)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

        // Pagination parameters
        $perPage = $request->get('per_page', 15);
        $perPage = min($perPage, 100); // Limit max per page to 100
        $page = $request->get('page', 1);

        // Sorting parameters
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $allowedSortFields = ['created_at', 'updated_at', 'move_in_date', 'tenant_full_name', 'base_rent_amount', 'status'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? $sortBy : 'created_at';
        $sortOrder = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

        $query = RmRental::with(['activeContract', 'property', 'project'])
            ->where('user_id', $ownerId)
            ->when($request->q, fn($q) => $q->where('tenant_full_name', 'like', "%{$request->q}%"))
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->building_id, fn($q) => $q->where('building_id', $request->building_id))
            ->when($request->unit_id, fn($q) => $q->where('unit_id', $request->unit_id))
            ->when($request->project_id, fn($q) => $q->where('project_id', $request->project_id))
            ->when($request->paying_plan, fn($q) => $q->where('paying_plan', $request->paying_plan))
            ->when($request->filter_by_month, function($q) use ($request) {
                $q->whereMonth('move_in_date', $request->filter_by_month)
                  ->whereYear('move_in_date', $request->filter_by_year ?? now()->year);
            })
            ->when($request->filter_by_day, function($q) use ($request) {
                $q->whereDate('move_in_date', $request->filter_by_day);
            })
            ->when($request->from_date, fn($q) => $q->whereDate('move_in_date', '>=', $request->from_date))
            ->when($request->to_date, fn($q) => $q->whereDate('move_in_date', '<=', $request->to_date))
            ->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    public function createRental($userId, array $data)
    {
        // Use the provided userId (which should be the tenant owner's ID)
        // This ensures rentals are created under the correct tenant account
        return DB::transaction(function () use ($userId, $data) {
            // Check if unit already has an active or draft rental
            if (!empty($data['unit_id'])) {
                $existingActiveRental = RmRental::where('unit_id', $data['unit_id'])
                    ->where('user_id', $userId)
                    ->whereIn('status', ['active', 'draft'])
                    ->exists();

                if ($existingActiveRental) {
                    throw new \Exception('This unit already has an active contract. Please end the existing contract before creating a new one.');
                }
            }

            // Extract cost items from data
            $costItems = $data['cost_items'] ?? [];
            unset($data['cost_items']);

            // Auto-populate building_id from property if not provided
            if (empty($data['building_id']) && !empty($data['unit_id'])) {
                $property = Property::find($data['unit_id']);
                if ($property && $property->building_id) {
                    $data['building_id'] = $property->building_id;
                }
            }

            $rental = RmRental::create(array_merge($data, [
                'user_id' => $userId,
                'status' => 'active',
            ]));

            // Create cost items if provided
            if (!empty($costItems)) {
                foreach ($costItems as $costItemData) {
                    $rental->costItems()->create(array_merge($costItemData, [
                        'user_id' => $userId,
                    ]));
                }
            }

            // Update property status based on active rentals
            if ($rental->unit_id) {
                $property = Property::where('id', $rental->unit_id)
                    ->where('user_id', $userId)
                    ->first();
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            $hasEnoughData = $data['move_in_date'] ?? null
                && $data['rental_duration'] ?? null
                && $data['paying_plan'] ?? null
                && $data['total_rental_amount'] ?? null;

            if ($hasEnoughData) {
                // Calculate total months based on rental_duration and rental_type
                $totalMonths = $this->calculateTotalMonthsFromDuration($data['rental_duration'], $data['rental_type']);

                $contract = RmContract::create([
                    'user_id' => $userId,
                    'rental_id' => $rental->id,
                    'start_date' => $data['move_in_date'],
                    'end_date' => Carbon::parse($data['move_in_date'])->addMonths($totalMonths)->subDay(),
                    'status' => 'active',
                    // Snapshot identifiers for audit/history
                    'property_id' => $rental->unit_id,
                    'project_id' => $rental->project_id,
                    'property_name' => $rental->property_name ?? null,
                    'project_name' => $rental->project_name ?? null,
                    'grace_period_months' => 0,
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]);

                $this->generateInstallments($userId, $rental->id, $contract->id, $data);

                return [
                    'id' => $rental->id,
                    'status' => 'active',
                    'contract' => [
                        'id' => $contract->id,
                        'status' => $contract->status,
                    ],
                    'cost_items' => $rental->costItems
                ];
            }

            return [
                'id' => $rental->id,
                'status' => 'active',
                'cost_items' => $rental->costItems
            ];
        });
    }

    public function getRentalDetails($userId, $id)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['activeContract', 'upcomingInstallments', 'maintenanceOpen', 'costItems'])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return $rental;
    }

    public function updateRental($userId, $id, array $data, bool $regenerate = false)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        return DB::transaction(function () use ($ownerId, $id, $data, $regenerate) {
            $rental = RmRental::where('user_id', $ownerId)->findOrFail($id);

            // Check if unit_id is being changed and if new unit is available
            if (isset($data['unit_id']) && $data['unit_id'] != $rental->unit_id) {
                $existingActiveRental = RmRental::where('unit_id', $data['unit_id'])
                    ->where('user_id', $ownerId)
                    ->where('id', '!=', $id) // Exclude current rental
                    ->whereIn('status', ['active', 'draft'])
                    ->exists();

                if ($existingActiveRental) {
                    throw new \Exception('The selected unit already has an active contract. Please choose a different unit or end the existing contract first.');
                }

                // Auto-populate building_id from property when unit changes
                if (empty($data['building_id'])) {
                    $property = Property::find($data['unit_id']);
                    if ($property && $property->building_id) {
                        $data['building_id'] = $property->building_id;
                    }
                }
            }

            // Handle cost items update
            if (isset($data['cost_items']) && is_array($data['cost_items'])) {
                $costItems = $data['cost_items'];
                unset($data['cost_items']); // Remove cost items from rental update data

                // Delete existing cost items and create new ones
                $rental->costItems()->delete();

                foreach ($costItems as $costItemData) {
                    $rental->costItems()->create(array_merge($costItemData, [
                        'user_id' => $ownerId,
                    ]));
                }
            }

            // Handle payment recording if payments are included
            if (isset($data['payments']) && is_array($data['payments'])) {
                $payments = $data['payments'];
                unset($data['payments']); // Remove payments from rental update data

                // Record payments
                if (!empty($payments)) {
                    $this->paymentService->recordMultiplePayments($ownerId, $id, $payments);
                }
            }

            $rental->update($data);

            if ($regenerate && $rental->activeContract) {
                RmPaymentInstallment::where('contract_id', $rental->activeContract->id)
                    ->where('status', 'pending')
                    ->delete();

                $this->generateInstallments(
                    $ownerId,
                    $rental->id,
                    $rental->activeContract->id,
                    $rental->toArray()
                );
            }

            // Update property status after rental update
            if ($rental->unit_id) {
                $property = Property::where('id', $rental->unit_id)
                    ->where('user_id', $ownerId)
                    ->first();
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            return $rental->fresh(['costItems']);
        });
    }

    public function deleteRental($userId, $id)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::where('user_id', $ownerId)->findOrFail($id);

        // Check for contracts that should block deletion (active or pending)
        $blockingContract = $rental->contracts()
            ->whereIn('status', ['active', 'pending'])
            ->first();

        if ($blockingContract) {
            throw \App\Exceptions\Rms\RentalException::hasActiveContract($rental, $blockingContract);
        }

        $unitId = $rental->unit_id;
        $rental->delete();

        // Update property status after rental deletion
        if ($unitId) {
            $property = Property::where('id', $unitId)
                ->where('user_id', $ownerId)
                ->first();
            if ($property) {
                $property->updatePropertyStatus();
            }
        }
    }

    public function getPropertyDetails($userId, $rentalId)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['property.contents', 'project', 'contracts', 'installments', 'activeExpenses', 'tenantCostItems', 'ownerCostItems'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        $activeContract = $rental->contracts()->whereIn('status', ['active', 'pending'])->orderByDesc('status')->orderBy('start_date')->first();

        $payments = $rental->installments()->orderBy('due_date')->get()->map(function ($i) {
            return [
                'id' => $i->id,
                'sequence_no' => $i->sequence_no,
                'due_date' => $i->due_date,
                'amount' => (float) $i->amount,
                'paid_amount' => (float) ($i->paid_amount ?? 0),
                'status' => $i->status,
                'payment_type' => $i->payment_type,
                'payment_status' => $i->payment_status,
                'reference' => $i->reference,
                'paid_at' => $i->paid_at,
            ];
        });

        // Get expenses for the rental
        $expenses = $rental->activeExpenses->map(function ($expense) use ($rental) {
            // Use the actual base_rent_amount column value, not the accessor
            $baseRentAmount = $rental->getAttributes()['base_rent_amount'] ?? 0;
            $calculatedAmount = $expense->amount_type === 'percentage'
                ? ($baseRentAmount * $expense->amount_value) / 100
                : $expense->amount_value;

            return [
                'id' => $expense->id,
                'expense_name' => $expense->expense_name,
                'image_path' => $expense->image_path,
                'image_url' => $expense->image_url,
                'amount_type' => $expense->amount_type,
                'amount_value' => (float) $expense->amount_value,
                'calculated_amount' => (float) $calculatedAmount,
                'cost_center' => $expense->cost_center,
                'is_active' => $expense->is_active,
                'can_be_modified' => $expense->canBeModified(),
                'created_at' => $expense->created_at,
                'updated_at' => $expense->updated_at,
            ];
        });

        // Calculate cost items breakdown (NEW SYSTEM)
        $costItemsBreakdown = $this->calculateCostItemsBreakdown($rental);

        return [
            'rental' => [
                'id' => $rental->id,
                'tenant_full_name' => $rental->tenant_full_name,
                'tenant_phone' => $rental->tenant_phone,
                'tenant_email' => $rental->tenant_email,
                'tenant_job_title' => $rental->tenant_job_title,
                'tenant_social_status' => $rental->tenant_social_status,
                'tenant_national_id' => $rental->tenant_national_id,
                'base_rent_amount' => (float) $rental->base_rent_amount,
                'deposit_amount' => (float) ($rental->deposit_amount ?? 0),
                'platform_fee' => (float) ($rental->platform_fee ?? 0),
                'water_fee' => (float) ($rental->water_fee ?? 0),
                'office_commission_type' => $rental->office_commission_type,
                'office_commission_value' => (float) ($rental->office_commission_value ?? 0),
                'office_fee' => (float) $rental->office_fee,
                'contract_number' => $rental->contract_number,
                'total_rental_amount' => (float) $rental->total_rental_amount,
                'currency' => $rental->currency,
                'move_in_date' => $rental->move_in_date,
                'paying_plan' => $rental->paying_plan,
                'rental_period' => (int) $rental->rental_period,
                'status' => $rental->status,
                'notes' => $rental->notes,
            ],
            'property' => [
                'id' => $rental->unit_id,
                'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
                'building' => $rental->building,
                'project' => [
                    'id' => $rental->project_id,
                    'name' => optional($rental->project)->name,
                ],
            ],
            'contract' => $activeContract ? [
                'id' => $activeContract->id,
                'contract_number' => $rental->contract_number,
                'start_date' => $activeContract->start_date,
                'end_date' => $activeContract->end_date,
                'status' => $activeContract->status,
            ] : null,
            'payment_details' => [
                'items' => $payments,
            ],
            'expenses' => $expenses,
            'cost_items_breakdown' => $costItemsBreakdown,
        ];
    }

    /**
     * Get rental details with comprehensive payment information
     */
    public function getRentalDetailsWithPayments($userId, $rentalId)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['property.contents', 'project', 'contracts', 'installments', 'tenantCostItems', 'ownerCostItems'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        $activeContract = $rental->contracts()->whereIn('status', ['active', 'pending'])->orderByDesc('status')->orderBy('start_date')->first();

        // Get payment summary
        $paymentSummary = $this->paymentService->getPaymentSummary($ownerId, $rentalId);

        // Get detailed installment payment information
        $installmentDetails = $this->paymentService->getInstallmentPaymentDetails($ownerId, $rentalId);

        // Calculate cost items breakdown (NEW SYSTEM)
        $costItemsBreakdown = $this->calculateCostItemsBreakdown($rental);

        return [
            'rental' => [
                'id' => $rental->id,
                'tenant_full_name' => $rental->tenant_full_name,
                'tenant_phone' => $rental->tenant_phone,
                'tenant_email' => $rental->tenant_email,
                'tenant_job_title' => $rental->tenant_job_title,
                'tenant_social_status' => $rental->tenant_social_status,
                'tenant_national_id' => $rental->tenant_national_id,
                'base_rent_amount' => (float) $rental->base_rent_amount,
                'deposit_amount' => (float) ($rental->deposit_amount ?? 0),
                'platform_fee' => (float) ($rental->platform_fee ?? 0),
                'water_fee' => (float) ($rental->water_fee ?? 0),
                'office_commission_type' => $rental->office_commission_type,
                'office_commission_value' => (float) ($rental->office_commission_value ?? 0),
                'office_fee' => (float) $rental->office_fee,
                'contract_number' => $rental->contract_number,
                'total_rental_amount' => (float) $rental->total_rental_amount,
                'currency' => $rental->currency,
                'move_in_date' => $rental->move_in_date,
                // Next payment information
                'next_payment_due_date' => $rental->next_payment_due_date,
                'next_payment_amount' => $rental->next_payment_amount,
                'paying_plan' => $rental->paying_plan,
                'rental_period' => (int) $rental->rental_period,
                'status' => $rental->status,
                'notes' => $rental->notes,
                // Payment summary
                'total_paid_amount' => $paymentSummary['total_paid'],
                'total_remaining_amount' => $paymentSummary['total_remaining'],
                'payment_status' => $paymentSummary['payment_status'],
            ],
            'property' => [
                'id' => $rental->unit_id,
                'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
                'building' => $rental->building,
                'project' => [
                    'id' => $rental->project_id,
                    'name' => optional($rental->project)->name,
                ],
            ],
            'contract' => $activeContract ? [
                'id' => $activeContract->id,
                'contract_number' => $rental->contract_number,
                'start_date' => $activeContract->start_date,
                'end_date' => $activeContract->end_date,
                'status' => $activeContract->status,
            ] : null,
            'payment_summary' => [
                'total_amount' => $paymentSummary['total_amount'],
                'total_paid' => $paymentSummary['total_paid'],
                'total_remaining' => $paymentSummary['total_remaining'],
                'payment_status' => $paymentSummary['payment_status'],
                'breakdown' => $paymentSummary['breakdown'],
            ],
            'installments' => $installmentDetails,
            'cost_items_breakdown' => $costItemsBreakdown,
            'recent_payments' => $paymentSummary['recent_payments']->load('costItem')->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_type' => $payment->payment_type,
                    'payment_type_label' => $payment->payment_type_label,
                    'cost_item_id' => $payment->cost_item_id,
                    'cost_item_name' => $payment->costItem?->name ?? null,
                    'installment_sequence' => $payment->installment_sequence,
                    'amount' => (float) $payment->amount,
                    'payment_date' => $payment->payment_date,
                    'payment_method' => $payment->payment_method,
                    'payment_method_label' => $payment->payment_method_label,
                    'reference' => $payment->reference,
                    'notes' => $payment->notes,
                ];
            }),
        ];
    }

    private function generateInstallments($userId, $rentalId, $contractId, array $data)
    {
        $plan = $data['paying_plan'];
        $totalAmount = $data['total_rental_amount'];
        $start = Carbon::parse($data['move_in_date']);
        $rentalDuration = (int) $data['rental_duration'];
        $rentalType = $data['rental_type'];

        // Calculate how many installments based on paying plan
        $chunks = match($plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        // Calculate total months for the rental
        $totalMonths = $this->calculateTotalMonthsFromDuration($rentalDuration, $rentalType);

        // Validate: Ensure total months is positive
        if ($totalMonths <= 0) {
            throw new \InvalidArgumentException('Rental duration must be at least 1 month');
        }

        // Calculate number of installments using ceil to ensure all months are covered
        // Example: 5 months with quarterly (3) = ceil(5/3) = 2 installments
        $numberOfInstallments = (int) ceil($totalMonths / $chunks);

        // Guard clause: Ensure we have at least 1 installment
        if ($numberOfInstallments < 1) {
            $numberOfInstallments = 1;
        }

        // Calculate installment amount
        $installmentAmount = round($totalAmount / $numberOfInstallments, 2);

        // Calculate the last installment to account for rounding differences
        $totalAllocated = $installmentAmount * ($numberOfInstallments - 1);
        $lastInstallmentAmount = round($totalAmount - $totalAllocated, 2);

        for ($i = 0; $i < $numberOfInstallments; $i++) {
            // Use adjusted amount for last installment to ensure total matches exactly
            $amount = ($i === $numberOfInstallments - 1) ? $lastInstallmentAmount : $installmentAmount;

            RmPaymentInstallment::create([
                'user_id' => $userId,
                'rental_id' => $rentalId,
                'contract_id' => $contractId,
                'sequence_no' => $i + 1,
                'due_date' => $start->copy()->addMonths($i * $chunks),
                'amount' => $amount,
                'status' => 'pending',
                'payment_type' => 'none',
                'payment_status' => 'not_due',
            ]);
        }
    }

    /**
     * Calculate total months based on rental duration and type
     */
    private function calculateTotalMonthsFromDuration($rentalDuration, $rentalType)
    {
        if ($rentalType === 'monthly') {
            return $rentalDuration; // duration is already in months
        } elseif ($rentalType === 'annual') {
            return $rentalDuration * 12; // convert years to months
        }

        return $rentalDuration; // fallback
    }

    public function getCurrentCollections($userId, $rentalId)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['activeContract', 'installments.payments'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        if (!$rental->activeContract) {
            return [
                'current_month' => [
                    'month' => now()->format('Y-m'),
                    'total_due' => 0,
                    'total_collected' => 0,
                    'remaining' => 0,
                    'tenants' => []
                ],
                'overdue' => [
                    'total_overdue' => 0,
                    'tenants' => []
                ],
                'summary' => [
                    'total_current_due' => 0,
                    'total_overdue' => 0,
                    'grand_total' => 0
                ]
            ];
        }

        $currentDate = now();
        $currentMonth = $currentDate->format('Y-m');
        $currentMonthStart = $currentDate->startOfMonth()->format('Y-m-d');
        $currentMonthEnd = $currentDate->endOfMonth()->format('Y-m-d');

        // Calculate fees for the rental
        $fees = $this->calculateRentalFees($rental);
        $monthlyFees = $fees['total_fees'];

        // Get current month installments
        $currentMonthInstallments = $rental->installments()
            ->where('contract_id', $rental->activeContract->id)
            ->whereBetween('due_date', [$currentMonthStart, $currentMonthEnd])
            ->get();

        // Get overdue installments (previous months with partial/unpaid amounts)
        $overdueInstallments = $rental->installments()
            ->where('contract_id', $rental->activeContract->id)
            ->where('due_date', '<', $currentMonthStart)
            ->get();

        // Process current month collections
        $currentMonthData = $this->processCurrentMonthCollections($rental, $currentMonthInstallments, $monthlyFees, $currentMonth);

        // Process overdue collections
        $overdueData = $this->processOverdueCollections($rental, $overdueInstallments, $monthlyFees);

        // Calculate summary totals
        $summary = [
            'total_current_due' => $currentMonthData['total_due'],
            'total_overdue' => $overdueData['total_overdue'],
            'grand_total' => $currentMonthData['total_due'] + $overdueData['total_overdue']
        ];

        return [
            'current_month' => $currentMonthData,
            'overdue' => $overdueData,
            'summary' => $summary
        ];
    }

    private function processCurrentMonthCollections($rental, $installments, $monthlyFees, $currentMonth)
    {
        $totalDue = 0;
        $totalCollected = 0;
        $tenants = [];

        foreach ($installments as $installment) {
            $rentAmount = (float) $installment->amount;
            $totalAmount = $rentAmount + $monthlyFees;
            $paidAmount = (float) $installment->paid_amount;
            $remainingAmount = $totalAmount - $paidAmount;

            // Only process installments with outstanding amounts
            if ($this->hasOutstandingAmount($installment)) {
                $totalDue += $totalAmount;
                $totalCollected += $paidAmount;

                $tenants[] = [
                    'rental_id' => $rental->id,
                    'tenant_name' => $rental->tenant_full_name,
                    'tenant_phone' => $rental->tenant_phone,
                    'installment_id' => $installment->id,
                    'sequence_no' => $installment->sequence_no,
                    'due_date' => $installment->due_date,
                    'monthly_amount' => $totalAmount,
                    'rent_amount' => $rentAmount,
                    'fees' => $monthlyFees,
                    'paid_amount' => $paidAmount,
                    'remaining' => $remainingAmount,
                    'status' => $this->getPaymentStatus($installment),
                    'is_late' => now()->isAfter($installment->due_date),
                    'payment_status' => $installment->payment_status
                ];
            }
        }

        return [
            'month' => $currentMonth,
            'total_due' => $totalDue,
            'total_collected' => $totalCollected,
            'remaining' => $totalDue - $totalCollected,
            'tenants' => $tenants
        ];
    }

    private function processOverdueCollections($rental, $installments, $monthlyFees)
    {
        $totalOverdue = 0;
        $tenants = [];

        foreach ($installments as $installment) {
            $rentAmount = (float) $installment->amount;
            $totalAmount = $rentAmount + $monthlyFees;
            $paidAmount = (float) $installment->paid_amount;
            $remainingAmount = $totalAmount - $paidAmount;

            // Only include if there's still money owed
            if ($remainingAmount > 0) {
                $totalOverdue += $remainingAmount;

                $tenants[] = [
                    'rental_id' => $rental->id,
                    'tenant_name' => $rental->tenant_full_name,
                    'tenant_phone' => $rental->tenant_phone,
                    'installment_id' => $installment->id,
                    'sequence_no' => $installment->sequence_no,
                    'due_date' => $installment->due_date,
                    'overdue_amount' => $remainingAmount,
                    'original_amount' => $totalAmount,
                    'paid_amount' => $paidAmount,
                    'status' => $this->getPaymentStatus($installment),
                    'days_overdue' => now()->diffInDays($installment->due_date),
                    'payment_status' => $installment->payment_status
                ];
            }
        }

        return [
            'total_overdue' => $totalOverdue,
            'tenants' => $tenants
        ];
    }

    private function getPaymentStatus($installment)
    {
        $paidAmount = (float) $installment->paid_amount;
        $totalAmount = (float) $installment->amount;
        $dueDate = \Carbon\Carbon::parse($installment->due_date);
        $isLate = now()->isAfter($dueDate);

        if ($paidAmount >= $totalAmount) {
            return $isLate ? 'paid_late' : 'paid';
        } elseif ($paidAmount > 0) {
            return $isLate ? 'partial_late' : 'partial';
        } else {
            return $isLate ? 'overdue' : 'unpaid';
        }
    }

    private function hasOutstandingAmount($installment)
    {
        $paidAmount = (float) $installment->paid_amount;
        $totalAmount = (float) $installment->amount;

        // Show if there's remaining amount OR if it's paid but we want to show paid status
        return $paidAmount < $totalAmount || $paidAmount > 0;
    }

    private function calculateRentalFees($rental)
    {
        // Use the FIXED amounts saved during rental creation
        // These should NOT be recalculated to maintain consistency
        $platformFee = (float) ($rental->platform_fee ?? 0);
        $waterFee = (float) ($rental->water_fee ?? 0);
        $officeFee = (float) ($rental->office_fee ?? 0);

        // Use the saved office_fee amount (calculated and saved during rental creation)
        // Do NOT recalculate office commission - use the fixed amount
        $officeCommission = $officeFee; // office_fee already contains the calculated commission

        $totalFees = $platformFee + $waterFee + $officeFee;

        return [
            'platform_fee' => $platformFee,
            'water_fee' => $waterFee,
            'office_fee' => $officeFee,
            'total_fees' => $totalFees
        ];
    }

    /**
     * Calculate total months based on rental_period and paying_plan
     */
    private function calculateTotalMonths($rentalPeriod, $payingPlan)
    {
        switch ($payingPlan) {
            case 'monthly':
                return $rentalPeriod * 1; // 1 month per period
            case 'quarterly':
                return $rentalPeriod * 3; // 3 months per period
            case 'semi_annual':
                return $rentalPeriod * 6; // 6 months per period
            case 'annual':
                return $rentalPeriod * 12; // 12 months per period
            default:
                return $rentalPeriod; // fallback to 1 month per period
        }
    }

    /**
     * Get payment collection data for a rental
     * Returns detailed payment information for collection interface
     */
    public function getPaymentCollectionData($userId, $rentalId)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with([
            'activeContract',
            'installments.payments',
            'property.project.contents',
            'property.contents',
            'property.building',
            'project.contents',
            'building',
            'tenantCostItems',
            'ownerCostItems'
        ])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        // Get property content in user's language
        $propertyContent = $this->getPropertyContent($rental->property, $ownerId);

        // Get project name in user's language
        $project = $rental->property?->project ?? $rental->project;
        $projectName = $this->getProjectName($project, $ownerId);

        // Get building name
        $building = $rental->property?->building ?? $rental->building;
        $buildingName = 'N/A';

        // Manually load the building if not an object (fix for relationship loading issue)
        if ((!$building || !is_object($building)) && ($rental->building_id || $rental->property?->building_id)) {
            $buildingId = $rental->building_id ?? $rental->property?->building_id;
            $building = \App\Models\Building::find($buildingId);
        }

        if ($building && is_object($building)) {
            $buildingName = $building->name ?? 'N/A';
        }

        if (!$rental->activeContract) {
            return [
                'rental_info' => [
                    'id' => $rental->id,
                    'tenant_name' => $rental->tenant_full_name,
                    'tenant_phone' => $rental->tenant_phone,
                    'tenant_email' => $rental->tenant_email,
                    'property_address' => $propertyContent['name'],
                    'building' => $rental->building,
                    'contract_number' => $rental->activeContract?->contract_number ?? 'N/A'
                ],
                'payment_details' => [
                    'items' => [],
                    'summary' => [
                        'total_due' => 0,
                        'total_paid' => 0,
                        'total_remaining' => 0
                    ]
                ]
            ];
        }

        // Get installments with payment details
        $installments = $rental->installments()
            ->where('contract_id', $rental->activeContract->id)
            ->orderBy('due_date')
            ->get();

        $totalInstallments = $installments->count();

        $items = $installments->map(function ($installment) {
            $paidAmount = (float) $installment->paid_amount;
            $rentAmount = (float) $installment->amount;
            $remainingAmount = round(max(0, $rentAmount - $paidAmount), 2);

            return [
                'id' => $installment->id,
                'sequence_no' => $installment->sequence_no,
                'due_date' => $installment->due_date,
                'rent_amount' => $rentAmount,
                'paid_amount' => $paidAmount,
                'remaining_amount' => $remainingAmount,
                'status' => $this->getInstallmentPaymentStatus($paidAmount, $rentAmount, $installment->due_date),
                'is_overdue' => now()->isAfter($installment->due_date) && $remainingAmount > 0
            ];
        });

        // Calculate cost items breakdown (NEW SYSTEM)
        $costItemsBreakdown = $this->calculateCostItemsBreakdown($rental);

        // Calculate summary with proper rounding
        $totalRentDue = round($installments->sum('amount'), 2);
        $totalCostItemsDue = round($costItemsBreakdown['summary']['total_cost_items_due'], 2);
        $totalDue = round($totalRentDue + $totalCostItemsDue, 2);
        $totalPaid = round($installments->sum('paid_amount'), 2);
        $totalCostItemsPaid = round($costItemsBreakdown['summary']['total_cost_items_paid'], 2);
        $totalCollected = round($totalPaid + $totalCostItemsPaid, 2);
        $totalRemaining = round($totalDue - $totalCollected, 2);

        return [
            'rental_info' => [
                'id' => $rental->id,
                'tenant_name' => $rental->tenant_full_name,
                'tenant_phone' => $rental->tenant_phone,
                'tenant_email' => $rental->tenant_email,
                'property_address' => $propertyContent['name'],
                'building' => $rental->building,
                'contract_number' => $rental->activeContract->contract_number
            ],
            'contract' => [
                'id' => $rental->activeContract->id,
                'contract_number' => $rental->activeContract->contract_number,
                'start_date' => $rental->activeContract->start_date
            ],
            'property' => [
                'id' => $rental->property?->id,
                'name' => $propertyContent['name'],
                'building' => $rental->building,
                'project' => [
                    'id' => $rental->property?->project?->id ?? $rental->project_id,
                    'name' => $projectName
                ]
            ],
            'cost_items_breakdown' => $costItemsBreakdown,
            'payment_details' => [
                'items' => $items,
                'summary' => [
                    'total_rent_due' => $totalRentDue,
                    'total_cost_items_due' => $totalCostItemsDue,
                    'total_due' => $totalDue,
                    'total_rent_paid' => $totalPaid,
                    'total_cost_items_paid' => $totalCostItemsPaid,
                    'total_collected' => $totalCollected,
                    'total_remaining' => $totalRemaining,
                    'overdue_count' => $items->where('is_overdue', true)->count(),
                    'paid_count' => $items->where('status', 'paid')->count(),
                    'partial_count' => $items->where('status', 'partial')->count(),
                    'unpaid_count' => $items->where('status', 'unpaid')->count()
                ]
            ]
        ];
    }

    /**
     * Calculate cost items breakdown for payment collection
     * Handles one_time vs per_installment costs
     */
    private function calculateCostItemsBreakdown($rental)
    {
        $monthsPerInstallment = $this->getMonthsPerInstallmentFromPlan($rental->paying_plan);

        $tenantCostItems = [];
        $ownerCostItems = [];

        $totalTenantOneTime = 0;
        $totalTenantPerInstallment = 0;
        $totalOwnerOneTime = 0;
        $totalOwnerPerInstallment = 0;

        // Process tenant cost items
        foreach ($rental->tenantCostItems as $costItem) {
            $itemData = $this->calculateCostItemDetails($costItem, $monthsPerInstallment, $rental);
            $tenantCostItems[] = $itemData;

            if ($costItem->payment_frequency === 'one_time') {
                $totalTenantOneTime += $itemData['total_amount'];
            } else {
                $totalTenantPerInstallment += $itemData['amount_per_installment'];
            }
        }

        // Process owner cost items
        foreach ($rental->ownerCostItems as $costItem) {
            $itemData = $this->calculateCostItemDetails($costItem, $monthsPerInstallment, $rental);
            $ownerCostItems[] = $itemData;

            if ($costItem->payment_frequency === 'one_time') {
                $totalOwnerOneTime += $itemData['total_amount'];
            } else {
                $totalOwnerPerInstallment += $itemData['amount_per_installment'];
            }
        }

        // Calculate totals
        $firstPaymentTenant = $totalTenantOneTime + $totalTenantPerInstallment;
        $subsequentPaymentsTenant = $totalTenantPerInstallment;
        $firstPaymentOwner = $totalOwnerOneTime + $totalOwnerPerInstallment;
        $subsequentPaymentsOwner = $totalOwnerPerInstallment;

        $totalCostItemsDue = $firstPaymentTenant + $firstPaymentOwner;

        // Calculate actual paid amount from rm_payments
        $totalCostItemsPaid = RmPayment::where('rental_id', $rental->id)
            ->where('payment_type', 'cost_item')
            ->whereNotNull('cost_item_id')
            ->sum('amount');

        return [
            'tenant_cost_items' => $tenantCostItems,
            'owner_cost_items' => $ownerCostItems,
            'summary' => [
                'tenant' => [
                    'one_time_costs' => round($totalTenantOneTime, 2),
                    'per_installment_costs' => round($totalTenantPerInstallment, 2),
                    'first_payment_total' => round($firstPaymentTenant, 2),
                    'subsequent_payments_total' => round($subsequentPaymentsTenant, 2),
                ],
                'owner' => [
                    'one_time_costs' => round($totalOwnerOneTime, 2),
                    'per_installment_costs' => round($totalOwnerPerInstallment, 2),
                    'first_payment_total' => round($firstPaymentOwner, 2),
                    'subsequent_payments_total' => round($subsequentPaymentsOwner, 2),
                ],
                'total_cost_items_due' => round($totalCostItemsDue, 2),
                'total_cost_items_paid' => round($totalCostItemsPaid, 2),
                'total_cost_items_remaining' => round($totalCostItemsDue - $totalCostItemsPaid, 2),
            ]
        ];
    }

    /**
     * Calculate details for a single cost item
     */
    private function calculateCostItemDetails($costItem, $monthsPerInstallment, $rental)
    {
        // Calculate base cost
        $baseCost = 0;
        if ($costItem->type === 'fixed') {
            $baseCost = (float) $costItem->cost;
        } elseif ($costItem->type === 'percentage') {
            $baseAmount = $costItem->percentage_of ?? $rental->total_rental_amount;
            $baseCost = ((float) $baseAmount * (float) $costItem->cost) / 100;
        }

        // Get paid amount from rm_payments
        $paidAmount = RmPayment::where('rental_id', $rental->id)
            ->where('cost_item_id', $costItem->id)
            ->where('payment_type', 'cost_item')
            ->sum('amount');

        // Calculate amounts based on payment frequency
        if ($costItem->payment_frequency === 'one_time') {
            $totalAmount = $baseCost;
            $remainingAmount = max(0, $totalAmount - $paidAmount);

            return [
                'id' => $costItem->id,
                'name' => $costItem->name,
                'cost' => round($baseCost, 2),
                'type' => $costItem->type,
                'payer' => $costItem->payer,
                'payment_frequency' => 'one_time',
                'description' => $costItem->description,
                'total_amount' => round($totalAmount, 2),
                'amount_per_installment' => 0,
                'paid_amount' => round($paidAmount, 2),
                'remaining_amount' => round($remainingAmount, 2),
                'applies_to_first_payment_only' => true,
                'payment_status' => $this->getCostItemPaymentStatus($paidAmount, $totalAmount),
            ];
        } else {
            // per_installment: calculate based on rental_type
            if ($rental->rental_type === 'annual') {
                // Annual: cost per year, divided by number of payments
                // Example: 100 SAR/year, 2 years, 4 payments = (100 × 2) / 4 = 50 SAR per installment
                $totalCostForContract = $baseCost * ($rental->rental_duration ?? 1);
                $numberOfPayments = $this->calculateNumberOfPayments($rental);
                $amountPerInstallment = ($numberOfPayments > 0) ? ($totalCostForContract / $numberOfPayments) : 0;

                return [
                    'id' => $costItem->id,
                    'name' => $costItem->name,
                    'cost_per_year' => round($baseCost, 2),
                    'type' => $costItem->type,
                    'payer' => $costItem->payer,
                    'payment_frequency' => 'per_installment',
                    'description' => $costItem->description,
                    'rental_duration_years' => $rental->rental_duration,
                    'number_of_payments' => $numberOfPayments,
                    'amount_per_installment' => round($amountPerInstallment, 2),
                    'total_amount' => round($amountPerInstallment, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'remaining_amount' => round(max(0, $amountPerInstallment - $paidAmount), 2),
                    'applies_to_all_payments' => true,
                    'payment_status' => $this->getCostItemPaymentStatus($paidAmount, $amountPerInstallment),
                ];
            } else {
                // Monthly: cost per month × months per installment
                // Example: 100 SAR/month × 6 months = 600 SAR per installment
                $amountPerInstallment = $baseCost * $monthsPerInstallment;

                return [
                    'id' => $costItem->id,
                    'name' => $costItem->name,
                    'cost_per_month' => round($baseCost, 2),
                    'type' => $costItem->type,
                    'payer' => $costItem->payer,
                    'payment_frequency' => 'per_installment',
                    'description' => $costItem->description,
                    'months_per_installment' => $monthsPerInstallment,
                    'amount_per_installment' => round($amountPerInstallment, 2),
                    'total_amount' => round($amountPerInstallment, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'remaining_amount' => round(max(0, $amountPerInstallment - $paidAmount), 2),
                    'applies_to_all_payments' => true,
                    'payment_status' => $this->getCostItemPaymentStatus($paidAmount, $amountPerInstallment),
                ];
            }
        }
    }

    /**
     * Get payment status for cost item
     */
    private function getCostItemPaymentStatus($paidAmount, $totalAmount)
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * Get months per installment from paying plan
     */
    private function getMonthsPerInstallmentFromPlan($payingPlan)
    {
        return match($payingPlan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };
    }

    /**
     * Calculate total number of payments for a rental
     */
    private function calculateNumberOfPayments($rental)
    {
        if (is_null($rental->rental_duration) ||
            is_null($rental->rental_type) ||
            is_null($rental->paying_plan) ||
            $rental->rental_duration <= 0) {
            return 0;
        }

        // Calculate total months
        $totalMonths = ($rental->rental_type === 'monthly')
            ? $rental->rental_duration
            : $rental->rental_duration * 12;

        // Calculate payment interval
        $paymentInterval = match($rental->paying_plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        // Calculate number of payments
        return ceil($totalMonths / $paymentInterval);
    }

    /**
     * Validate that installment IDs belong to the specified rental
     */
    public function validateInstallmentsForRental($userId, $rentalId, $installmentIds)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $validInstallmentIds = RmPaymentInstallment::whereHas('rental', function ($query) use ($ownerId, $rentalId) {
            $query->where('user_id', $ownerId)->where('id', $rentalId);
        })->pluck('id')->toArray();

        $invalidIds = $installmentIds->diff($validInstallmentIds);

        if ($invalidIds->isNotEmpty()) {
            throw new \InvalidArgumentException(
                'Invalid installment IDs: ' . $invalidIds->implode(', ') .
                '. These installments do not belong to the specified rental.'
            );
        }
    }

    /**
     * Get payment collection data for all rentals of the authenticated user
     * Returns summary and paginated list of rentals with payment collection data
     */
    public function getAllPaymentCollections($userId, array $filters = [])
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        // Pagination parameters
        $perPage = $filters['per_page'] ?? 15;
        $perPage = min($perPage, 100); // Max 100 per page
        $page = $filters['page'] ?? 1;

        // Build base query
        $query = RmRental::with(['activeContract', 'property.contents', 'property.building', 'project.contents', 'building', 'tenantCostItems', 'ownerCostItems'])
            ->where('user_id', $ownerId);

        // Apply filters
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['property_id'])) {
            $query->where('unit_id', $filters['property_id']);
        }

        if (!empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('move_in_date', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('move_in_date', '<=', $filters['to_date']);
        }

        // Get paginated rentals
        $rentals = $query->orderBy('created_at', 'desc')->paginate($perPage, ['*'], 'page', $page);

        // Initialize summary totals
        $summaryTotalDue = 0;
        $summaryTotalCollected = 0;
        $summaryTotalRemaining = 0;

        // Process each rental
        $rentalsList = [];
        foreach ($rentals as $rental) {
            // Skip rentals without active contract
            if (!$rental->activeContract) {
                continue;
            }

            // Get installments with payment details
            $installments = $rental->installments()
                ->where('contract_id', $rental->activeContract->id)
                ->orderBy('due_date')
                ->get();

            // Calculate cost items breakdown (NEW SYSTEM)
            $costItemsBreakdown = $this->calculateCostItemsBreakdown($rental);

            // Calculate totals for this rental
            $totalRentDue = round($installments->sum('amount'), 2);
            $totalCostItemsDue = round($costItemsBreakdown['summary']['total_cost_items_due'], 2);
            $totalDue = round($totalRentDue + $totalCostItemsDue, 2);
            $totalPaid = round($installments->sum('paid_amount'), 2);

            // Get cost items paid
            $totalCostItemsPaid = round($costItemsBreakdown['summary']['total_cost_items_paid'], 2);
            $totalCollected = round($totalPaid + $totalCostItemsPaid, 2);
            $totalRemaining = round($totalDue - $totalCollected, 2);

            // Add to summary
            $summaryTotalDue += $totalDue;
            $summaryTotalCollected += $totalCollected;
            $summaryTotalRemaining += $totalRemaining;

            // Map installments
            $items = $installments->map(function ($installment) {
                $paidAmount = (float) $installment->paid_amount;
                $rentAmount = (float) $installment->amount;
                $remainingAmount = round(max(0, $rentAmount - $paidAmount), 2);

                return [
                    'id' => $installment->id,
                    'sequence_no' => $installment->sequence_no,
                    'due_date' => $installment->due_date,
                    'rent_amount' => $rentAmount,
                    'paid_amount' => $paidAmount,
                    'remaining_amount' => $remainingAmount,
                    'status' => $this->getInstallmentPaymentStatus($paidAmount, $rentAmount, $installment->due_date),
                    'is_overdue' => now()->isAfter($installment->due_date) && $remainingAmount > 0
                ];
            });

            // Get property content and project name in user's language
            $propertyContent = $this->getPropertyContent($rental->property, $ownerId);
            $projectName = $this->getProjectName($rental->project, $ownerId);

            // Build rental data
            $rentalData = [
                'rental_id' => $rental->id,
                'tenant_name' => $rental->tenant_full_name,
                'tenant_phone' => $rental->tenant_phone,
                'tenant_email' => $rental->tenant_email,
                'property_id' => $rental->unit_id,
                'property_name' => $propertyContent['name'],
                'project_id' => $rental->project_id,
                'project_name' => $projectName,
                'building' => $rental->building,
                'contract_number' => $rental->activeContract->contract_number ?? 'N/A',
                'status' => $rental->status,
                'move_in_date' => $rental->move_in_date?->toDateString(),
                'currency' => $rental->currency ?? 'SAR',
                'cost_items_breakdown' => $costItemsBreakdown,
                'payment_summary' => [
                    'total_rent_due' => $totalRentDue,
                    'total_cost_items_due' => $totalCostItemsDue,
                    'total_due' => $totalDue,
                    'total_rent_collected' => $totalPaid,
                    'total_cost_items_collected' => $totalCostItemsPaid,
                    'total_collected' => $totalCollected,
                    'total_remaining' => $totalRemaining,
                    'overdue_count' => $items->where('is_overdue', true)->count(),
                    'paid_count' => $items->where('status', 'paid')->count(),
                    'partial_count' => $items->where('status', 'partial')->count(),
                    'unpaid_count' => $items->where('status', 'unpaid')->count()
                ],
                'installments' => $items->values()->toArray()
            ];

            $rentalsList[] = $rentalData;
        }

        return [
            'summary' => [
                'total_rentals' => count($rentalsList),
                'total_due' => round($summaryTotalDue, 2),
                'total_collected' => round($summaryTotalCollected, 2),
                'total_remaining' => round($summaryTotalRemaining, 2),
                'collection_percentage' => $summaryTotalDue > 0
                    ? round(($summaryTotalCollected / $summaryTotalDue) * 100, 2)
                    : 0
            ],
            'rentals' => $rentalsList,
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
        ];
    }

    /**
     * Get total fees collected for a rental
     */
    private function getTotalFeesCollected($rentalId)
    {
        return RmPayment::where('rental_id', $rentalId)
            ->whereIn('payment_type', ['platform_fee', 'water_fee', 'office_fee'])
            ->sum('amount');
    }

    /**
     * Get installment payment status
     */
    private function getInstallmentPaymentStatus($paidAmount, $totalAmount, $dueDate)
    {
        if ($paidAmount <= 0) {
            return now()->isAfter($dueDate) ? 'overdue' : 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * Get paid amount for a specific fee type
     */
    private function getPaidAmountForFee($rentalId, $feeType)
    {
        return \App\Models\Api\Rms\RmPayment::where('rental_id', $rentalId)
            ->where('payment_type', $feeType)
            ->sum('amount');
    }

    /**
     * Get fee payment status
     */
    private function getFeePaymentStatus($totalAmount, $paidAmount)
    {
        if ($paidAmount <= 0) {
            return 'unpaid';
        }

        if ($paidAmount >= $totalAmount) {
            return 'paid';
        }

        return 'partial';
    }

    /**
     * End rental contract by updating the active contract's end date and status
     */
    public function endRentalContract($userId, $rentalId, array $data)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        return DB::transaction(function () use ($ownerId, $rentalId, $data) {
            // Find the rental
            $rental = RmRental::where('user_id', $ownerId)->findOrFail($rentalId);

            // Find the active contract
            $activeContract = $rental->activeContract;
            if (!$activeContract) {
                throw new \Exception('No active contract found for this rental');
            }

            // Update the contract
            $contractData = [
                'end_date' => $data['end_date'],
                'status' => 'terminated',
                'termination_reason' => $data['termination_reason'] ?? 'Contract ended by user',
                'updated_by' => $ownerId,
            ];

            $activeContract->update($contractData);

            // Update any pending installments to reflect the contract termination
            $pendingInstallments = RmPaymentInstallment::where('contract_id', $activeContract->id)
                ->where('status', 'pending')
                ->where('due_date', '>', $data['end_date'])
                ->get();

            foreach ($pendingInstallments as $installment) {
                $installment->update([
                    'status' => 'cancelled',
                    'payment_status' => 'cancelled'
                ]);
            }

            // Update rental status if needed
            $rental->update([
                'status' => 'ended',
                'updated_by' => $ownerId
            ]);

            // Update property status
            if ($rental->unit_id) {
                $property = Property::where('id', $rental->unit_id)
                    ->where('user_id', $ownerId)
                    ->first();
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            return $rental->fresh(['activeContract', 'contracts']);
        });
    }

    /**
     * Update rental status with validation
     */
    public function updateRentalStatus($userId, $id, array $data)
    {
        return DB::transaction(function () use ($userId, $id, $data) {
            $rental = RmRental::where('user_id', $userId)->findOrFail($id);

            $currentStatus = $rental->status;
            $newStatus = $data['status'];

            // Validate status transitions
            $this->validateStatusTransition($currentStatus, $newStatus);

            // Prepare update data
            $updateData = [
                'status' => $newStatus,
                'notes' => $data['notes'] ?? $rental->notes
            ];

            // Add end_date if status is 'ended' and end_date is provided
            if ($newStatus === 'ended' && !empty($data['end_date'])) {
                $updateData['end_date'] = $data['end_date'];
            }

            // Update status
            $rental->update($updateData);

            // CASCADE: When rental status changes to 'ended', terminate active contract
            if ($newStatus === 'ended') {
                $activeContract = $rental->activeContract;
                if ($activeContract && $activeContract->status === 'active') {
                    $endDate = $data['end_date'] ?? now()->toDateString();

                    // Terminate the contract
                    $activeContract->update([
                        'status' => 'terminated',
                        'end_date' => $endDate,
                        'termination_reason' => $data['termination_reason'] ?? 'Rental ended by user',
                        'updated_by' => $userId
                    ]);

                    // Cancel future installments after end date
                    RmPaymentInstallment::where('contract_id', $activeContract->id)
                        ->where('status', 'pending')
                        ->where('due_date', '>', $endDate)
                        ->update([
                            'status' => 'cancelled',
                            'payment_status' => 'cancelled'
                        ]);

                    \Log::info('Contract automatically terminated due to rental status change', [
                        'rental_id' => $rental->id,
                        'contract_id' => $activeContract->id,
                        'user_id' => $userId
                    ]);
                }
            }

            // CASCADE: When rental status changes to 'cancelled', terminate/cancel contracts
            if ($newStatus === 'cancelled') {
                $activeContract = $rental->activeContract;
                if ($activeContract && in_array($activeContract->status, ['active', 'pending'])) {
                    $activeContract->update([
                        'status' => 'cancelled',
                        'termination_reason' => $data['termination_reason'] ?? 'Rental cancelled by user',
                        'updated_by' => $userId
                    ]);

                    // Cancel all pending installments
                    RmPaymentInstallment::where('contract_id', $activeContract->id)
                        ->where('status', 'pending')
                        ->update([
                            'status' => 'cancelled',
                            'payment_status' => 'cancelled'
                        ]);
                }
            }

            // Update property status if needed
            if ($rental->unit_id) {
                $property = \App\Models\User\RealestateManagement\Property::find($rental->unit_id);
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            return $rental->fresh(['activeContract', 'contracts']);
        });
    }

    /**
     * Validate status transitions
     */
    private function validateStatusTransition($currentStatus, $newStatus)
    {
        $allowedTransitions = [
            'draft' => ['active', 'cancelled'],
            'active' => ['ended', 'cancelled'],
            'ended' => [], // No transitions from ended
            'cancelled' => [] // No transitions from cancelled
        ];

        if (!isset($allowedTransitions[$currentStatus])) {
            throw new \Exception("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $allowedTransitions[$currentStatus])) {
            throw new \Exception("Cannot transition from '{$currentStatus}' to '{$newStatus}'. Allowed transitions: " . implode(', ', $allowedTransitions[$currentStatus]));
        }
    }

    /**
     * Renew an ended rental by creating a new rental record
     * Copies tenant and property information from the old rental
     * Creates new contract with new duration and amounts
     */
    public function renewRental($userId, $oldRentalId, array $data)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        return DB::transaction(function () use ($ownerId, $oldRentalId, $data) {
            // Find the old rental
            $oldRental = RmRental::where('user_id', $ownerId)->findOrFail($oldRentalId);

            // Validate that the rental is ended
            if ($oldRental->status !== 'ended') {
                throw new \Exception('Only ended rentals can be renewed. Current status: ' . $oldRental->status);
            }

            // Check if unit already has an active rental
            if (!empty($oldRental->unit_id)) {
                $existingActiveRental = RmRental::where('unit_id', $oldRental->unit_id)
                    ->where('user_id', $ownerId)
                    ->whereIn('status', ['active', 'draft'])
                    ->exists();

                if ($existingActiveRental) {
                    throw new \Exception('This unit already has an active contract. Please end the existing contract before renewing.');
                }
            }

            // Extract cost items from data
            $costItems = $data['cost_items'] ?? [];
            unset($data['cost_items']);

            // Prepare building_id: use old rental's building_id or fetch from property
            $buildingId = $oldRental->building_id;
            if (empty($buildingId) && !empty($oldRental->unit_id)) {
                $property = Property::find($oldRental->unit_id);
                if ($property && $property->building_id) {
                    $buildingId = $property->building_id;
                }
            }

            // Prepare new rental data by copying from old rental and merging with new data
            $newRentalData = [
                'user_id' => $ownerId,
                // Copy tenant information
                'tenant_full_name' => $oldRental->tenant_full_name,
                'tenant_phone' => $oldRental->tenant_phone,
                'tenant_email' => $oldRental->tenant_email,
                'tenant_job_title' => $oldRental->tenant_job_title,
                'tenant_social_status' => $oldRental->tenant_social_status,
                'tenant_national_id' => $oldRental->tenant_national_id,
                // Copy property information
                'unit_id' => $oldRental->unit_id,
                'project_id' => $oldRental->project_id,
                'building_id' => $buildingId,
                // New rental details from request
                'rental_type' => $data['rental_type'],
                'rental_duration' => $data['rental_duration'],
                'paying_plan' => $data['paying_plan'],
                'total_rental_amount' => $data['total_rental_amount'],
                'currency' => $data['currency'] ?? $oldRental->currency ?? 'SAR',
                'move_in_date' => now()->toDateString(), // Start from today
                'notes' => $data['notes'] ?? null,
                'status' => 'active',
                'created_by' => $ownerId,
                'updated_by' => $ownerId,
            ];

            // Create new rental
            $newRental = RmRental::create($newRentalData);

            // Create cost items if provided
            if (!empty($costItems)) {
                foreach ($costItems as $costItemData) {
                    $newRental->costItems()->create(array_merge($costItemData, [
                        'user_id' => $ownerId,
                    ]));
                }
            }

            // Update property status based on active rentals
            if ($newRental->unit_id) {
                $property = Property::where('id', $newRental->unit_id)
                    ->where('user_id', $ownerId)
                    ->first();
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            // Create contract and installments if we have enough data
            $hasEnoughData = $newRentalData['move_in_date']
                && $newRentalData['rental_duration']
                && $newRentalData['paying_plan']
                && $newRentalData['total_rental_amount'];

            if ($hasEnoughData) {
                // Calculate total months based on rental_duration and rental_type
                $totalMonths = $this->calculateTotalMonthsFromDuration(
                    $newRentalData['rental_duration'],
                    $newRentalData['rental_type']
                );

                $contract = RmContract::create([
                    'user_id' => $ownerId,
                    'rental_id' => $newRental->id,
                    'start_date' => $newRentalData['move_in_date'],
                    'end_date' => Carbon::parse($newRentalData['move_in_date'])->addMonths($totalMonths)->subDay(),
                    'status' => 'active',
                    // Snapshot identifiers for audit/history
                    'property_id' => $newRental->unit_id,
                    'project_id' => $newRental->project_id,
                    'property_name' => $newRental->property_name ?? null,
                    'project_name' => $newRental->project_name ?? null,
                    'grace_period_months' => 0,
                    'created_by' => $ownerId,
                    'updated_by' => $ownerId,
                ]);

                $this->generateInstallments($ownerId, $newRental->id, $contract->id, $newRentalData);

                return [
                    'id' => $newRental->id,
                    'old_rental_id' => $oldRental->id,
                    'status' => 'active',
                    'message' => 'Rental renewed successfully',
                    'contract' => [
                        'id' => $contract->id,
                        'status' => $contract->status,
                        'start_date' => $contract->start_date,
                        'end_date' => $contract->end_date,
                    ],
                    'cost_items' => $newRental->costItems
                ];
            }

            return [
                'id' => $newRental->id,
                'old_rental_id' => $oldRental->id,
                'status' => 'active',
                'message' => 'Rental renewed successfully',
                'cost_items' => $newRental->costItems
            ];
        });
    }

    /**
     * Get property payment report with collected vs outstanding payments
     *
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function getPropertyPaymentReport($userId, $filters = [])
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        // Parse filters
        $fromDate = $filters['from_date'] ?? now()->startOfYear()->toDateString();
        $toDate = $filters['to_date'] ?? now()->endOfYear()->toDateString();
        $propertyId = $filters['property_id'] ?? null;
        $projectId = $filters['project_id'] ?? null;
        $buildingId = $filters['building_id'] ?? null;

        // Get all properties for the user
        $propertiesQuery = Property::where('user_id', $ownerId)
            ->with(['rentals' => function ($q) use ($fromDate, $toDate) {
                $q->with(['tenantCostItems', 'ownerCostItems'])
                  ->whereHas('contracts', function ($contractQuery) use ($fromDate, $toDate) {
                    $contractQuery->where(function ($dateQuery) use ($fromDate, $toDate) {
                        $dateQuery->whereBetween('start_date', [$fromDate, $toDate])
                            ->orWhereBetween('end_date', [$fromDate, $toDate])
                            ->orWhere(function ($overlapQuery) use ($fromDate, $toDate) {
                                $overlapQuery->where('start_date', '<=', $fromDate)
                                    ->where('end_date', '>=', $toDate);
                            });
                    });
                });
            }]);

        // Apply filters
        if ($propertyId) {
            $propertiesQuery->where('id', $propertyId);
        }

        if ($projectId) {
            $propertiesQuery->where('project_id', $projectId);
        }

        if ($buildingId) {
            $propertiesQuery->where('building_id', $buildingId);
        }

        $properties = $propertiesQuery->get();

        $reportData = [];
        $grandTotalExpected = 0;
        $grandTotalCollected = 0;
        $grandTotalOutstanding = 0;

        foreach ($properties as $property) {
            $propertyData = [
                'property_id' => $property->id,
                'property_name' => $property->name ?? 'N/A',
                'property_address' => $property->address ?? 'N/A',
                'building' => $property->building->name ?? 'N/A',
                'project' => $property->project->name ?? 'N/A',
                'total_expected' => 0,
                'total_collected' => 0,
                'total_outstanding' => 0,
                'rentals' => []
            ];

            foreach ($property->rentals as $rental) {
                // Get contracts for this rental within date range
                $contracts = $rental->contracts()
                    ->where(function ($q) use ($fromDate, $toDate) {
                        $q->whereBetween('start_date', [$fromDate, $toDate])
                            ->orWhereBetween('end_date', [$fromDate, $toDate])
                            ->orWhere(function ($overlapQuery) use ($fromDate, $toDate) {
                                $overlapQuery->where('start_date', '<=', $fromDate)
                                    ->where('end_date', '>=', $toDate);
                            });
                    })
                    ->get();

                if ($contracts->isEmpty()) {
                    continue; // Skip rentals without contracts in date range
                }

                // Get all installments for this rental within date range
                $installments = $rental->installments()
                    ->whereBetween('due_date', [$fromDate, $toDate])
                    ->get();

                // Get all payments for this rental within date range
                $payments = $rental->payments()
                    ->whereBetween('payment_date', [$fromDate, $toDate])
                    ->orderBy('payment_date', 'desc')
                    ->get();

                // Calculate cost items breakdown (NEW SYSTEM)
                $costItemsBreakdown = $this->calculateCostItemsBreakdown($rental);

                // Calculate expected amount from installments + cost_items
                $expectedRent = round($installments->sum('amount'), 2);
                $expectedCostItems = round($costItemsBreakdown['summary']['total_cost_items_due'], 2);
                $expectedDeposit = round($rental->deposit_amount ?? 0, 2);
                $totalExpected = round($expectedRent + $expectedCostItems + $expectedDeposit, 2);

                // Calculate collected amount
                $collectedRent = round($payments->where('payment_type', 'rent')->sum('amount'), 2);
                $collectedCostItems = round($payments->where('payment_type', 'cost_item')->sum('amount'), 2);
                $collectedDeposit = round($payments->where('payment_type', 'deposit')->sum('amount'), 2);
                $totalCollected = round($collectedRent + $collectedCostItems + $collectedDeposit, 2);

                // Calculate outstanding
                $totalOutstanding = round(max(0, $totalExpected - $totalCollected), 2);

                // Build payment history
                $paymentHistory = $payments->load('costItem')->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'payment_type' => $payment->payment_type,
                        'payment_type_label' => $payment->payment_type_label,
                        'cost_item_id' => $payment->cost_item_id,
                        'cost_item_name' => $payment->costItem?->name ?? null,
                        'installment_sequence' => $payment->installment_sequence,
                        'amount' => round((float) $payment->amount, 2),
                        'payment_date' => $payment->payment_date,
                        'payment_method' => $payment->payment_method,
                        'bank_name' => $payment->bank_name,
                        'transfer_to' => $payment->transfer_to ?? null,
                        'reference' => $payment->reference,
                        'notes' => $payment->notes,
                        'receipt_image_url' => $payment->receipt_image_path ? url($payment->receipt_image_path) : null,
                        'created_at' => $payment->created_at->toDateTimeString(),
                    ];
                });

                $rentalData = [
                    'rental_id' => $rental->id,
                    'tenant_name' => $rental->tenant_full_name,
                    'tenant_phone' => $rental->tenant_phone,
                    'tenant_email' => $rental->tenant_email,
                    'contract_number' => $rental->contract_number,
                    'status' => $rental->status,
                    'move_in_date' => $rental->move_in_date?->toDateString(),
                    'base_rent_amount' => round((float) $rental->base_rent_amount, 2),
                    'currency' => $rental->currency ?? 'SAR',
                    'payment_breakdown' => [
                        'rent' => [
                            'expected' => round($expectedRent, 2),
                            'collected' => round($collectedRent, 2),
                            'outstanding' => round(max(0, $expectedRent - $collectedRent), 2),
                        ],
                        'cost_items' => [
                            'expected' => round($expectedCostItems, 2),
                            'collected' => round($collectedCostItems, 2),
                            'outstanding' => round(max(0, $expectedCostItems - $collectedCostItems), 2),
                        ],
                        'deposit' => [
                            'expected' => round($expectedDeposit, 2),
                            'collected' => round($collectedDeposit, 2),
                            'outstanding' => round(max(0, $expectedDeposit - $collectedDeposit), 2),
                        ],
                    ],
                    'cost_items_breakdown' => $costItemsBreakdown,
                    'total_expected' => round($totalExpected, 2),
                    'total_collected' => round($totalCollected, 2),
                    'total_outstanding' => round($totalOutstanding, 2),
                    'installments_count' => $installments->count(),
                    'payments_count' => $payments->count(),
                    'payment_history' => $paymentHistory->values()->toArray(),
                ];

                $propertyData['rentals'][] = $rentalData;
                $propertyData['total_expected'] += $totalExpected;
                $propertyData['total_collected'] += $totalCollected;
                $propertyData['total_outstanding'] += $totalOutstanding;
            }

            // Only include properties with rentals
            if (!empty($propertyData['rentals'])) {
                $propertyData['total_expected'] = round($propertyData['total_expected'], 2);
                $propertyData['total_collected'] = round($propertyData['total_collected'], 2);
                $propertyData['total_outstanding'] = round($propertyData['total_outstanding'], 2);
                $propertyData['rentals_count'] = count($propertyData['rentals']);

                $reportData[] = $propertyData;

                $grandTotalExpected += $propertyData['total_expected'];
                $grandTotalCollected += $propertyData['total_collected'];
                $grandTotalOutstanding += $propertyData['total_outstanding'];
            }
        }

        return [
            'filters' => [
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'property_id' => $propertyId,
                'project_id' => $projectId,
                'building_id' => $buildingId,
            ],
            'summary' => [
                'total_properties' => count($reportData),
                'total_expected' => round($grandTotalExpected, 2),
                'total_collected' => round($grandTotalCollected, 2),
                'total_outstanding' => round($grandTotalOutstanding, 2),
                'collection_percentage' => $grandTotalExpected > 0
                    ? round(($grandTotalCollected / $grandTotalExpected) * 100, 2)
                    : 0,
            ],
            'properties' => $reportData,
        ];
    }

    /**
     * Calculate expected fees for a rental based on installment count
     */
    private function calculateExpectedFees($rental, $installmentCount)
    {
        $platformFee = ($rental->platform_fee ?? 0) * $installmentCount;
        $waterFee = ($rental->water_fee ?? 0) * $installmentCount;
        $officeFee = ($rental->office_fee ?? 0) * $installmentCount;

        return round($platformFee + $waterFee + $officeFee, 2);
    }

    /**
     * Get daily follow-up data for rentals with payment due dates
     *
     * @param int $userId
     * @param array $filters
     * @return array
     */
    public function getDailyFollowUp($userId, array $filters = [])
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        // Pagination parameters
        $perPage = $filters['per_page'] ?? 15;
        $perPage = min($perPage, 100); // Max 100 per page
        $page = $filters['page'] ?? 1;

        // Date parameters
        $today = now()->toDateString();
        $fromDate = $filters['from_date'] ?? $today;
        $toDate = $filters['to_date'] ?? $today;

        // Status filter: overdue, due_today, upcoming
        $status = $filters['status'] ?? 'due_today';

        // Building query for installments based on status
        $installmentsQuery = RmPaymentInstallment::with([
            'rental.activeContract',
            'rental.property.project.contents', // Eager load project contents for language support
            'rental.property.building',
            'rental.building', // Also load rental's direct building relationship
            'rental.property.contents', // Eager load property contents for language support
            'rental.project.contents', // Also load from rental's direct project relationship
            'contract',
            'payments'
        ])
        ->whereHas('rental', function($q) use ($ownerId, $filters) {
            $q->where('user_id', $ownerId)
              ->whereIn('status', ['active']);

            // Building filter - check both rental's building_id and property's building_id
            if (!empty($filters['building_id'])) {
                $q->where(function($subQuery) use ($filters) {
                    $subQuery->where('building_id', $filters['building_id'])
                             ->orWhereHas('property', function($propQuery) use ($filters) {
                                 $propQuery->where('building_id', $filters['building_id']);
                             });
                });
            }
        })
        ->whereIn('status', ['pending', 'active']);

        // Apply status-based date filters
        switch ($status) {
            case 'overdue':
                $installmentsQuery->whereDate('due_date', '<', $today);
                break;
            case 'due_today':
                if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                    // If date range is provided, use it
                    $installmentsQuery->whereBetween('due_date', [$fromDate, $toDate]);
                } else {
                    // Default to today
                    $installmentsQuery->whereDate('due_date', '=', $today);
                }
                break;
            case 'upcoming':
                $installmentsQuery->whereDate('due_date', '>', $today);
                break;
            default:
                // If date range is provided, use it
                if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                    $installmentsQuery->whereBetween('due_date', [$fromDate, $toDate]);
                } else {
                    // Default to today
                    $installmentsQuery->whereDate('due_date', '=', $today);
                }
        }

        // Sort by due date (ascending)
        $installmentsQuery->orderBy('due_date', 'asc');

        // Get paginated installments
        $installments = $installmentsQuery->paginate($perPage, ['*'], 'page', $page);

        // Process each installment to build the response
        $followUpList = [];
        $totalAmountDue = 0;
        $totalArrears = 0;
        $totalOverdueArrears = 0;

        foreach ($installments as $installment) {
            $rental = $installment->rental;

            if (!$rental || !$rental->activeContract) {
                continue;
            }

            // Calculate paid amount for this installment
            $paidAmount = $installment->payments()->sum('amount');
            $remainingAmount = max(0, $installment->amount - $paidAmount);

            // Calculate overdue arrears (only if past due date)
            $overdueArrears = 0;
            if ($installment->due_date < $today && $remainingAmount > 0) {
                $overdueArrears = $remainingAmount;
            }

            // Calculate total arrears for this rental (all unpaid amounts across all installments)
            $allInstallments = $rental->installments()
                ->where('contract_id', $rental->activeContract->id)
                ->whereIn('status', ['pending', 'active'])
                ->get();

            $totalUnpaidAmount = 0;
            foreach ($allInstallments as $inst) {
                $instPaid = $inst->payments()->sum('amount');
                $instRemaining = max(0, $inst->amount - $instPaid);
                $totalUnpaidAmount += $instRemaining;
            }

            // Get property content in user's default language
            $propertyContent = $this->getPropertyContent($rental->property, $ownerId);

            // Get project name in user's language (try property's project first, then rental's project)
            $project = $rental->property?->project ?? $rental->project;
            $projectName = $this->getProjectName($project, $ownerId);

            // Get building name (try property's building first, then rental's building)
            $building = $rental->property?->building ?? $rental->building;
            $buildingName = 'N/A';

            // Manually load the building if not an object (fix for relationship loading issue)
            if ((!$building || !is_object($building)) && ($rental->building_id || $rental->property?->building_id)) {
                $buildingId = $rental->building_id ?? $rental->property?->building_id;
                $building = \App\Models\Building::find($buildingId);
            }

            if ($building && is_object($building)) {
                $buildingName = $building->name ?? 'N/A';
            }

            $followUpItem = [
                'rental_id' => $rental->id,
                'contract_number' => $rental->contract_number ?? 'N/A',
                'tenant_name' => $rental->tenant_full_name,
                'mobile_number' => $rental->tenant_phone,
                'email' => $rental->tenant_email,
                'unit_information' => [
                    'unit_id' => $rental->unit_id,
                    'unit_name' => $propertyContent['name'],
                    'unit_address' => $propertyContent['address'],
                ],
                'building' => [
                    'building_id' => $rental->property?->building_id ?? $rental->building_id,
                    'building_name' => $buildingName,
                ],
                'project' => [
                    'project_id' => $rental->property?->project_id ?? $rental->project_id,
                    'project_name' => $projectName,
                ],
                'installment_info' => [
                    'installment_id' => $installment->id,
                    'sequence_no' => $installment->sequence_no,
                    'amount' => round($installment->amount, 2),
                    'paid_amount' => round($paidAmount, 2),
                    'remaining_amount' => round($remainingAmount, 2),
                ],
                'amount_to_be_paid' => round($remainingAmount, 2),
                'rental_method' => $rental->paying_plan,
                'arrears' => [
                    'total_unpaid_amount' => round($totalUnpaidAmount, 2), // All unpaid amounts
                    'overdue_amount' => round($overdueArrears, 2), // Only overdue amounts
                ],
                'due_date' => $installment->due_date->format('Y-m-d'),
                'days_overdue' => $installment->due_date < $today
                    ? now()->diffInDays($installment->due_date)
                    : 0,
                'contract_info' => [
                    'contract_id' => $rental->activeContract->id,
                    'start_date' => $rental->activeContract->start_date->format('Y-m-d'),
                    'end_date' => $rental->activeContract->end_date->format('Y-m-d'),
                    'status' => $rental->activeContract->status,
                ],
                'contract_expiration_date' => $rental->activeContract->end_date->format('Y-m-d'),
                'payment_status' => $remainingAmount <= 0 ? 'paid' : ($installment->due_date < $today ? 'overdue' : 'pending'),
            ];

            $followUpList[] = $followUpItem;

            // Update totals
            $totalAmountDue += $remainingAmount;
            $totalArrears += $totalUnpaidAmount;
            $totalOverdueArrears += $overdueArrears;
        }

        return [
            'data' => $followUpList,
            'pagination' => [
                'current_page' => $installments->currentPage(),
                'per_page' => $installments->perPage(),
                'total' => $installments->total(),
                'last_page' => $installments->lastPage(),
                'from' => $installments->firstItem(),
                'to' => $installments->lastItem(),
                'has_more_pages' => $installments->hasMorePages(),
                'next_page_url' => $installments->nextPageUrl(),
                'prev_page_url' => $installments->previousPageUrl(),
            ],
            'summary' => [
                'total_amount_due' => round($totalAmountDue, 2),
                'total_arrears' => round($totalArrears, 2),
                'total_overdue_arrears' => round($totalOverdueArrears, 2),
                'total_records' => $installments->total(),
            ],
            'filters' => [
                'status' => $status,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'building_id' => $filters['building_id'] ?? null,
            ],
        ];
    }

    /**
     * List all contracts with detailed information
     *
     * @param Request $request
     * @return array
     */
    public function listAllContracts($request)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

        // Pagination parameters
        $perPage = $request->get('per_page', 15);
        $perPage = min($perPage, 100);

        // Build the base query
        $query = RmContract::with([
            'rental.property.contents', // Eager load property contents for language support
            'rental.property.building',
            'rental.building',
            'rental.project.contents',
            'installments'
        ])
        ->whereHas('rental', function($q) use ($ownerId) {
            $q->where('user_id', $ownerId);
        });

        // Apply filters
        if ($request->has('building_id') && $request->building_id) {
            $query->whereHas('rental', function($q) use ($request) {
                $q->where('building_id', $request->building_id);
            });
        }

        if ($request->has('payment_status') && $request->payment_status) {
            // This will be filtered after loading due to complex calculation
            // We'll apply this filter in the collection processing
        }

        if ($request->has('rental_method') && $request->rental_method) {
            $query->whereHas('rental', function($q) use ($request) {
                $q->where('paying_plan', $request->rental_method);
            });
        }

        if ($request->has('from_date') && $request->from_date) {
            $query->where('start_date', '>=', $request->from_date);
        }

        if ($request->has('to_date') && $request->to_date) {
            $query->where('end_date', '<=', $request->to_date);
        }

        if ($request->has('contract_status') && $request->contract_status) {
            $query->where('status', $request->contract_status);
        }

        // Order by creation date (newest first)
        $query->orderBy('created_at', 'desc');

        // Get paginated results
        $contracts = $query->paginate($perPage);

        // Transform the data
        $contractsList = [];
        foreach ($contracts as $contract) {
            $rental = $contract->rental;

            if (!$rental) {
                continue;
            }

            // Calculate payment status for next/latest installment
            $paymentStatusData = $this->calculateContractPaymentStatus($contract, $rental);

            // Get unit information
            $property = $rental->property;
            $propertyContent = $this->getPropertyContent($property, $ownerId);

            $unitInfo = [
                'unit_id' => $rental->unit_id,
                'unit_number' => $property?->property_number ?? 'N/A',
                'unit_name' => $propertyContent['name'],
                'unit_type' => $property?->property_type ?? 'N/A',
                'unit_size' => $property?->bedrooms ? $property->bedrooms . ' BR' : 'N/A',
                'unit_address' => $propertyContent['address'],
            ];

            // Get building information (try property's building first, then rental's building)
            $building = $property?->building ?? $rental->building;
            $buildingName = 'N/A';

            // Manually load the building if not an object (fix for relationship loading issue)
            if ((!$building || !is_object($building)) && ($rental->building_id || $property?->building_id)) {
                $buildingId = $rental->building_id ?? $property?->building_id;
                $building = \App\Models\Building::find($buildingId);
            }

            if ($building && is_object($building)) {
                $buildingName = $building->name ?? 'N/A';
            }

            $buildingInfo = [
                'building_id' => $property?->building_id ?? $rental->building_id,
                'building_name' => $buildingName,
                'building_address' => $property?->city ?? 'N/A', // Using property city as building address
            ];

            // Get tenant information
            $tenantInfo = [
                'tenant_name' => $rental->tenant_full_name,
                'tenant_email' => $rental->tenant_email ?? 'N/A',
                'tenant_phone' => $rental->tenant_phone,
            ];

            // Get lease term
            $leaseTerm = [
                'start_date' => $contract->start_date ? $contract->start_date->format('Y-m-d') : null,
                'end_date' => $contract->end_date ? $contract->end_date->format('Y-m-d') : null,
                'duration_days' => $contract->start_date && $contract->end_date
                    ? $contract->start_date->diffInDays($contract->end_date)
                    : null,
            ];

            // Rental method mapping
            $rentalMethodMap = [
                'monthly' => 'Monthly',
                'quarterly' => 'Quarterly',
                'semi_annual' => 'Semi-Annual',
                'annual' => 'Annual',
            ];

            $contractData = [
                'contract_id' => $contract->id,
                'contract_number' => $rental->contract_number ?? 'N/A',
                'contract_status' => $contract->status,
                'tenant_information' => $tenantInfo,
                'unit_information' => $unitInfo,
                'building' => $buildingInfo,
                'rent_amount' => round($rental->base_rent_amount ?? 0, 2),
                'total_rental_amount' => round($rental->total_rental_amount ?? 0, 2),
                'currency' => $rental->currency ?? 'SAR',
                'lease_term' => $leaseTerm,
                'rental_method' => $rentalMethodMap[$rental->paying_plan] ?? $rental->paying_plan,
                'rental_method_code' => $rental->paying_plan,
                'payment_status' => $paymentStatusData['status'],
                'payment_status_color' => $paymentStatusData['color'],
                'payment_details' => $paymentStatusData['details'],
                'created_at' => $contract->created_at->format('Y-m-d H:i:s'),
            ];

            // Filter by payment status if requested
            if ($request->has('payment_status') && $request->payment_status) {
                if ($paymentStatusData['status'] !== $request->payment_status) {
                    continue;
                }
            }

            $contractsList[] = $contractData;
        }

        return [
            'status' => true,
            'data' => $contractsList,
            'pagination' => [
                'current_page' => $contracts->currentPage(),
                'per_page' => $contracts->perPage(),
                'total' => count($contractsList), // Use filtered count
                'last_page' => $contracts->lastPage(),
                'from' => $contracts->firstItem(),
                'to' => $contracts->lastItem(),
                'has_more_pages' => $contracts->hasMorePages(),
                'next_page_url' => $contracts->nextPageUrl(),
                'prev_page_url' => $contracts->previousPageUrl(),
            ],
        ];
    }

    /**
     * Calculate payment status for a contract
     * Returns status, color, and details
     *
     * Red: Late payment (from day 1 past due date)
     * Yellow: Due soon (within current month)
     * Green: Paid/up-to-date
     */
    private function calculateContractPaymentStatus($contract, $rental)
    {
        // Get the next unpaid or partially paid installment
        $nextInstallment = RmPaymentInstallment::where('contract_id', $contract->id)
            ->where('rental_id', $rental->id)
            ->whereColumn('paid_amount', '<', 'amount')
            ->orderBy('due_date', 'asc')
            ->first();

        if (!$nextInstallment) {
            // All installments are paid
            return [
                'status' => 'paid',
                'color' => 'green',
                'details' => [
                    'message' => 'All payments completed',
                    'next_due_date' => null,
                    'amount_due' => 0,
                    'amount_paid' => 0,
                ]
            ];
        }

        $dueDate = Carbon::parse($nextInstallment->due_date);
        $today = Carbon::today();
        $paidAmount = (float) $nextInstallment->paid_amount;
        $totalAmount = (float) $nextInstallment->amount;
        $remainingAmount = max(0, $totalAmount - $paidAmount);

        // Red: Late payment (from day 1 past due date)
        if ($dueDate->isBefore($today)) {
            return [
                'status' => 'overdue',
                'color' => 'red',
                'details' => [
                    'message' => 'Payment overdue',
                    'next_due_date' => $dueDate->format('Y-m-d'),
                    'amount_due' => round($totalAmount, 2),
                    'amount_paid' => round($paidAmount, 2),
                    'remaining_amount' => round($remainingAmount, 2),
                    'days_overdue' => $today->diffInDays($dueDate),
                ]
            ];
        }

        // Yellow: Due soon (within current month before due date)
        $endOfMonth = Carbon::today()->endOfMonth();
        if ($dueDate->between($today, $endOfMonth)) {
            return [
                'status' => 'pending',
                'color' => 'yellow',
                'details' => [
                    'message' => 'Payment due soon',
                    'next_due_date' => $dueDate->format('Y-m-d'),
                    'amount_due' => round($totalAmount, 2),
                    'amount_paid' => round($paidAmount, 2),
                    'remaining_amount' => round($remainingAmount, 2),
                    'days_until_due' => $today->diffInDays($dueDate),
                ]
            ];
        }

        // Green: Payment not due yet (beyond current month)
        return [
            'status' => 'not_due',
            'color' => 'green',
            'details' => [
                'message' => 'Payment not due yet',
                'next_due_date' => $dueDate->format('Y-m-d'),
                'amount_due' => round($totalAmount, 2),
                'amount_paid' => round($paidAmount, 2),
                'remaining_amount' => round($remainingAmount, 2),
                'days_until_due' => $today->diffInDays($dueDate),
            ]
        ];
    }
}
