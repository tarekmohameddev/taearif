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
        UserCredit::getOrCreateForUser($userId);
        $userCredit = UserCredit::where('user_id', $userId)->first();
        if (!$userCredit) {
            return false;
        }
        // available = total - used - reserved (see UserCredit::getAvailableCreditsAttribute)
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

    public function reserve(int $userId, int $amount, string $referenceType, string $referenceId): void
    {
        $description = $this->marker($referenceType, $referenceId);

        DB::transaction(function () use ($userId, $amount, $referenceType, $referenceId, $description) {
            UserCredit::getOrCreateForUser($userId);
            $userCredit = UserCredit::where('user_id', $userId)->lockForUpdate()->first();
            if (!$userCredit) {
                throw new InsufficientCreditsException($userId, $amount);
            }

            $available = $userCredit->total_credits - $userCredit->used_credits - (int) ($userCredit->reserved_credits ?? 0);
            if ($available < $amount) {
                throw new InsufficientCreditsException($userId, $amount);
            }

            $userCredit->increment('reserved_credits', $amount);

            CreditTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'reserve',
                'credits_amount' => $amount,
                'status' => 'completed',
                'description' => $description,
                'metadata' => [
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                ],
            ]);
        });
    }

    public function consumeReserved(int $userId, int $amount, string $referenceType, string $referenceId): void
    {
        $description = $this->marker($referenceType, $referenceId);

        DB::transaction(function () use ($userId, $amount, $description) {
            $userCredit = UserCredit::where('user_id', $userId)->lockForUpdate()->first();
            if (!$userCredit) {
                return;
            }

            $reserved = (int) ($userCredit->reserved_credits ?? 0);
            $consumeAmount = min($amount, $reserved);
            if ($consumeAmount <= 0) {
                return;
            }

            $userCredit->decrement('reserved_credits', $consumeAmount);
            $userCredit->increment('used_credits', $consumeAmount);

            CreditTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'usage',
                'credits_amount' => -$consumeAmount,
                'status' => 'completed',
                'description' => $description,
                'metadata' => [
                    'usage_type' => 'marketing_channel',
                    'timestamp' => now()->toISOString(),
                ],
            ]);
        });
    }

    public function releaseReserved(int $userId, int $amount, string $referenceType, string $referenceId): void
    {
        $marker = $this->marker($referenceType, $referenceId);

        DB::transaction(function () use ($userId, $amount, $referenceType, $referenceId, $marker) {
            $existingRelease = CreditTransaction::where('user_id', $userId)
                ->where('transaction_type', 'release')
                ->where('description', $marker)
                ->exists();

            if ($existingRelease) {
                return;
            }

            $userCredit = UserCredit::where('user_id', $userId)->lockForUpdate()->first();
            if (!$userCredit) {
                return;
            }

            $reserved = (int) ($userCredit->reserved_credits ?? 0);
            $releaseAmount = min($amount, $reserved);
            if ($releaseAmount <= 0) {
                return;
            }

            $userCredit->decrement('reserved_credits', $releaseAmount);

            CreditTransaction::create([
                'user_id' => $userId,
                'transaction_type' => 'release',
                'credits_amount' => $releaseAmount,
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
