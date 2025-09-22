<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmPayment;
use App\Models\User\RealestateManagement\Property;
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
            ->when($request->property_number, fn($q) => $q->where('property_number', $request->property_number))
            ->when($request->property_id, fn($q) => $q->where('property_id', $request->property_id))
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
            $rental = RmRental::create(array_merge($data, [
                'user_id' => $userId,
                'status' => 'active',
            ]));

            // Update property status based on active rentals
            if ($rental->property_id) {
                $property = Property::where('id', $rental->property_id)
                    ->where('user_id', $userId)
                    ->first();
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            $hasEnoughData = $data['move_in_date'] ?? null
                && $data['rental_period'] ?? null
                && $data['paying_plan'] ?? null
                && $data['base_rent_amount'] ?? null;

            if ($hasEnoughData) {
                // Calculate total months based on rental_period and paying_plan
                $totalMonths = $this->calculateTotalMonths($data['rental_period'], $data['paying_plan']);
                
                $contract = RmContract::create([
                    'user_id' => $userId,
                    'rental_id' => $rental->id,
                    'start_date' => $data['move_in_date'],
                    'end_date' => Carbon::parse($data['move_in_date'])->addMonths($totalMonths)->subDay(),
                    'status' => 'active',
                    // Snapshot identifiers for audit/history
                    'property_id' => $rental->property_id,
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
                    ]
                ];
            }

            return [
                'id' => $rental->id,
                'status' => 'active'
            ];
        });
    }

    public function getRentalDetails($userId, $id)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['activeContract', 'upcomingInstallments', 'maintenanceOpen'])
            ->where('user_id', $ownerId)
            ->findOrFail($id);

        return $rental;
    }

    public function updateRental($userId, $id, array $data, bool $regenerate = false)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        return DB::transaction(function () use ($ownerId, $id, $data, $regenerate) {
            $rental = RmRental::where('user_id', $ownerId)->findOrFail($id);
            
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
            if ($rental->property_id) {
                $property = Property::where('id', $rental->property_id)
                    ->where('user_id', $ownerId)
                    ->first();
                if ($property) {
                    $property->updatePropertyStatus();
                }
            }

            return $rental->fresh();
        });
    }

    public function deleteRental($userId, $id)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::where('user_id', $ownerId)->findOrFail($id);

        if ($rental->activeContract) {
            throw new \Exception('Cannot delete rental with active contract');
        }

        $propertyId = $rental->property_id;
        $rental->delete();

        // Update property status after rental deletion
        if ($propertyId) {
            $property = Property::where('id', $propertyId)
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

        $rental = RmRental::with(['property.contents', 'project', 'contracts', 'installments'])
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
                'id' => $rental->property_id,
                'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
                'unit_label' => $rental->unit_label,
                'property_number' => $rental->property_number,
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
        ];
    }

    /**
     * Get rental details with comprehensive payment information
     */
    public function getRentalDetailsWithPayments($userId, $rentalId)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;

        $rental = RmRental::with(['property.contents', 'project', 'contracts', 'installments'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        $activeContract = $rental->contracts()->whereIn('status', ['active', 'pending'])->orderByDesc('status')->orderBy('start_date')->first();

        // Get payment summary
        $paymentSummary = $this->paymentService->getPaymentSummary($ownerId, $rentalId);
        
        // Get detailed installment payment information
        $installmentDetails = $this->paymentService->getInstallmentPaymentDetails($ownerId, $rentalId);

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
                'id' => $rental->property_id,
                'name' => optional($rental->property)->firstContent ? $rental->property->firstContent->title : null,
                'unit_label' => $rental->unit_label,
                'property_number' => $rental->property_number,
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
            'recent_payments' => $paymentSummary['recent_payments']->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'payment_type' => $payment->payment_type,
                    'payment_type_label' => $payment->payment_type_label,
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
        $amount = $data['base_rent_amount'];
        $start = Carbon::parse($data['move_in_date']);
        $rentalPeriod = (int) $data['rental_period'];

        $chunks = match($plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        $periods = $rentalPeriod; // rental_period is now the number of payment periods
        $installmentAmount = round($amount * $chunks, 2);

        for ($i = 0; $i < $periods; $i++) {
            RmPaymentInstallment::create([
                'user_id' => $userId,
                'rental_id' => $rentalId,
                'contract_id' => $contractId,
                'sequence_no' => $i + 1,
                'due_date' => $start->copy()->addMonths($i * $chunks),
                'amount' => $installmentAmount,
                'status' => 'pending',
                'payment_type' => 'none',
                'payment_status' => 'not_due',
            ]);
        }
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
            'office_commission_value' => $officeCommission,
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
        
        $rental = RmRental::with(['activeContract', 'installments.payments', 'property.project'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        if (!$rental->activeContract) {
            return [
                'rental_info' => [
                    'id' => $rental->id,
                    'tenant_name' => $rental->tenant_full_name,
                    'tenant_phone' => $rental->tenant_phone,
                    'tenant_email' => $rental->tenant_email,
                    'property_address' => $rental->property?->name ?? 'N/A',
                    'unit_label' => $rental->unit_label,
                    'property_number' => $rental->property_number,
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

        // Calculate fees for the rental
        $fees = $this->calculateRentalFees($rental);
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

        // Calculate summary with proper rounding
        $totalRentDue = round($installments->sum('amount'), 2);
        $totalFeesDue = round($fees['total_fees'], 2);
        $totalDue = round($totalRentDue + $totalFeesDue, 2);
        $totalPaid = round($installments->sum('paid_amount'), 2);
        $totalRemaining = round($totalDue - $totalPaid, 2);

        return [
            'rental_info' => [
                'id' => $rental->id,
                'tenant_name' => $rental->tenant_full_name,
                'tenant_phone' => $rental->tenant_phone,
                'tenant_email' => $rental->tenant_email,
                'property_address' => $rental->property?->name ?? 'N/A',
                'unit_label' => $rental->unit_label,
                'property_number' => $rental->property_number,
                'contract_number' => $rental->activeContract->contract_number
            ],
            'contract' => [
                'id' => $rental->activeContract->id,
                'contract_number' => $rental->activeContract->contract_number,
                'start_date' => $rental->activeContract->start_date
            ],
            'property' => [
                'id' => $rental->property?->id,
                'name' => $rental->property?->name,
                'unit_label' => $rental->unit_label,
                'property_number' => $rental->property_number,
                'project' => [
                    'id' => $rental->property?->project?->id,
                    'name' => $rental->property?->project?->name
                ]
            ],
            'fees_breakdown' => [
                'platform_fee' => round($fees['platform_fee'], 2),
                'water_fee' => round($fees['water_fee'], 2),
                'office_fee' => round($fees['office_fee'], 2),
                'office_commission_value' => round($fees['office_commission_value'], 2),
                'total_fees' => round($fees['total_fees'], 2)
            ],
            'available_fees' => [
                [
                    'fee_type' => 'platform_fee',
                    'fee_name' => 'Platform Fee',
                    'total_amount' => round($fees['platform_fee'], 2),
                    'paid_amount' => $this->getPaidAmountForFee($rental->id, 'platform_fee'),
                    'remaining_amount' => round($fees['platform_fee'] - $this->getPaidAmountForFee($rental->id, 'platform_fee'), 2),
                    'status' => $this->getFeePaymentStatus($fees['platform_fee'], $this->getPaidAmountForFee($rental->id, 'platform_fee'))
                ],
                [
                    'fee_type' => 'water_fee',
                    'fee_name' => 'Water Fee',
                    'total_amount' => round($fees['water_fee'], 2),
                    'paid_amount' => $this->getPaidAmountForFee($rental->id, 'water_fee'),
                    'remaining_amount' => round($fees['water_fee'] - $this->getPaidAmountForFee($rental->id, 'water_fee'), 2),
                    'status' => $this->getFeePaymentStatus($fees['water_fee'], $this->getPaidAmountForFee($rental->id, 'water_fee'))
                ],
                [
                    'fee_type' => 'office_fee',
                    'fee_name' => 'Office Fee',
                    'total_amount' => round($fees['office_fee'], 2),
                    'paid_amount' => $this->getPaidAmountForFee($rental->id, 'office_fee'),
                    'remaining_amount' => round($fees['office_fee'] - $this->getPaidAmountForFee($rental->id, 'office_fee'), 2),
                    'status' => $this->getFeePaymentStatus($fees['office_fee'], $this->getPaidAmountForFee($rental->id, 'office_fee'))
                ]
            ],
            'payment_details' => [
                'items' => $items,
                'summary' => [
                    'total_rent_due' => $totalRentDue,
                    'total_fees_due' => $totalFeesDue,
                    'total_due' => $totalDue,
                    'total_paid' => $totalPaid,
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
}
