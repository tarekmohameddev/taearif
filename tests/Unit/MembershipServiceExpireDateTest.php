<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Services\MembershipService;
use Carbon\Carbon;
use Tests\TestCase;

class MembershipServiceExpireDateTest extends TestCase
{
    public function test_trial_package_expires_after_seven_days(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'trial_days' => 7,
        ]);

        $start = Carbon::parse('2026-08-18');
        $expire = app(MembershipService::class)->calculateExpireDate($package, $start);

        $this->assertSame('2026-08-25', $expire->toDateString());
        $this->assertSame(7, $start->diffInDays($expire));
    }

    public function test_trial_package_with_zero_trial_days_falls_back_to_default(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'trial_days' => 0,
        ]);

        $start = Carbon::parse('2026-08-18');
        $expire = app(MembershipService::class)->calculateExpireDate($package, $start);

        $this->assertSame(
            $start->copy()->addDays(MembershipService::DEFAULT_TRIAL_DAYS)->toDateString(),
            $expire->toDateString()
        );
    }

    public function test_trial_package_with_null_trial_days_falls_back_to_default(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'trial_days' => null,
        ]);

        $start = Carbon::parse('2026-08-18');
        $expire = app(MembershipService::class)->calculateExpireDate($package, $start);

        $this->assertSame(
            $start->copy()->addDays(MembershipService::DEFAULT_TRIAL_DAYS)->toDateString(),
            $expire->toDateString()
        );
    }

    public function test_trial_expiry_ignores_billing_period(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'trial_days' => 7,
        ]);

        $start = Carbon::parse('2026-08-18');
        $expire = app(MembershipService::class)->calculateExpireDate($package, $start, 3);

        $this->assertSame('2026-08-25', $expire->toDateString());
    }
}
