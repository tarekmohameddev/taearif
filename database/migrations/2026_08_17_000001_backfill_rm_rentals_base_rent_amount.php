<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('rm_rentals')
            ->whereNotNull('total_rental_amount')
            ->whereNull('base_rent_amount')
            ->orderBy('id')
            ->chunkById(500, function ($rentals) {
                foreach ($rentals as $rental) {
                    if ($rental->rental_duration !== null && $rental->rental_type !== null) {
                        $totalMonths = $rental->rental_type === 'annual'
                            ? (int) $rental->rental_duration * 12
                            : (int) $rental->rental_duration;
                    } else {
                        $totalMonths = max(0, (int) ($rental->rental_period ?? 0));
                    }

                    $interval = match ($rental->paying_plan) {
                        'quarterly' => 3,
                        'semi_annual' => 6,
                        'annual' => 12,
                        default => 1,
                    };
                    $numberOfPayments = $totalMonths > 0
                        ? (int) ceil($totalMonths / $interval)
                        : 0;

                    if ($numberOfPayments > 0) {
                        DB::table('rm_rentals')
                            ->where('id', $rental->id)
                            ->whereNull('base_rent_amount')
                            ->update([
                                'base_rent_amount' => round(
                                    (float) $rental->total_rental_amount / $numberOfPayments,
                                    2
                                ),
                            ]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Data backfills are intentionally irreversible.
    }
};
