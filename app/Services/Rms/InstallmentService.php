<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmPaymentInstallment;
use App\Models\Api\Rms\RmContract;
use App\Models\Api\Rms\RmRental;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

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
        if ($contract->installments()->exists()) {
            throw ValidationException::withMessages([
                'schedule' => ['An installment schedule already exists; use regenerate instead.'],
            ]);
        }

        $rental = $contract->rental;
        $periods = InstallmentSchedule::intervalMonths($rental->paying_plan);
        $totalPayments = InstallmentSchedule::numberOfPayments(
            $rental->rental_duration,
            $rental->rental_type,
            $rental->paying_plan,
            $rental->rental_period
        );

        if ($totalPayments <= 0) {
            throw ValidationException::withMessages([
                'rental_duration' => ['Rental duration must produce at least one installment.'],
            ]);
        }

        $totalAmount = round((float) $rental->total_rental_amount, 2);
        $amount = round($totalAmount / $totalPayments, 2);
        $lastAmount = round($totalAmount - ($amount * ($totalPayments - 1)), 2);
        $rental->update(['base_rent_amount' => $amount]);

        $start = \Carbon\Carbon::parse($contract->start_date);
        $grace = (int) ($contract->grace_period_months ?? 0);

        $installments  = [];
        for ($i = 0; $i < $totalPayments; $i++) {
            $isGrace = $i < $grace;
            $installmentAmount = $i === $totalPayments - 1 ? $lastAmount : $amount;
            $installments [] = [
                'user_id'    => $rental->user_id,
                'rental_id'  => $rental->id,
                'contract_id'=> $contract->id,
                'sequence_no'=> $i + 1,
                'due_date'   => $start->copy()->addMonths($i * $periods),
                'amount'     => $isGrace ? 0 : $installmentAmount,
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
        return DB::transaction(function () use ($rentalId, $userId) {
            $rental = RmRental::where('id', $rentalId)
                ->where('user_id', $userId)
                ->firstOrFail();

            $contract = $rental->contracts()
                ->where('status', 'active')
                ->first();

            if (!$contract) {
                throw ValidationException::withMessages([
                    'contract' => ['No active contract found for this rental.'],
                ]);
            }

            $installments = $contract->installments()->orderBy('due_date')->get();
            $surviving = $installments->filter(fn ($item) =>
                (float) ($item->paid_amount ?? 0) > 0
                || in_array($item->status, ['paid', 'partial'], true)
            );

            $numberOfPayments = InstallmentSchedule::numberOfPayments(
                $rental->rental_duration,
                $rental->rental_type,
                $rental->paying_plan,
                $rental->rental_period
            );
            $remainingPayments = max(0, $numberOfPayments - $surviving->count());

            if ($remainingPayments <= 0) {
                throw ValidationException::withMessages([
                    'schedule' => ['The lease is fully invoiced; renew or end the contract instead.'],
                ]);
            }

            $newBase = round((float) $rental->base_rent_amount, 2);
            if ($newBase <= 0 && $numberOfPayments > 0) {
                $newBase = round((float) $rental->total_rental_amount / $numberOfPayments, 2);
            }

            $invoiced = round((float) $surviving->sum('amount'), 2);
            $newTotal = round($invoiced + ($newBase * $remainingPayments), 2);
            $rental->update([
                'base_rent_amount' => $newBase,
                'total_rental_amount' => $newTotal,
            ]);

            $maxSequence = (int) ($installments->max('sequence_no') ?? 0);
            $contract->installments()
                ->whereIn('status', ['pending', 'overdue'])
                ->where(function ($query) {
                    $query->whereNull('paid_amount')->orWhere('paid_amount', 0);
                })
                ->delete();

            $interval = InstallmentSchedule::intervalMonths($rental->paying_plan);
            $start = $surviving->isNotEmpty()
                ? Carbon::parse($surviving->sortByDesc('due_date')->first()->due_date)->addMonths($interval)
                : Carbon::parse($contract->start_date);

            $allocated = 0.0;
            for ($i = 0; $i < $remainingPayments; $i++) {
                $amount = $i === $remainingPayments - 1
                    ? round($newTotal - $invoiced - $allocated, 2)
                    : $newBase;
                $allocated = round($allocated + $amount, 2);

                RmPaymentInstallment::create([
                    'user_id' => $rental->user_id,
                    'rental_id' => $rental->id,
                    'contract_id' => $contract->id,
                    'sequence_no' => $maxSequence + $i + 1,
                    'due_date' => $start->copy()->addMonths($i * $interval),
                    'amount' => $amount,
                    'status' => 'pending',
                    'payment_type' => 'none',
                    'payment_status' => 'not_due',
                ]);
            }

            return $rental->fresh();
        });
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
