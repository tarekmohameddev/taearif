<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RentalService
{
    public function listRentals($request)
    {
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : auth()->id();

        return RmRental::with(['activeContract', 'property', 'project'])
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
            ->paginate($request->get('per_page', 15));
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
                'name' => optional($rental->property->firstContent)->title,
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

        $rental = RmRental::with(['activeContract', 'installments'])
            ->where('user_id', $ownerId)
            ->findOrFail($rentalId);

        if (!$rental->activeContract) {
            return [
                'summary' => [
                    'total_rent_amount' => 0,
                    'total_fees' => 0,
                    'total_collection_amount' => 0,
                    'installments_count' => 0,
                    'period' => 'Current + Previous Months'
                ],
                'installments' => []
            ];
        }

        $currentDate = now();
        $currentMonth = $currentDate->format('Y-m');
        
        // Get installments for current month and previous months only (not future)
        $installments = $rental->installments()
            ->where('contract_id', $rental->activeContract->id)
            ->whereIn('status', ['pending'])
            ->whereIn('payment_status', ['not_due', 'overdue'])
            ->whereRaw("DATE_FORMAT(due_date, '%Y-%m') <= ?", [$currentMonth])
            ->orderBy('due_date')
            ->get();

        // Calculate fees for the rental
        $fees = $this->calculateRentalFees($rental);
        
        // Calculate total rent amount from installments
        $totalRentAmount = $installments->sum('amount');
        $totalFees = $fees['total_fees'];
        $totalCollectionAmount = $totalRentAmount + $totalFees;

        // Format installments with period info
        $formattedInstallments = $installments->map(function ($installment) use ($fees) {
            $dueDate = \Carbon\Carbon::parse($installment->due_date);
            $period = $dueDate->format('F Y'); // e.g., "January 2024"
            
            return [
                'id' => $installment->id,
                'sequence_no' => $installment->sequence_no,
                'due_date' => $installment->due_date,
                'period' => $period,
                'rent_amount' => (float) $installment->amount,
                'fees' => $fees,
                'total_amount' => (float) $installment->amount + $fees['total_fees'],
                'status' => $installment->status,
                'payment_status' => $installment->payment_status,
            ];
        });

        return [
            'summary' => [
                'total_rent_amount' => (float) $totalRentAmount,
                'total_fees' => (float) $totalFees,
                'total_collection_amount' => (float) $totalCollectionAmount,
                'installments_count' => $installments->count(),
                'period' => 'Current + Previous Months'
            ],
            'installments' => $formattedInstallments
        ];
    }

    private function calculateRentalFees($rental)
    {
        $platformFee = (float) ($rental->platform_fee ?? 0);
        $waterFee = (float) ($rental->water_fee ?? 0);
        $officeFee = (float) ($rental->office_fee ?? 0);
        
        // Calculate office commission based on type
        $officeCommission = 0;
        if ($rental->office_commission_type === 'percentage' && $rental->office_commission_value) {
            $officeCommission = ($rental->base_rent_amount * $rental->office_commission_value) / 100;
        } elseif ($rental->office_commission_type === 'amount' && $rental->office_commission_value) {
            $officeCommission = (float) $rental->office_commission_value;
        }

        $totalFees = $platformFee + $waterFee + $officeFee + $officeCommission;

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
}
