<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PACKAGE_ID = 26;

    /**
     * Shorten currently-active trial memberships whose recorded span is longer
     * than packages.trial_days, but only when start_date + trial_days is still
     * in the future.
     *
     * Known exception: membership id 1927 (user 1411) started 2025-10-21 with a
     * 365-day span. Recalculating it to start + 7 would expire that tenant in
     * the past, so the future-date predicate leaves it untouched.
     */
    public function up(): void
    {
        if (! Schema::hasTable('memberships') || ! Schema::hasTable('packages')) {
            return;
        }

        if (
            ! Schema::hasColumn('memberships', 'package_id')
            || ! Schema::hasColumn('memberships', 'start_date')
            || ! Schema::hasColumn('memberships', 'expire_date')
            || ! Schema::hasColumn('packages', 'trial_days')
        ) {
            return;
        }

        DB::update(
            'UPDATE memberships
             INNER JOIN packages ON packages.id = memberships.package_id
             SET memberships.expire_date = DATE_ADD(memberships.start_date, INTERVAL packages.trial_days DAY)
             WHERE memberships.package_id = ?
               AND memberships.status = 1
               AND memberships.start_date <= ?
               AND memberships.expire_date >= ?
               AND DATEDIFF(memberships.expire_date, memberships.start_date) > packages.trial_days
               AND packages.trial_days > 0
               AND DATE_ADD(memberships.start_date, INTERVAL packages.trial_days DAY) > ?',
            [
                self::PACKAGE_ID,
                now()->toDateString(),
                now()->toDateString(),
                now()->toDateString(),
            ]
        );

        Cache::forget('payment_active_packages');
    }

    /**
     * Original expire dates are not recoverable, so reverse is a no-op.
     */
    public function down(): void
    {
        Cache::forget('payment_active_packages');
    }
};
