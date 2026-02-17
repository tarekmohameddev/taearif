<?php

namespace App\Domain\Communication\Services;

use App\Domain\Communication\Contracts\CreditService;
use App\Domain\Communication\Exceptions\InsufficientCreditsException;
use App\Models\Api\markting\CreditTransaction;
use App\Models\Api\markting\UserCredit;
use Illuminate\Support\Facades\DB;

class CreditServiceImpl implements CreditService
{
    private const REFUND_MARKER_PREFIX = 'communication_message:';

    public function hasSufficientCredits(int $userId, int $amount): bool
    {
        $userCredit = UserCredit::getOrCreateForUser($userId);

        return $userCredit->available_credits >= $amount;
    }

    public function deduct(int $userId, int $amount, string $referenceType, string $referenceId): void
    {
        $description = $this->marker($referenceType, $referenceId);

        DB::transaction(function () use ($userId, $amount, $description) {
            UserCredit::getOrCreateForUser($userId);
            $userCredit = UserCredit::where('user_id', $userId)->lockForUpdate()->first();
            if (!$userCredit) {
                throw new InsufficientCreditsException($userId, $amount);
            }

            if ($userCredit->available_credits < $amount) {
                throw new InsufficientCreditsException($userId, $amount);
            }

            $ok = $userCredit->useCredits($amount, $description);
            if (!$ok) {
                throw new InsufficientCreditsException($userId, $amount);
            }
        });
    }

    public function refund(int $userId, int $amount, string $referenceType, string $referenceId): void
    {
        $marker = $this->marker($referenceType, $referenceId);

        DB::transaction(function () use ($userId, $amount, $referenceType, $referenceId, $marker) {
            $existingRefund = CreditTransaction::where('user_id', $userId)
                ->where('transaction_type', 'refund')
                ->where('description', $marker)
                ->exists();

            if ($existingRefund) {
                return;
            }

            $userCredit = UserCredit::where('user_id', $userId)->lockForUpdate()->first();
            if (!$userCredit) {
                return;
            }

            $refundAmount = min($amount, $userCredit->used_credits);
            if ($refundAmount <= 0) {
                return;
            }

            $userCredit->decrement('used_credits', $refundAmount);

            CreditTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'refund',
                'credits_amount' => $refundAmount,
                'status' => 'completed',
                'description' => $marker,
                'metadata' => [
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ],
            ]);
        });
    }

    private function marker(string $referenceType, string $referenceId): string
    {
        return self::REFUND_MARKER_PREFIX . $referenceType . ':' . $referenceId;
    }
}
