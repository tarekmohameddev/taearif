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
                if ($eligibleForTrial) {
                    $status    = InstallStatus::Trialing;
                    $trialEnds = CarbonImmutable::now()->addDays(15);
                } else {
                    $status = InstallStatus::PendingPayment;
                }
            }


            $install = ApiInstallation::updateOrCreate(
                ['user_id' => $user->id, 'app_id' => $app->id],
                [
                    'status'              => $status,
                    'activated_at'        => now(),
                    'trial_ends_at'       => $trialEnds,
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
