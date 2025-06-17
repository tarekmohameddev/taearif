<?php

namespace App\Services;

use App\Models\User;
use App\Enums\BillingType;
use App\Models\Api\ApiApp;
use Carbon\CarbonImmutable;
use App\Enums\InstallStatus;
use Illuminate\Support\Facades\DB;
use App\Models\Api\ApiInstallation;
use App\Http\Controllers\Payment\ArbController;

class InstallationService
{

    public function install(User $user, ApiApp $app, array $settings = []): array
    {
        // Validate user and app
        return DB::transaction(function () use ($user, $app, $settings) {

            if (! $user || ! $app) {
                throw new \Exception('Invalid user or app');
            }
            $trialUsedAt = null;
            $hadInstallBefore = ApiInstallation::withTrashed()
                ->forUser($user->id)->forApp($app->id)
                ->whereNotNull('activated_at')->exists();

            $eligibleForTrial = ! $hadInstallBefore;
            $status     = InstallStatus::Installed;
            $trialEnds  = null;

            if ($app->billing_type === BillingType::Paid) {
                $status = InstallStatus::PendingPayment;
            }

            if ($app->billing_type === BillingType::PaidTrial) {
                $now = CarbonImmutable::now();

                $previousInstall = ApiInstallation::withTrashed()
                    ->forUser($user->id)
                    ->forApp($app->id)
                    ->orderByDesc('activated_at')
                    ->first();

                $alreadyUsedTrial = $previousInstall && $previousInstall->trial_used_at !== null;

                if (! $alreadyUsedTrial) {
                    // First time trial
                    $status     = InstallStatus::Trialing;
                    $trialEnds  = $now->addDays($app->trial_days ?? 15);
                    $trialUsedAt = $now;
                } elseif (
                    $previousInstall->status === InstallStatus::Trialing &&
                    $previousInstall->trial_ends_at &&
                    $now->lt($previousInstall->trial_ends_at)
                ) {
                    // Still within previous trial
                    $status     = InstallStatus::Trialing;
                    $trialEnds  = $previousInstall->trial_ends_at;
                    $trialUsedAt = $previousInstall->trial_used_at;
                } else {
                    // Trial expired or already used
                    $status     = InstallStatus::PendingPayment;
                    $trialEnds  = null;
                    $trialUsedAt = $previousInstall->trial_used_at;
                }
            }
            if ($app->billing_type === BillingType::Free) {
                $status = InstallStatus::Installed;
                $trialEnds = null;
                $trialUsedAt = null;
            }


            $install = ApiInstallation::updateOrCreate(
                ['user_id' => $user->id, 'app_id' => $app->id],
                [
                    'status'              => $status,
                    'activated_at'        => now(),
                    'trial_ends_at'       => $trialEnds,
                    'trial_used_at'       => $trialUsedAt ?? null,
                    'installed'           => $status === InstallStatus::Installed,
                    'installed_at'        => $status === InstallStatus::Installed ? now() : null,
                    'uninstalled_at'      => null,
                    'current_period_end'  => $trialEnds,
                    'invoice_id'          => null,
                    'payment_subscription_id' => null,
                ]
            );

            $install->settings()->updateOrCreate([], ['settings' => $settings]);

            $paymentUrl = null;
            if ($status === InstallStatus::PendingPayment) {
                $paymentUrl = $this->kickOffPayment($install, $app, $user);
            }

            return ['installation' => $install->fresh(), 'payment_url' => $paymentUrl];
        });
    }

    private function kickOffPayment(ApiInstallation $install, ApiApp $app, User $user): ?string
    {
        $arb  = app(\App\Http\Controllers\Payment\ArbController::class);
        $resp = $arb->paymentProcessForApp($user, $app);

        if ($resp === 'error') {

            return null;
        }

        if (! isset($resp['redirect_url'])) {
            throw new \Exception('Payment process failed, no redirect URL provided');
        }

        parse_str(parse_url($resp['redirect_url'], PHP_URL_QUERY), $q);
        $install->markPending($q['PaymentID'] ?? '');

        return $resp['redirect_url'];
    }


}
