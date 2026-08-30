<?php

namespace App\Models\Concerns;

use App\Services\MembershipService;

trait HasPackageDisplay
{
    public function isTrialPackage(): bool
    {
        $trialPackageId = (int) config('membership.trial_package_id', MembershipService::TRIAL_PACKAGE_ID);

        return $this->term === MembershipService::TERM_TRIAL
            || (int) $this->is_trial === 1
            || (int) $this->id === $trialPackageId;
    }

    public function getDisplayTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($this->isTrialPackage()) {
            $days = $this->getEffectiveTrialDays();

            if ($locale === 'en' || str_starts_with($locale, 'en')) {
                return "Trial ({$days} days)";
            }

            return ($this->title ?? '') . " ({$days} أيام)";
        }

        return $this->title ?? '';
    }

    public function getDisplayTitleEn(): string
    {
        if ($this->isTrialPackage()) {
            return 'Trial (' . $this->getEffectiveTrialDays() . ' days)';
        }

        return $this->title_en ?: ($this->title ?? '');
    }

    protected function getEffectiveTrialDays(): int
    {
        $days = (int) ($this->trial_days ?? 0);

        return $days > 0 ? $days : MembershipService::DEFAULT_TRIAL_DAYS;
    }
}
