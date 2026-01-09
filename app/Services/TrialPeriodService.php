<?php

namespace App\Services;

use App\Models\Api\ApiApp;
use App\Models\Api\ApiInstallation;
use App\Models\User;
use App\Enums\BillingType;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;

/**
 * Trial Period Service
 *
 * Handles trial period calculations and eligibility checks
 */
class TrialPeriodService
{
    /**
     * Check if user is eligible for trial
     *
     * @param User $user
     * @param ApiApp $app
     * @return bool
     */
    public function isEligibleForTrial(User $user, ApiApp $app): bool
    {
        if ($app->billing_type !== BillingType::PaidTrial) {
            return false;
        }

        // Check if user has used trial before
        $hadInstallBefore = ApiInstallation::withTrashed()
            ->where('user_id', $user->id)
            ->where('app_id', $app->id)
            ->whereNotNull('activated_at')
            ->exists();

        return !$hadInstallBefore;
    }

    /**
     * Calculate trial end date
     *
     * @param ApiApp $app
     * @param CarbonImmutable|null $startDate
     * @return CarbonImmutable|null
     */
    public function calculateTrialEndDate(
        ApiApp $app,
        ?CarbonImmutable $startDate = null
    ): ?CarbonImmutable {
        if ($app->billing_type !== BillingType::PaidTrial) {
            return null;
        }

        $startDate = $startDate ?? CarbonImmutable::now();
        $trialDays = $app->trial_days ?? 15;

        return $startDate->addDays($trialDays);
    }

    /**
     * Get existing trial information if user has active trial
     *
     * @param User $user
     * @param ApiApp $app
     * @return array{status: string, trial_ends_at: CarbonImmutable|null, trial_used_at: CarbonImmutable|null}|null
     */
    public function getExistingTrialInfo(User $user, ApiApp $app): ?array
    {
        $previousInstall = ApiInstallation::withTrashed()
            ->where('user_id', $user->id)
            ->where('app_id', $app->id)
            ->orderByDesc('activated_at')
            ->first();

        if (!$previousInstall) {
            return null;
        }

        $now = CarbonImmutable::now();
        $alreadyUsedTrial = $previousInstall->trial_used_at !== null;

        // Check if still within previous trial period
        if (
            $previousInstall->status === \App\Enums\InstallStatus::Trialing &&
            $previousInstall->trial_ends_at &&
            $now->lt($previousInstall->trial_ends_at)
        ) {
            return [
                'status' => 'active',
                'trial_ends_at' => $previousInstall->trial_ends_at,
                'trial_used_at' => $previousInstall->trial_used_at,
            ];
        }

        return [
            'status' => $alreadyUsedTrial ? 'used' : 'available',
            'trial_ends_at' => $previousInstall->trial_ends_at,
            'trial_used_at' => $previousInstall->trial_used_at,
        ];
    }

    /**
     * Check if trial has expired
     *
     * @param ApiInstallation $installation
     * @return bool
     */
    public function isTrialExpired(ApiInstallation $installation): bool
    {
        if (!$installation->trial_ends_at) {
            return false;
        }

        return CarbonImmutable::now()->isAfter($installation->trial_ends_at);
    }

    /**
     * Get days remaining in trial
     *
     * @param ApiInstallation $installation
     * @return int|null
     */
    public function getTrialDaysRemaining(ApiInstallation $installation): ?int
    {
        if (!$installation->trial_ends_at) {
            return null;
        }

        $now = CarbonImmutable::now();
        if ($now->isAfter($installation->trial_ends_at)) {
            return 0;
        }

        return $now->diffInDays($installation->trial_ends_at, false);
    }
}

