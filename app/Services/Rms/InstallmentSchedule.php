<?php

namespace App\Services\Rms;

class InstallmentSchedule
{
    public static function splitTotalAcrossPayments(float|int $total, int $paymentCount): array
    {
        if ($paymentCount < 1) {
            throw new \InvalidArgumentException('Payment count must be at least 1');
        }

        $wholeTotal = self::normalizeWholeAmount($total);

        if ($paymentCount === 1) {
            $base = $wholeTotal;
            $last = $wholeTotal;
        } else {
            $base = (int) round($wholeTotal / $paymentCount, 0, PHP_ROUND_HALF_UP);
            $last = $wholeTotal - ($base * ($paymentCount - 1));
        }

        if ($base === 0 || $last < 0) {
            throw new \InvalidArgumentException('Total amount cannot be split across the payment count');
        }

        return [
            'base' => $base,
            'last' => $last,
        ];
    }

    public static function normalizeWholeAmount(float|int $amount): int
    {
        return (int) round($amount, 0, PHP_ROUND_HALF_UP);
    }

    public static function intervalMonths(?string $payingPlan): int
    {
        return match ($payingPlan) {
            'quarterly' => 3,
            'semi_annual' => 6,
            'annual' => 12,
            default => 1,
        };
    }

    public static function totalMonths(
        mixed $rentalDuration,
        ?string $rentalType,
        mixed $legacyRentalPeriod = null
    ): int {
        if ($rentalDuration !== null && $rentalType !== null) {
            $duration = (int) $rentalDuration;

            return $rentalType === 'annual' ? $duration * 12 : $duration;
        }

        return max(0, (int) ($legacyRentalPeriod ?? 0));
    }

    public static function numberOfPayments(
        mixed $rentalDuration,
        ?string $rentalType,
        ?string $payingPlan,
        mixed $legacyRentalPeriod = null
    ): int {
        $totalMonths = self::totalMonths($rentalDuration, $rentalType, $legacyRentalPeriod);

        if ($totalMonths <= 0) {
            return 0;
        }

        return (int) ceil($totalMonths / self::intervalMonths($payingPlan));
    }
}
