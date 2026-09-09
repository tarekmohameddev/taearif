<?php

declare(strict_types=1);

namespace App\Services\Analytics;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardVisitService
{
    public const BUSINESS_TIMEZONE = 'Asia/Riyadh';

    public function __construct(
        private readonly DashboardPresenceEligibilityService $eligibility
    ) {
    }

    public function recordEligibleVisit(User $user, ?CarbonImmutable $clock = null): array
    {
        $eligibility = $this->eligibility->resolve($user);

        if (! $eligibility['eligible']) {
            return [
                'recorded' => false,
                'reason' => $eligibility['reason'],
            ];
        }

        $this->recordResolvedVisit($user, (int) $eligibility['tenant_owner_id'], $clock);

        return [
            'recorded' => true,
            'reason' => null,
        ];
    }

    public function recordFor(User $user, ?CarbonImmutable $clock = null): void
    {
        $eligibility = $this->eligibility->resolve($user);

        if (! $eligibility['eligible']) {
            return;
        }

        $this->recordResolvedVisit($user, (int) $eligibility['tenant_owner_id'], $clock);
    }

    private function recordResolvedVisit(User $user, int $tenantOwnerId, ?CarbonImmutable $clock = null): void
    {
        $now = ($clock ?? CarbonImmutable::now(self::BUSINESS_TIMEZONE))
            ->setTimezone(self::BUSINESS_TIMEZONE);

        $visitedOn = $now->toDateString();

        $inserted = DB::table('dashboard_daily_visits')->insertOrIgnore([
            'user_id' => $user->id,
            'tenant_owner_id' => $tenantOwnerId,
            'visited_on' => $visitedOn,
            'first_seen_at' => $now,
            'last_seen_at' => $now,
            'visits_count' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($inserted === 0) {
            DB::table('dashboard_daily_visits')
                ->where('user_id', $user->id)
                ->where('visited_on', $visitedOn)
                ->update([
                    'tenant_owner_id' => $tenantOwnerId,
                    'last_seen_at' => $now,
                    'visits_count' => DB::raw('visits_count + 1'),
                    'updated_at' => $now,
                ]);
        }
    }
}
