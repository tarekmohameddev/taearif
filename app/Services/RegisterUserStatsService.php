<?php

declare(strict_types=1);

namespace App\Services;

use Closure;

/**
 * Bucket counts for the "إحصائيات عامة" card on admin/register/users.
 *
 * Every bucket keys on packages.id, not on packages.term and never on
 * memberships.is_trial:
 *   - memberships.is_trial is wrong on ~127 production rows, 69 of them on
 *     the paid annual package.
 *   - package 28 is a 30-day trial whose term is 'monthly', so a term-based
 *     "paid monthly" query would swallow it.
 *   - the page's package filter buttons filter by package_id, so keying on
 *     id is what keeps each card in agreement with its button.
 */
class RegisterUserStatsService
{
    /**
     * @param  Closure():\Illuminate\Database\Eloquent\Builder  $base
     *         Factory returning a fresh tenant-scoped User builder.
     * @param  array{from: ?\Carbon\Carbon, to: ?\Carbon\Carbon, title: string}|null  $registrationWindow
     *         Window the registrations bucket reports on. Defaults to the
     *         current month, counted from the 1st up to now.
     * @return array<int, array{key: string, title: string, unit: string, count: int}>
     */
    public function counts(Closure $base, ?array $registrationWindow = null): array
    {
        $window  = $registrationWindow ?? $this->currentMonthWindow();
        $today   = now()->toDateString();
        $free    = (int) config('membership.free_package_id', MembershipService::FREE_PACKAGE_ID);
        $yearly  = MembershipService::PAID_YEARLY_PACKAGE_ID;
        $monthly = MembershipService::PAID_MONTHLY_PACKAGE_ID;
        $trial7  = (int) config('membership.trial_package_id', MembershipService::TRIAL_PACKAGE_ID);
        $trial30 = (int) config('membership.trial_monthly_package_id', MembershipService::TRIAL_MONTHLY_PACKAGE_ID);
        $paidIds = [$yearly, $monthly];

        return [
            [
                'key'   => 'registrations',
                'title' => $window['title'],
                'unit'  => 'مستخدم',
                'count' => $this->registrationCount($base, $window),
            ],
            [
                'key'   => 'paid_yearly',
                'title' => 'اشتراكات سنوية مدفوعة',
                'unit'  => 'اشتراك',
                'count' => $base()
                    ->whereHas('currentMembership', fn ($m) => $m->where('package_id', $yearly))
                    ->count(),
            ],
            [
                'key'   => 'paid_monthly',
                'title' => 'اشتراكات شهرية مدفوعة',
                'unit'  => 'اشتراك',
                'count' => $base()
                    ->whereHas('currentMembership', fn ($m) => $m->where('package_id', $monthly))
                    ->count(),
            ],
            [
                'key'   => 'trial_7_active',
                'title' => 'تجربة 7 أيام (جارية)',
                'unit'  => 'تجربة',
                'count' => $this->activeTrialCount($base, $trial7, $today),
            ],
            [
                'key'   => 'trial_30_active',
                'title' => 'تجربة 30 يوم (جارية)',
                'unit'  => 'تجربة',
                'count' => $this->activeTrialCount($base, $trial30, $today),
            ],
            [
                'key'   => 'trial_7_expired',
                'title' => 'منتهية بعد تجربة 7 أيام',
                'unit'  => 'تجربة',
                'count' => $this->churnedTrialCount($base, $trial7, $paidIds, $today),
            ],
            [
                'key'   => 'trial_30_expired',
                'title' => 'منتهية بعد تجربة 30 يوم',
                'unit'  => 'تجربة',
                'count' => $this->churnedTrialCount($base, $trial30, $paidIds, $today),
            ],
            [
                'key'   => 'expired',
                'title' => 'اشتراكات منتهية',
                'unit'  => 'اشتراك',
                'count' => $this->expiredCount($base, $free, $today),
            ],
        ];
    }

    /**
     * The default window: the 1st of the current month up to now.
     *
     * @return array{from: \Carbon\Carbon, to: \Carbon\Carbon, title: string}
     */
    private function currentMonthWindow(): array
    {
        return [
            'from'  => now()->startOfMonth(),
            'to'    => now(),
            'title' => 'المسجلون الجدد هذا الشهر',
        ];
    }

    /**
     * Bounds are applied separately, not via whereBetween, so a half-open
     * filter (only a From date, or only a To date) still works.
     */
    private function registrationCount(Closure $base, array $window): int
    {
        $query = $base();

        if (! empty($window['from'])) {
            $query->where('created_at', '>=', $window['from']);
        }

        if (! empty($window['to'])) {
            $query->where('created_at', '<=', $window['to']);
        }

        return $query->count();
    }

    /**
     * expire_date > today, not >=: the row badge treats the expire date as
     * exclusive while currentMembership treats it as inclusive, so a trial
     * expiring today already renders as "منتهي". Same rule as the existing
     * trial branch of the package_id filter in RegisterUserController.
     */
    private function activeTrialCount(Closure $base, int $packageId, string $today): int
    {
        return $base()
            ->whereHas('currentMembership', fn ($m) => $m
                ->where('package_id', $packageId)
                ->whereDate('expire_date', '>', $today))
            ->count();
    }

    /**
     * Trial churn: the trial ran out and the user never bought a paid plan.
     * "Never", not "not currently" — someone who bought annual and later
     * lapsed converted, and belongs in the expired bucket instead.
     */
    private function churnedTrialCount(Closure $base, int $packageId, array $paidIds, string $today): int
    {
        return $base()
            ->whereHas('memberships', fn ($m) => $m
                ->where('package_id', $packageId)
                ->where('status', 1)
                ->whereDate('expire_date', '<=', $today))
            ->whereDoesntHave('memberships', fn ($m) => $m
                ->whereIn('package_id', $paidIds)
                ->where('status', 1))
            ->count();
    }

    /**
     * Mirrors the "منتهي" badge in the Subscription column: either the user
     * was auto-downgraded to the free package on expiry, or they had a
     * membership that lapsed and have no active one (the same definition
     * ExpiredUser uses). Users who never subscribed render as "غير مشترك"
     * and are deliberately excluded.
     */
    private function expiredCount(Closure $base, int $freePackageId, string $today): int
    {
        return $base()
            ->where(function ($q) use ($freePackageId, $today) {
                $q->whereHas('currentMembership', fn ($m) => $m->where('package_id', $freePackageId))
                  ->orWhere(function ($inner) use ($today) {
                      $inner->whereDoesntHave('currentMembership')
                            ->whereHas('memberships', fn ($m) => $m
                                ->where('status', 1)
                                ->whereDate('expire_date', '<', $today));
                  });
            })
            ->count();
    }
}
