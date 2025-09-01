<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmRental;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstallmentService
{
    public function listInstallments($userId, $filters = [])
    {
        $query = RmPaymentInstallment::where('user_id', $userId);

        if (!empty($filters['rental_id'])) {
            $query->where('rental_id', $filters['rental_id']);
        }

        if (!empty($filters['contract_id'])) {
            $query->where('contract_id', $filters['contract_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['from'])) {
            $query->whereDate('due_date', '>=', $filters['from']);
        }

        if (!empty($filters['to'])) {
            $query->whereDate('due_date', '<=', $filters['to']);
        }

        return $query->orderBy('due_date')->get();
    }

    public function updateInstallment($installmentId, $data, $userId)
    {
        $installment = RmPaymentInstallment::where('id', $installmentId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $installment->update([
            'status' => $data['status'],
            'paid_amount' => $data['paid_amount'] ?? $installment->paid_amount,
            'paid_at' => $data['paid_at'] ?? $installment->paid_at,
            'reference' => $data['reference'] ?? $installment->reference,
            'notes' => $data['notes'] ?? $installment->notes,
        ]);

        return $installment;
    }

    public function generateSchedule(RmContract $contract)
    {
        $rental = $contract->rental;
        $months = $rental->rental_period_months;
        $plan   = $rental->paying_plan; // monthly|quarterly|semi_annual|annual

        // prefer platform_contract_value if provided (>0), else fall back to rental base
        $baseRent = $contract->platform_contract_value > 0
            ? $contract->platform_contract_value
            : $rental->base_rent_amount;

        $userId = $rental->user_id;

        $periods = match ($plan) {
            'monthly' => 1,
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1
        };

        $totalPayments = (int) ceil($months / $periods);
        $amount = round($baseRent * $periods, 2);

        $start = \Carbon\Carbon::parse($contract->start_date);
        $grace = (int) ($contract->grace_period_months ?? 0);

        $installments  = [];
        for ($i = 0; $i < $totalPayments; $i++) {
            $isGrace = $i < $grace;
            $installments [] = [
                'user_id'    => $userId,
                'rental_id'  => $rental->id,
                'contract_id'=> $contract->id,
                'sequence_no'=> $i + 1,
                'due_date'   => $start->copy()->addMonths($i * $periods),
                'amount'     => $isGrace ? 0 : $amount,
                'status'     => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        RmPaymentInstallment::insert($installments);
    }

    public function regenerateSchedule($rentalId, $userId)
    {
        $rental = RmRental::where('id', $rentalId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $contract = $rental->contracts()
            ->where('status', 'active')
            ->first();

        if (!$contract) {
            throw new \Exception("No active contract found for this rental.");
        }

        // Delete all installments for this contract
        RmPaymentInstallment::where('contract_id', $contract->id)->delete();

        $this->generateSchedule($contract);
    }

}
