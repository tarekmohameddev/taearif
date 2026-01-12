<?php

namespace App\Services;

use App\Models\User;
use App\Enums\BillingType;
use App\Models\Api\ApiApp;
use Carbon\CarbonImmutable;
use App\Enums\InstallStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Api\ApiInstallation;
use App\Models\Api\AppPaymentTransaction;
use App\Http\Controllers\Payment\ArbController;
use App\Exceptions\Installation\InvalidInstallationException;
use App\Exceptions\Installation\PaymentInitiationException;
use App\Services\TrialPeriodService;
use App\Services\InstallationStateMachine;
use App\Services\InstallationLockService;

/**
 * Installation Service
 *
 * Handles app installation logic with proper error handling,
 * trial period management, and concurrent installation protection
 */
class InstallationService
{
    protected TrialPeriodService $trialService;
    protected InstallationStateMachine $stateMachine;
    protected InstallationLockService $lockService;

    public function __construct(
        TrialPeriodService $trialService,
        InstallationStateMachine $stateMachine,
        InstallationLockService $lockService
    ) {
        $this->trialService = $trialService;
        $this->stateMachine = $stateMachine;
        $this->lockService = $lockService;
    }

    /**
     * Install an app for a user
     *
     * @param User $user The user installing the app
     * @param ApiApp $app The app to install
     * @param array $settings Optional installation settings
     * @return array{installation: ApiInstallation, payment_url: string|null}
     * @throws InvalidInstallationException
     * @throws PaymentInitiationException
     */
    public function install(User $user, ApiApp $app, array $settings = []): array
    {
        // Validate inputs
        if (!$user || !$app) {
            throw InvalidInstallationException::invalidUser($user?->id);
        }

        if (!$app->is_enabled) {
            throw InvalidInstallationException::appNotEnabled($app->id);
        }

        // Use lock to prevent concurrent installations
        return $this->lockService->withLock($user->id, $app->id, function () use ($user, $app, $settings) {
            return DB::transaction(function () use ($user, $app, $settings) {
                Log::info('Starting app installation', [
                    'user_id' => $user->id,
                    'app_id' => $app->id,
                    'app_name' => $app->name,
                ]);

                // Get existing installation if any
                $existingInstall = ApiInstallation::withTrashed()
                    ->where('user_id', $user->id)
                    ->where('app_id', $app->id)
                    ->first();

                // Determine installation status and trial info
                [$status, $trialEnds, $trialUsedAt] = $this->determineInstallationStatus(
                    $user,
                    $app,
                    $existingInstall
                );

                // Create or update installation
                $install = ApiInstallation::updateOrCreate(
                    ['user_id' => $user->id, 'app_id' => $app->id],
                    [
                        'status' => $status,
                        'activated_at' => $existingInstall?->activated_at ?? now(),
                        'trial_ends_at' => $existingInstall?->trial_ends_at ?? $trialEnds,
                        'trial_used_at' => $existingInstall?->trial_used_at ?? $trialUsedAt,
                        'installed' => in_array($status, [InstallStatus::Installed, InstallStatus::Trialing], true),
                        'installed_at' => $existingInstall?->installed_at ?? ($status === InstallStatus::Installed ? now() : null),
                        'uninstalled_at' => null,
                        'current_period_end' => $existingInstall?->current_period_end ?? $trialEnds,
                        'invoice_id' => null,
                        'payment_subscription_id' => null,
                    ]
                );

                // Save settings
                $install->settings()->updateOrCreate([], ['settings' => $settings]);

                // Initiate payment if app requires payment (regardless of installation status)
                $paymentUrl = null;
                if ($app->billing_type === BillingType::Paid || 
                    ($app->billing_type === BillingType::PaidTrial && $status !== InstallStatus::Trialing)) {
                    $paymentUrl = $this->initiatePayment($install, $app, $user);
                }

                Log::info('App installation completed', [
                    'installation_id' => $install->id,
                    'user_id' => $user->id,
                    'app_id' => $app->id,
                    'status' => $status->value,
                    'requires_payment' => $paymentUrl !== null,
                ]);

                return [
                    'installation' => $install->fresh(['settings', 'app']),
                    'payment_url' => $paymentUrl,
                ];
            });
        });
    }

