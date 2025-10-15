<?php

namespace App\Services\Rms;

use App\Models\Api\Rms\RmPayment;
use App\Models\Api\Rms\RmRental;
use App\Models\Api\Rms\RmPaymentInstallment;
use App\Exceptions\PaymentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PaymentService
{
    /**
     * Record a payment for a rental
     */
    public function recordPayment($userId, $rentalId, array $paymentData)
    {
        return DB::transaction(function () use ($userId, $rentalId, $paymentData) {
            $rental = RmRental::where('user_id', $userId)->findOrFail($rentalId);

            // Validate rental is active
            $this->validateRentalCanReceivePayment($rental);

            // Validate payment data
            $validatedData = $this->validatePaymentData($paymentData);

            // Check for duplicate payment reference (if provided)
            if (!empty($validatedData['reference'])) {
                $this->validateNoDuplicatePayment($rentalId, $validatedData['reference']);
            }

            // Check for overpayment before creating payment
            if ($validatedData['payment_type'] === 'rent' && $validatedData['installment_id']) {
                $this->validateNoOverpayment($validatedData['installment_id'], $validatedData['amount']);
                $this->validateInstallmentNotCancelled($validatedData['installment_id']);
            }

            // Create payment record
            $payment = RmPayment::create(array_merge($validatedData, [
                'user_id' => $userId,
                'rental_id' => $rentalId,
                'contract_id' => $rental->activeContract?->id,
                'payment_date' => $validatedData['payment_date'] ?? now()->toDateString(),
                'created_by' => $userId,
                'updated_by' => $userId,
            ]));

            // Update installment if payment is for rent
            if ($payment->payment_type === 'rent' && $payment->installment_id) {
                $this->updateInstallmentPayment($payment);
            }

            // Handle automatic excess payment distribution for rent payments
            // Only distribute if no specific installment_id is provided (general payment)
            if ($payment->payment_type === 'rent' && !$payment->installment_id) {
                $this->distributeExcessPayment($rentalId, $payment->amount);
            }

            return $payment;
        });
    }

    /**
     * Record multiple payments at once
     */
    public function recordMultiplePayments($userId, $rentalId, array $paymentsData)
    {
        return DB::transaction(function () use ($userId, $rentalId, $paymentsData) {
            $rental = RmRental::where('user_id', $userId)->findOrFail($rentalId);

            // Validate rental is active
            $this->validateRentalCanReceivePayment($rental);

            $payments = [];

            foreach ($paymentsData as $index => $paymentData) {
                $validatedData = $this->validatePaymentData($paymentData);

                // Check for duplicate payment reference (if provided)
                if (!empty($validatedData['reference'])) {
                    $this->validateNoDuplicatePayment($rentalId, $validatedData['reference']);
                }

                // Check for overpayment before creating payment
                if ($validatedData['payment_type'] === 'rent' && $validatedData['installment_id']) {
                    $this->validateNoOverpayment($validatedData['installment_id'], $validatedData['amount']);
                    $this->validateInstallmentNotCancelled($validatedData['installment_id']);
                }

                $payment = RmPayment::create(array_merge($validatedData, [
                    'user_id' => $userId,
                    'rental_id' => $rentalId,
                    'contract_id' => $rental->activeContract?->id,
                    'payment_date' => $validatedData['payment_date'] ?? now()->toDateString(),
                    'created_by' => $userId,
                    'updated_by' => $userId,
                ]));

                // Update installment if payment is for rent
                if ($payment->payment_type === 'rent' && $payment->installment_id) {
                    $this->updateInstallmentPayment($payment);
                }

                $payments[] = $payment;
            }

            return $payments;
        });
    }

    /**
     * Get payment summary for a rental
     */
    public function getPaymentSummary($userId, $rentalId)
    {
        $rental = RmRental::where('user_id', $userId)->findOrFail($rentalId);

        // Get all payments for this rental
        $payments = RmPayment::where('rental_id', $rentalId)->get();

        // Calculate totals by payment type
        $paymentSummary = [
            'rent' => [
                'total' => $rental->total_rental_amount ?? 0,
                'paid' => $payments->where('payment_type', 'rent')->sum('amount'),
                'remaining' => 0
            ],
            'platform_fee' => [
                'total' => $rental->platform_fee ?? 0,
                'paid' => $payments->where('payment_type', 'platform_fee')->sum('amount'),
                'remaining' => 0
            ],
            'water_fee' => [
                'total' => $rental->water_fee ?? 0,
                'paid' => $payments->where('payment_type', 'water_fee')->sum('amount'),
                'remaining' => 0
            ],
            'office_fee' => [
                'total' => $rental->office_fee ?? 0,
                'paid' => $payments->where('payment_type', 'office_fee')->sum('amount'),
                'remaining' => 0
            ],
            'deposit' => [
                'total' => $rental->deposit_amount ?? 0,
                'paid' => $payments->where('payment_type', 'deposit')->sum('amount'),
                'remaining' => 0
            ]
        ];

        // Calculate remaining amounts
        foreach ($paymentSummary as $type => &$summary) {
            $summary['remaining'] = max(0, $summary['total'] - $summary['paid']);
        }

        // Calculate overall totals
        $totalAmount = array_sum(array_column($paymentSummary, 'total'));
        $totalPaid = array_sum(array_column($paymentSummary, 'paid'));
        $totalRemaining = $totalAmount - $totalPaid;

        return [
            'rental_id' => $rentalId,
            'total_amount' => $totalAmount,
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'payment_status' => $this->getPaymentStatus($totalPaid, $totalAmount),
            'breakdown' => $paymentSummary,
            'recent_payments' => $payments->sortByDesc('payment_date')->take(5)->values()
        ];
    }

    /**
     * Get detailed payment breakdown for installments
     */
    public function getInstallmentPaymentDetails($userId, $rentalId)
    {
        $rental = RmRental::where('user_id', $userId)->findOrFail($rentalId);

        $installments = RmPaymentInstallment::where('rental_id', $rentalId)
            ->with(['payments' => function($query) {
                $query->where('payment_type', 'rent');
            }])
            ->orderBy('due_date')
            ->get();

        $installmentDetails = $installments->map(function ($installment) use ($rental) {
            $rentPayments = $installment->payments;
            $paidAmount = $rentPayments->sum('amount');
            $remainingAmount = max(0, $installment->amount - $paidAmount);

            // Calculate fees for this installment
            $fees = $this->calculateInstallmentFees($rental, $installment);

            return [
                'id' => $installment->id,
                'sequence_no' => $installment->sequence_no,
                'due_date' => $installment->due_date,
                'rent_amount' => $installment->amount,
                'rent_paid' => $paidAmount,
                'rent_remaining' => $remainingAmount,
                'fees' => $fees,
                'total_amount' => $installment->amount + array_sum($fees),
                'total_paid' => $paidAmount + array_sum(array_column($fees, 'paid')),
                'total_remaining' => $remainingAmount + array_sum(array_column($fees, 'remaining')),
                'status' => $this->getInstallmentStatus($paidAmount, $installment->amount),
                'payments' => $rentPayments->map(function ($payment) {
                    return [
                        'id' => $payment->id,
                        'amount' => $payment->amount,
                        'payment_date' => $payment->payment_date,
                        'payment_method' => $payment->payment_method,
                        'reference' => $payment->reference
                    ];
                })
            ];
        });

        return $installmentDetails;
    }

    /**
     * Calculate fees for a specific installment
     */
    private function calculateInstallmentFees($rental, $installment)
    {
        $totalInstallments = $rental->rental_period ?? 1;

        return [
            'platform_fee' => [
                'total' => ($rental->platform_fee ?? 0) / $totalInstallments,
                'paid' => $this->getPaidAmountForInstallment($rental->id, 'platform_fee', $installment->id),
                'remaining' => 0
            ],
            'water_fee' => [
                'total' => ($rental->water_fee ?? 0) / $totalInstallments,
                'paid' => $this->getPaidAmountForInstallment($rental->id, 'water_fee', $installment->id),
                'remaining' => 0
            ],
            'office_fee' => [
                'total' => ($rental->office_fee ?? 0) / $totalInstallments,
                'paid' => $this->getPaidAmountForInstallment($rental->id, 'office_fee', $installment->id),
                'remaining' => 0
            ]
        ];
    }

    /**
     * Get paid amount for a specific installment and payment type
     */
    private function getPaidAmountForInstallment($rentalId, $paymentType, $installmentId = null)
    {
        $query = RmPayment::where('rental_id', $rentalId)
            ->where('payment_type', $paymentType);

        if ($installmentId) {
            $query->where('installment_id', $installmentId);
        }

        return $query->sum('amount');
    }

    /**
     * Update installment payment status
     */
    private function updateInstallmentPayment($payment)
    {
        $installment = RmPaymentInstallment::find($payment->installment_id);
        if (!$installment) return;

        $totalPaid = RmPayment::where('installment_id', $installment->id)
            ->where('payment_type', 'rent')
            ->sum('amount');

        // Check for overpayment
        if ($totalPaid > $installment->amount) {
            $overpayment = $totalPaid - $installment->amount;
            \Log::warning('Overpayment detected', [
                'installment_id' => $installment->id,
                'rental_id' => $installment->rental_id,
                'amount_due' => $installment->amount,
                'total_paid' => $totalPaid,
                'overpayment' => $overpayment,
                'payment_id' => $payment->id
            ]);
        }

        $installment->update([
            'paid_amount' => $totalPaid,
            'status' => $this->getInstallmentStatus($totalPaid, $installment->amount),
            'paid_at' => $totalPaid >= $installment->amount ? now() : null
        ]);
    }

    /**
     * Get payment status based on amounts
     */
    private function getPaymentStatus($paid, $total)
    {
        if ($paid <= 0) return 'unpaid';
        if ($paid >= $total) return 'paid';
        return 'partial';
    }

    /**
     * Get installment status based on amounts
     */
    private function getInstallmentStatus($paid, $total)
    {
        if ($paid <= 0) return 'pending';
        if ($paid >= $total) return 'paid';
        return 'partial';
    }

    /**
     * Validate payment data
     */
    private function validatePaymentData($data)
    {
        $validated = [
            'payment_type' => $data['payment_type'],
            'amount' => (float) $data['amount'],
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'bank_name' => $data['bank_name'] ?? null,
            'receipt_image_path' => $data['receipt_image_path'] ?? null,
            'transfer_to' => $data['transfer_to'] ?? null,
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
            'installment_id' => $data['installment_id'] ?? null,
        ];

        // Validate payment type
        $validTypes = ['rent', 'platform_fee', 'water_fee', 'office_fee', 'deposit'];
        if (!in_array($validated['payment_type'], $validTypes)) {
            throw new \InvalidArgumentException('Invalid payment type');
        }

        // Validate amount (allow negative for refunds)
        if ($validated['amount'] == 0) {
            throw new \InvalidArgumentException('Payment amount cannot be zero');
        }

        return $validated;
    }

    /**
     * Distribute excess payment across installments
     * Priority: Late installments first, then future installments
     */
    public function distributeExcessPayment($rentalId, $excessAmount)
    {
        if ($excessAmount <= 0) {
            return;
        }

        // 1. First, pay any late installments
        $lateInstallments = RmPaymentInstallment::where('rental_id', $rentalId)
            ->where('due_date', '<', now())
            ->whereRaw('paid_amount < amount')
            ->orderBy('due_date')
            ->get();

        foreach ($lateInstallments as $installment) {
            if ($excessAmount <= 0) break;

            $remaining = $installment->amount - $installment->paid_amount;
            if ($remaining > 0) {
                $payAmount = min($excessAmount, $remaining);
                $this->applyPaymentToInstallment($installment, $payAmount);
                $excessAmount -= $payAmount;
            }
        }

        // 2. Then, apply to future installments
        if ($excessAmount > 0) {
            $futureInstallments = RmPaymentInstallment::where('rental_id', $rentalId)
                ->where('due_date', '>=', now())
                ->whereRaw('paid_amount < amount')
                ->orderBy('due_date')
                ->get();

            foreach ($futureInstallments as $installment) {
                if ($excessAmount <= 0) break;

                $remaining = $installment->amount - $installment->paid_amount;
                if ($remaining > 0) {
                    $payAmount = min($excessAmount, $remaining);
                    $this->applyPaymentToInstallment($installment, $payAmount);
                    $excessAmount -= $payAmount;
                }
            }
        }
    }

    /**
     * Apply payment to an installment
     */
    private function applyPaymentToInstallment($installment, $amount)
    {
        $newPaidAmount = $installment->paid_amount + $amount;
        $totalAmount = $installment->amount;

        // Determine payment status
        $paymentStatus = 'not_due';
        if ($installment->due_date < now()) {
            $paymentStatus = $newPaidAmount >= $totalAmount ? 'paid_in_full' : 'paid_in_part';
        } else {
            $paymentStatus = $newPaidAmount >= $totalAmount ? 'paid_in_full' : 'paid_in_part';
        }

        // Update installment
        $installment->update([
            'paid_amount' => $newPaidAmount,
            'payment_status' => $paymentStatus,
            'status' => $newPaidAmount >= $totalAmount ? 'paid' : 'pending'
        ]);
    }

    /**
     * Validate that payment won't cause overpayment
     *
     * @throws \Exception if payment would cause overpayment
     */
    /**
     * Validate that payment won't cause overpayment
     */
    private function validateNoOverpayment($installmentId, $paymentAmount)
    {
        $installment = RmPaymentInstallment::find($installmentId);
        if (!$installment) {
            throw PaymentException::installmentNotFound($installmentId);
        }

        // Calculate current total paid
        $currentPaid = RmPayment::where('installment_id', $installmentId)
            ->where('payment_type', 'rent')
            ->sum('amount');

        // Check if already fully paid
        if ($currentPaid >= $installment->amount) {
            throw PaymentException::installmentFullyPaid($installmentId, $installment->amount);
        }

        // Calculate what total would be after this payment
        $newTotal = $currentPaid + $paymentAmount;

        // Check if this would cause overpayment
        if ($newTotal > $installment->amount) {
            throw PaymentException::overpayment(
                $installmentId,
                $installment->amount,
                $currentPaid,
                $paymentAmount
            );
        }
    }

    /**
     * Validate rental can receive payment
     */
    private function validateRentalCanReceivePayment($rental)
    {
        if ($rental->status !== 'active') {
            throw PaymentException::rentalNotActive($rental->id, $rental->status);
        }

        if (!$rental->activeContract) {
            throw PaymentException::noActiveContract($rental->id);
        }
    }

    /**
     * Validate installment is not cancelled
     */
    private function validateInstallmentNotCancelled($installmentId)
    {
        $installment = RmPaymentInstallment::find($installmentId);
        if (!$installment) {
            throw PaymentException::installmentNotFound($installmentId);
        }

        if ($installment->status === 'cancelled') {
            throw PaymentException::installmentCancelled($installmentId);
        }
    }

    /**
     * Validate no duplicate payment with same reference
     */
    private function validateNoDuplicatePayment($rentalId, $reference)
    {
        $existingPayment = RmPayment::where('rental_id', $rentalId)
            ->where('reference', $reference)
            ->first();

        if ($existingPayment) {
            throw PaymentException::duplicatePayment($reference, $existingPayment->id);
        }
    }
}
