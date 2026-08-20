<?php

namespace App\Services\Payment;

use App\Models\Api\marketing\CreditTransaction;
use App\Models\Api\marketing\UserCredit;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditPaymentCompletionService
{
    public function complete(CreditTransaction $transaction, array $gatewayData): CreditTransaction
    {
        return DB::transaction(function () use ($transaction, $gatewayData) {
            $lockedTransaction = CreditTransaction::whereKey($transaction->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $paymentTransactionId = array_key_exists('payment_transaction_id', $gatewayData)
                ? $gatewayData['payment_transaction_id']
                : $lockedTransaction->payment_transaction_id;
            $paymentTransactionId = $paymentTransactionId !== null
                ? trim((string) $paymentTransactionId)
                : null;
            $paymentTransactionId = $paymentTransactionId !== '' ? $paymentTransactionId : null;

            if (
                $lockedTransaction->isCompleted()
                && $lockedTransaction->payment_transaction_id
                && $paymentTransactionId
                && (string) $lockedTransaction->payment_transaction_id !== $paymentTransactionId
            ) {
                throw new RuntimeException(
                    'This credit transaction was completed by a different gateway payment.'
                );
            }

            if ($lockedTransaction->isCompleted()) {
                return $lockedTransaction;
            }

            $userCredit = UserCredit::where('user_id', $lockedTransaction->user_id)
                ->lockForUpdate()
                ->firstOrCreate(
                    ['user_id' => $lockedTransaction->user_id],
                    [
                        'total_credits' => 0,
                        'used_credits' => 0,
                        'monthly_limit' => 2147483647,
                        'average_cost_per_credit' => 0.05,
                        'reset_date' => now()->addMonth(),
                        'is_active' => true,
                    ]
                );

            if (!$lockedTransaction->isRecoverable()) {
                throw new RuntimeException('This credit transaction cannot be completed.');
            }

            if ($paymentTransactionId) {
                $existingOwner = CreditTransaction::where('payment_transaction_id', $paymentTransactionId)
                    ->where('id', '<>', $lockedTransaction->getKey())
                    ->first();

                if ($existingOwner) {
                    return $this->resolveExistingOwner($existingOwner, $lockedTransaction);
                }
            }

            $metadata = is_array($lockedTransaction->metadata) ? $lockedTransaction->metadata : [];
            $gatewayMetadata = $gatewayData['metadata'] ?? [];
            if (is_array($gatewayMetadata)) {
                $metadata = array_merge($metadata, $gatewayMetadata);
            }

            $lockedTransaction->status = 'completed';
            $lockedTransaction->payment_transaction_id = $paymentTransactionId;
            $lockedTransaction->payment_method = $gatewayData['payment_method']
                ?? $gatewayData['gateway']
                ?? $lockedTransaction->payment_method;
            $lockedTransaction->metadata = $metadata;

            if (array_key_exists('amount_paid', $gatewayData)) {
                $lockedTransaction->amount_paid = $gatewayData['amount_paid'];
            }

            try {
                $lockedTransaction->save();
            } catch (QueryException $exception) {
                if (!$paymentTransactionId || !$this->isUniqueConstraintViolation($exception)) {
                    throw $exception;
                }

                $existingOwner = CreditTransaction::where(
                    'payment_transaction_id',
                    $paymentTransactionId
                )->first();

                if (!$existingOwner) {
                    throw $exception;
                }

                if ($existingOwner->is($lockedTransaction) && $existingOwner->isCompleted()) {
                    return $existingOwner;
                }

                return $this->resolveExistingOwner($existingOwner, $lockedTransaction, $exception);
            }

            $userCredit->addCredits(
                (int) $lockedTransaction->credits_amount,
                $lockedTransaction->credit_package_id,
                $lockedTransaction->description ?? 'Credit purchase',
                $lockedTransaction
            );

            return $lockedTransaction->refresh();
        });
    }

    public function markCancelled(CreditTransaction $transaction, array $meta = []): CreditTransaction
    {
        return $this->mark($transaction, 'cancelled', $meta);
    }

    public function markFailed(CreditTransaction $transaction, array $meta = []): CreditTransaction
    {
        return $this->mark($transaction, 'failed', $meta);
    }

    private function mark(CreditTransaction $transaction, string $status, array $meta): CreditTransaction
    {
        return DB::transaction(function () use ($transaction, $status, $meta) {
            $lockedTransaction = CreditTransaction::whereKey($transaction->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedTransaction->isCompleted()) {
                return $lockedTransaction;
            }

            $metadata = is_array($lockedTransaction->metadata) ? $lockedTransaction->metadata : [];
            $lockedTransaction->status = $status;
            $lockedTransaction->metadata = array_merge($metadata, $meta);
            $lockedTransaction->save();

            return $lockedTransaction->refresh();
        });
    }

    private function resolveExistingOwner(
        CreditTransaction $existingOwner,
        CreditTransaction $attemptedTransaction,
        ?QueryException $exception = null
    ): CreditTransaction {
        if ($existingOwner->is($attemptedTransaction) && $existingOwner->isCompleted()) {
            return $existingOwner;
        }

        throw $exception ?? new RuntimeException(
            'The gateway payment is already assigned to another credit transaction.'
        );
    }

    private function isUniqueConstraintViolation(QueryException $exception): bool
    {
        $errorInfo = $exception->errorInfo;

        return ($errorInfo[0] ?? null) === '23000'
            && (int) ($errorInfo[1] ?? 0) === 1062;
    }
}
