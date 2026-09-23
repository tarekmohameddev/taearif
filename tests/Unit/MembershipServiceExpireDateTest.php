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

    public function test_fixed_duration_package_expires_after_duration_months(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'price' => 996,
            'duration_months' => 24,
        ]);

        $start = Carbon::parse('2026-10-15');
        $expire = app(MembershipService::class)->calculateExpireDate($package, $start);

        $this->assertSame('2028-10-15', $expire->toDateString());
        $this->assertSame(24, $start->diffInMonths($expire));
    }

    public function test_fixed_duration_ignores_ordinary_yearly_term_calculation(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'duration_months' => 12,
        ]);

        $start = Carbon::parse('2026-10-15');
        $expire = app(MembershipService::class)->calculateExpireDate($package, $start, 2);

        $this->assertSame('2027-10-15', $expire->toDateString());
    }

    public function test_fixed_duration_does_not_mutate_start_carbon(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'duration_months' => 24,
        ]);

        $start = Carbon::parse('2026-10-15');
        app(MembershipService::class)->calculateExpireDate($package, $start);

        $this->assertSame('2026-10-15', $start->toDateString());
    }

    public function test_fixed_duration_uses_no_overflow_month_arithmetic(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'duration_months' => 1,
        ]);

        $monthEnd = Carbon::parse('2026-01-31');
        $this->assertSame(
            '2026-02-28',
            app(MembershipService::class)->calculateExpireDate($package, $monthEnd)->toDateString()
        );

        $leapYear = Carbon::parse('2024-01-31');
        $this->assertSame(
            '2024-02-29',
            app(MembershipService::class)->calculateExpireDate($package, $leapYear)->toDateString()
        );
    }

    public function test_monthly_yearly_and_lifetime_expiry_remain_unchanged(): void
    {
        $monthly = new Package(['term' => MembershipService::TERM_MONTHLY]);
        $yearly = new Package(['term' => MembershipService::TERM_YEARLY]);
        $lifetime = new Package(['term' => MembershipService::TERM_LIFETIME]);

        $this->assertSame(
            '2026-09-18',
            app(MembershipService::class)
                ->calculateExpireDate($monthly, Carbon::parse('2026-08-18'), 1)
                ->toDateString()
        );
        $this->assertSame(
            '2029-08-18',
            app(MembershipService::class)
                ->calculateExpireDate($yearly, Carbon::parse('2026-08-18'), 3)
                ->toDateString()
        );
        $this->assertSame(
            Carbon::maxValue()->toDateString(),
            app(MembershipService::class)
                ->calculateExpireDate($lifetime, Carbon::parse('2026-08-18'))
                ->toDateString()
        );
    }

    public function test_fixed_duration_expected_amount_returns_full_price_with_period_one(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'price' => 996,
            'duration_months' => 24,
        ]);

        $this->assertSame(996.0, app(MembershipService::class)->calculateExpectedMembershipAmount($package, 1));
    }

    public function test_fixed_duration_expected_amount_does_not_multiply_by_period(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'price' => 996,
            'duration_months' => 24,
        ]);

        $this->assertSame(996.0, app(MembershipService::class)->calculateExpectedMembershipAmount($package, 2));
    }

    public function test_zero_duration_is_not_treated_as_fixed_duration(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'price' => 999,
            'duration_months' => 0,
        ]);

        $start = Carbon::parse('2026-10-15');

        $this->assertSame(
            '2028-10-15',
            app(MembershipService::class)->calculateExpireDate($package, $start, 2)->toDateString()
        );
        $this->assertSame(
            1998.0,
            app(MembershipService::class)->calculateExpectedMembershipAmount($package, 2)
        );
    }

    public function test_ordinary_yearly_expected_amount_multiplies_by_period(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'price' => 999,
        ]);

        $this->assertSame(1998.0, app(MembershipService::class)->calculateExpectedMembershipAmount($package, 2));
    }

    public function test_lifetime_expected_amount_remains_unchanged(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_LIFETIME,
            'price' => 500,
        ]);

        $this->assertSame(500.0, app(MembershipService::class)->calculateExpectedMembershipAmount($package, 2));
    }
}
