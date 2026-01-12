<?php

namespace App\Console\Commands;

use App\Models\Api\AppPaymentTransaction;
use App\Models\Api\ApiInstallation;
use App\Services\Payment\ArbPaymentVerificationService;
use App\Services\InstallationStateMachine;
use App\Enums\InstallStatus;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class VerifyPendingAppPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:verify-pending-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verify pending app payment transactions and activate installations if payment confirmed';

    protected ArbPaymentVerificationService $verificationService;
    protected InstallationStateMachine $stateMachine;

    public function __construct(
        ArbPaymentVerificationService $verificationService,
        InstallationStateMachine $stateMachine
    ) {
        parent::__construct();
        $this->verificationService = $verificationService;
        $this->stateMachine = $stateMachine;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Checking for pending app payment transactions...');

        // Query pending transactions older than 1 hour
        $pendingTransactions = AppPaymentTransaction::pending()
            ->olderThan(1)
            ->with(['installation', 'app', 'user'])
            ->get();

        if ($pendingTransactions->isEmpty()) {
            $this->info('No pending payment transactions found.');
            return self::SUCCESS;
        }

        $this->info("Found {$pendingTransactions->count()} pending payment transaction(s) to verify.");

        $verifiedCount = 0;
        $failedCount = 0;
        $skippedCount = 0;

        foreach ($pendingTransactions as $transaction) {
            try {
                $this->info("Verifying payment transaction ID: {$transaction->payment_transaction_id}");

                // Verify payment with ARB API
                $verification = $this->verificationService->verifyPayment($transaction->payment_transaction_id);

                if ($verification['verified']) {
                    // Update transaction
                    $transaction->markCompleted($verification['details'], [
                        'verified_by' => 'background_job',
                        'verified_at' => now()->toIso8601String(),
                    ]);

                    // Handle installation update (support both old and new flows)
                    if ($transaction->installation) {
                        if ($transaction->installation->status === InstallStatus::PendingPayment) {
                            // Old flow: transition from PendingPayment to Installed
                            try {
                                $this->stateMachine->transition(
                                    $transaction->installation,
                                    InstallStatus::Installed,
                                    [
                                        'recurring_id' => $verification['details']['RecurringId'] ?? null,
                                        'payment_subscription_id' => $verification['details']['RecurringId'] ?? null,
                                    ]
                                );

                                $this->info("✓ Payment verified and installation activated: Transaction {$transaction->id}, Installation {$transaction->installation->id}");
                                $verifiedCount++;

                                Log::info('App payment verified and installation activated via background job', [
                                    'transaction_id' => $transaction->id,
                                    'installation_id' => $transaction->installation->id,
                                    'payment_id' => $transaction->payment_transaction_id,
                                ]);
                            } catch (\Exception $e) {
                                $this->error("✗ Failed to activate installation for transaction {$transaction->id}: {$e->getMessage()}");
                                $failedCount++;

                                Log::error('Failed to activate installation during background payment verification', [
                                    'transaction_id' => $transaction->id,
                                    'installation_id' => $transaction->installation->id,
                                    'error' => $e->getMessage(),
                                ]);
                            }
                        } else if ($transaction->installation->status === InstallStatus::Installed) {
                            // New flow: installation already installed, just mark transaction as complete
                            if (isset($verification['details']['RecurringId'])) {
                                $transaction->installation->update([
                                    'recurring_id' => $verification['details']['RecurringId'],
                                    'payment_subscription_id' => $verification['details']['RecurringId'],
                                ]);
                            }

                            $this->info("✓ Payment verified (installation already active): Transaction {$transaction->id}, Installation {$transaction->installation->id}");
                            $verifiedCount++;

                            Log::info('App payment verified via background job (installation already active)', [
                                'transaction_id' => $transaction->id,
                                'installation_id' => $transaction->installation->id,
                                'payment_id' => $transaction->payment_transaction_id,
                            ]);
                        } else {
                            // Installation in other state (e.g., Trialing, Uninstalled)
                            $this->info("✓ Payment verified but installation in state {$transaction->installation->status->value}: Transaction {$transaction->id}");
                            $verifiedCount++;
                        }
                    } else {
                        // No installation found
                        $this->info("✓ Payment verified but no installation found: Transaction {$transaction->id}");
                        $verifiedCount++;
                    }
                } else {
                    $this->warn("✗ Payment verification failed for transaction {$transaction->id}: {$verification['status']}");
                    $failedCount++;

                    Log::warning('Payment verification failed in background job', [
                        'transaction_id' => $transaction->id,
                        'payment_id' => $transaction->payment_transaction_id,
                        'verification_status' => $verification['status'],
                    ]);
                }
            } catch (\Exception $e) {
                $this->error("✗ Error processing transaction {$transaction->id}: {$e->getMessage()}");
                $failedCount++;

                Log::error('Error in background payment verification', [
                    'transaction_id' => $transaction->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info('');
        $this->info('Payment verification process completed.');
        $this->info("Summary: {$verifiedCount} verified, {$failedCount} failed, {$skippedCount} skipped.");

        return self::SUCCESS;
    }
}
