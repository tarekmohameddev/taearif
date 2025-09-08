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
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;
        $query = RmPaymentInstallment::where('user_id', $ownerId);

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
        $ownerId = auth()->user() ? auth()->user()->tenantOwnerId() : $userId;
        $installment = RmPaymentInstallment::where('id', $installmentId)
            ->where('user_id', $ownerId)
            ->firstOrFail();

        // Normalize incoming fields
        $newPaidAmount = array_key_exists('paid_amount', $data)
            ? (float) $data['paid_amount']
            : (float) ($installment->paid_amount ?? 0);

        $explicitStatus = $data['status'] ?? null;

        // Update base fields first
        $installment->reference = $data['reference'] ?? $installment->reference;
        $installment->notes = $data['notes'] ?? $installment->notes;
        $installment->paid_at = $data['paid_at'] ?? $installment->paid_at;
        $installment->paid_amount = $newPaidAmount;

        // Determine status automatically if not explicitly set
        if ($explicitStatus === null) {
            $installment->status = $this->determineStatus($installment->amount, $newPaidAmount, $installment->due_date);
        } else {
            $installment->status = $explicitStatus;
        }

        // Set payment_type and payment_status
        $installment->payment_type = $this->determinePaymentType($installment->amount, $newPaidAmount);
        $installment->payment_status = $this->determinePaymentStatus($installment->amount, $newPaidAmount, $installment->due_date);

        $installment->save();

        // Handle overpayment: carry forward to next installments of the same contract
        $overflow = max(0, $newPaidAmount - (float) $installment->amount);
        if ($overflow > 0) {
            $this->applyOverflowToSubsequentInstallments($installment, $overflow);
        }

        return $installment->fresh();
    }

    public function generateSchedule(RmContract $contract)
    {
        $rental = $contract->rental;
        $months = $rental->rental_period_months;
        $plan   = $rental->paying_plan; // monthly|quarterly|semi_annual|annual

        // Use rental base rent amount
        $baseRent = $rental->base_rent_amount;

        $userId = $rental->user_id; // already owner id on rental

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
                'payment_type' => 'none',
                'payment_status' => 'not_due',
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

    protected function determineStatus($amount, $paidAmount, $dueDate)
    {
        $amount = (float) $amount;
        $paidAmount = (float) ($paidAmount ?? 0);
        $today = now()->toDateString();

        if ($paidAmount >= $amount && $amount > 0) {
            return 'paid';
        }

        if ($paidAmount > 0 && $paidAmount < $amount) {
            // If partially paid and due date passed => still overdue until fully paid
            return (\Carbon\Carbon::parse($dueDate)->lt(\Carbon\Carbon::parse($today))) ? 'overdue' : 'partial';
        }

        // No payment yet
        return (\Carbon\Carbon::parse($dueDate)->lt(\Carbon\Carbon::parse($today))) ? 'overdue' : 'pending';
    }

    protected function determinePaymentType($amount, $paidAmount)
    {
        $amount = (float) $amount;
        $paidAmount = (float) ($paidAmount ?? 0);

        if ($paidAmount >= $amount && $amount > 0) {
            return 'full';
        } elseif ($paidAmount > 0) {
            return 'partial';
        } else {
            return 'none';
        }
    }

    protected function determinePaymentStatus($amount, $paidAmount, $dueDate)
    {
        $amount = (float) $amount;
        $paidAmount = (float) ($paidAmount ?? 0);
        $today = now()->toDateString();

        if ($paidAmount >= $amount && $amount > 0) {
            return 'paid_in_full';
        } elseif ($paidAmount > 0) {
            return 'paid_in_part';
        } else {
            // Check if payment is overdue
            return (\Carbon\Carbon::parse($dueDate)->lt(\Carbon\Carbon::parse($today))) ? 'late' : 'not_due';
        }
    }

    protected function applyOverflowToSubsequentInstallments(RmPaymentInstallment $current, float $overflow)
    {
        $subsequent = RmPaymentInstallment::where('contract_id', $current->contract_id)
            ->where('id', '!=', $current->id)
            ->orderBy('due_date')
            ->orderBy('sequence_no')
            ->get();

        foreach ($subsequent as $next) {
            if ($overflow <= 0) {
                break;
            }

            $nextPaid = (float) ($next->paid_amount ?? 0);
            $nextAmount = (float) $next->amount;
            $remainingForNext = max(0, $nextAmount - $nextPaid);

            if ($remainingForNext <= 0) {
                // already fully covered
                if ($next->status !== 'paid') {
                    $next->status = 'paid';
                    $next->save();
                }
                continue;
            }

            $applied = min($overflow, $remainingForNext);
            $nextPaid += $applied;
            $overflow -= $applied;

            $next->paid_amount = $nextPaid;
            $next->status = $this->determineStatus($nextAmount, $nextPaid, $next->due_date);
            $next->payment_type = $this->determinePaymentType($nextAmount, $nextPaid);
            $next->payment_status = $this->determinePaymentStatus($nextAmount, $nextPaid, $next->due_date);
            if (!$next->paid_at && $next->status === 'paid') {
                $next->paid_at = now();
            }
            $next->save();
        }
    }
}
