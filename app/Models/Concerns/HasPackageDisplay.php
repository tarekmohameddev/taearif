<?php

namespace App\Models\Concerns;

use App\Models\Membership;
use App\Services\MembershipService;

trait HasPackageDisplay
{
    /**
     * Whether this package is a trial, and so renders a "(N أيام)" day count.
     *
     * There is more than one trial package: 26 (7 days) and 28 (30 days) at the
     * time of writing, so this cannot key on the canonical trial id alone.
     * packages.is_trial marks them, and only them.
     *
     * NOTE: is_trial has previously been used to mean "this package is not
     * paid" and was set on the free package (16), which put a phantom day count
     * on it. If that ever recurs, fix the data — do not narrow this method,
     * or genuine trials like 28 silently lose their day count.
     */
    public function isTrialPackage(): bool
    {
        $trialPackageId = (int) config('membership.trial_package_id', MembershipService::TRIAL_PACKAGE_ID);

        return $this->term === MembershipService::TERM_TRIAL
            || (int) $this->is_trial === 1
            || (int) $this->id === $trialPackageId;
    }

    public function isFreePackage(): bool
    {
        $freePackageId = (int) config('membership.free_package_id', MembershipService::FREE_PACKAGE_ID);

        return (int) $this->id === $freePackageId;
    }

    public function getDisplayTitle(?string $locale = null, ?Membership $membership = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->isTrialPackage()) {
            $days = $this->getEffectiveTrialDays();

            if ($locale === 'en' || str_starts_with($locale, 'en')) {
                return "Trial ({$days} days)";
            }

            return ($this->title ?? '') . " ({$days} أيام)";
        }

        if ($this->isFreePackage() && $this->shouldShowFreePackageDays($membership)) {
            $days = $membership->getDaysOnPackage();

            if ($locale === 'en' || str_starts_with($locale, 'en')) {
                return ($this->title_en ?: ($this->title ?? '')) . " ({$days} days)";
            }

            return ($this->title ?? '') . " ({$days} يوماً)";
        }

        return $this->title ?? '';
    }

    public function getDisplayTitleEn(?Membership $membership = null): string
    {
        if ($this->isTrialPackage()) {
            return 'Trial (' . $this->getEffectiveTrialDays() . ' days)';
        }

        if ($this->isFreePackage() && $this->shouldShowFreePackageDays($membership)) {
            return ($this->title_en ?: ($this->title ?? '')) . ' (' . $membership->getDaysOnPackage() . ' days)';
        }

        return $this->title_en ?: ($this->title ?? '');
    }

    protected function shouldShowFreePackageDays(?Membership $membership): bool
    {
        return $membership !== null
            && (int) $membership->status === 1
            && ! empty($membership->start_date);
    }

    protected function getEffectiveTrialDays(): int
    {
        $days = (int) ($this->trial_days ?? 0);

        return $days > 0 ? $days : MembershipService::DEFAULT_TRIAL_DAYS;
    }
}
