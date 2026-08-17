<?php

namespace App\Services\Rms;

class InstallmentSchedule
{
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