    /**
     * Determine installation status based on billing type and trial eligibility
     *
     * @param User $user
     * @param ApiApp $app
     * @param ApiInstallation|null $existingInstall
     * @return array{0: InstallStatus, 1: CarbonImmutable|null, 2: CarbonImmutable|null}
     */
    protected function determineInstallationStatus(
        User $user,
        ApiApp $app,
        ?ApiInstallation $existingInstall
    ): array {
        $status = InstallStatus::Installed;
        $trialEnds = null;
        $trialUsedAt = null;

        switch ($app->billing_type) {
            case BillingType::Free:
                $status = InstallStatus::Installed;
                break;

            case BillingType::Paid:
                // Install immediately, payment handled separately
                $status = InstallStatus::Installed;
                break;

            case BillingType::PaidTrial:
                $trialInfo = $this->trialService->getExistingTrialInfo($user, $app);

                if ($trialInfo && $trialInfo['status'] === 'active') {
                    // Still within existing trial period
                    $status = InstallStatus::Trialing;
                    $trialEnds = $trialInfo['trial_ends_at'];
                    $trialUsedAt = $trialInfo['trial_used_at'];
                } elseif ($this->trialService->isEligibleForTrial($user, $app)) {
                    // Eligible for new trial
                    $status = InstallStatus::Trialing;
                    $trialEnds = $this->trialService->calculateTrialEndDate($app);
                    $trialUsedAt = CarbonImmutable::now();
                } else {
                    // Trial used, install immediately, payment handled separately
                    $status = InstallStatus::Installed;
                }
                break;
        }

        return [$status, $trialEnds, $trialUsedAt];
    }

    /**
     * Initiate payment for paid apps
     *
     * @param ApiInstallation $install
     * @param ApiApp $app
     * @param User $user
     * @return string Payment redirect URL
     * @throws PaymentInitiationException
     */
    protected function initiatePayment(
        ApiInstallation $install,
        ApiApp $app,
        User $user
    ): string {
        try {
            $arb = app(ArbController::class);
            $resp = $arb->paymentProcessForApp($user, $app);

            if ($resp === 'error') {
                throw PaymentInitiationException::gatewayError('arb', 'Payment gateway returned error');
            }

            if (!isset($resp['redirect_url'])) {
                throw PaymentInitiationException::noRedirectUrl('arb');
            }

            // Extract payment ID from redirect URL
            parse_str(parse_url($resp['redirect_url'], PHP_URL_QUERY), $query);
            $paymentId = $query['PaymentID'] ?? null;

            if (!$paymentId) {
                throw PaymentInitiationException::gatewayError('arb', 'Payment ID not found in redirect URL');
            }

            // Create payment transaction record
            $transaction = AppPaymentTransaction::create([
                'user_id' => $user->id,
                'installation_id' => $install->id,
                'app_id' => $app->id,
                'payment_transaction_id' => $paymentId,
                'gateway' => 'arb',
                'amount' => $app->price,
                'currency' => 'SAR',
                'status' => 'pending',
                'gateway_response' => $resp,
                'metadata' => [
                    'payment_initiated_at' => now()->toIso8601String(),
                    'redirect_url' => $resp['redirect_url'],
                ],
            ]);

            // Store invoice_id for payment tracking (installation is already installed)
            $install->update(['invoice_id' => $paymentId]);

            Log::info('Payment initiated for installation', [
                'installation_id' => $install->id,
                'payment_id' => $paymentId,
                'app_id' => $app->id,
                'transaction_id' => $transaction->id,
            ]);

            return $resp['redirect_url'];

        } catch (PaymentInitiationException $e) {
            Log::error('Payment initiation failed', [
                'installation_id' => $install->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error during payment initiation', [
                'installation_id' => $install->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw PaymentInitiationException::gatewayError('arb', $e->getMessage());
        }
    }
}
