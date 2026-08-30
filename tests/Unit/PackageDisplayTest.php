<?php

namespace Tests\Unit;

use App\Models\Package;
use App\Services\MembershipService;
use Tests\TestCase;

class PackageDisplayTest extends TestCase
{
    public function test_is_trial_package_by_term(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'trial_days' => 7,
        ]);

        $this->assertTrue($package->isTrialPackage());
    }

    /**
     * Package 28 — a 30-day trial on a monthly term. It is a trial only by the
     * is_trial flag, so this is what keeps that clause in isTrialPackage().
     */
    public function test_is_trial_flag_marks_a_monthly_term_package_as_trial(): void
    {
        $package = new Package([
            'id' => 28,
            'term' => MembershipService::TERM_MONTHLY,
            'is_trial' => 1,
            'trial_days' => 30,
            'title' => 'الباقة الشهرية للتجربة',
        ]);

        $this->assertTrue($package->isTrialPackage());
        $this->assertSame('الباقة الشهرية للتجربة (30 أيام)', $package->getDisplayTitle('ar'));
    }

    /**
     * Package 16 — free, not a trial, but carries a leftover trial_days of 360.
     * A day count must not appear on it: trial_days alone is not the trigger.
     */
    public function test_trial_days_without_the_flag_keeps_plain_title(): void
    {
        $package = new Package([
            'id' => MembershipService::FREE_PACKAGE_ID,
            'term' => MembershipService::TERM_YEARLY,
            'is_trial' => 0,
            'trial_days' => 360,
            'title' => 'الباقة المجانية',
            'title_en' => 'Free Package',
        ]);

        $this->assertFalse($package->isTrialPackage());
        $this->assertSame('الباقة المجانية', $package->getDisplayTitle('ar'));
        $this->assertSame('Free Package', $package->getDisplayTitleEn());
    }

    public function test_is_trial_package_by_configured_id(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_MONTHLY,
            'is_trial' => 0,
            'trial_days' => 7,
        ]);
        $package->id = MembershipService::TRIAL_PACKAGE_ID;

        $this->assertTrue($package->isTrialPackage());
    }

    public function test_non_trial_package_is_not_trial(): void
    {
        $package = new Package([
            'id' => MembershipService::PAID_MONTHLY_PACKAGE_ID,
            'term' => MembershipService::TERM_MONTHLY,
            'is_trial' => 0,
            'title' => 'الباقة المميزة الشهرية',
            'title_en' => 'Premium Monthly Package',
        ]);

        $this->assertFalse($package->isTrialPackage());
    }

    public function test_arabic_display_title_for_trial_uses_actual_trial_days(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'title' => 'الباقة التجريبية',
            'trial_days' => 14,
        ]);

        $this->assertSame('الباقة التجريبية (14 أيام)', $package->getDisplayTitle('ar'));
    }

    public function test_english_display_title_for_trial_uses_trial_prefix(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'title' => 'الباقة التجريبية',
            'title_en' => 'Trial Package',
            'trial_days' => 7,
        ]);

        $this->assertSame('Trial (7 days)', $package->getDisplayTitleEn());
        $this->assertSame('Trial (7 days)', $package->getDisplayTitle('en'));
    }

    public function test_trial_display_falls_back_to_default_days_when_missing(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_TRIAL,
            'title' => 'الباقة التجريبية',
            'trial_days' => 0,
        ]);

        $this->assertSame(
            'الباقة التجريبية (' . MembershipService::DEFAULT_TRIAL_DAYS . ' أيام)',
            $package->getDisplayTitle('ar')
        );
        $this->assertSame(
            'Trial (' . MembershipService::DEFAULT_TRIAL_DAYS . ' days)',
            $package->getDisplayTitleEn()
        );
    }

    public function test_non_trial_display_title_keeps_existing_titles(): void
    {
        $package = new Package([
            'term' => MembershipService::TERM_YEARLY,
            'title' => 'الباقة المميزة سنوية',
            'title_en' => 'Premium Annual Package',
        ]);

        $this->assertSame('الباقة المميزة سنوية', $package->getDisplayTitle('ar'));
        $this->assertSame('Premium Annual Package', $package->getDisplayTitleEn());
    }
}
