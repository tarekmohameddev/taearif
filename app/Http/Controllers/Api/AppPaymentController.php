<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiInstallation;
use App\Models\Api\AppPaymentTransaction;
use App\Models\PaymentGateway;
use App\Services\InstallationStateMachine;
use App\Services\Payment\ArbPaymentVerificationService;
use App\Enums\InstallStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class AppPaymentController extends Controller
{
    protected InstallationStateMachine $stateMachine;
    protected ArbPaymentVerificationService $verificationService;

    public function __construct(
        InstallationStateMachine $stateMachine,
        ArbPaymentVerificationService $verificationService
    ) {
        $this->stateMachine = $stateMachine;
        $this->verificationService = $verificationService;
    }

    /**
     * Handle payment callback from ARB gateway (API endpoint)
     * Returns JSON instead of redirects for API clients
     *
     * @param Request $request
     * @param string $gateway
     * @return \Illuminate\Http\JsonResponse
     */
    public function handleCallback(Request $request, string $gateway)
    {
        // Detect if this is an API request
        $isApiRequest = $this->isApiRequest($request);

        // Rate limiting: max 50 requests per minute per IP
        $key = 'app_payment_callback:' . $gateway . ':' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 50)) {
            Log::warning('App payment callback rate limit exceeded', [
                'gateway' => $gateway,
                'ip' => $request->ip(),
            ]);

            if ($isApiRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Too many requests',
                ], 429);
            }

            return $this->finalizeRedirect(false, 'Too many requests');
        }

        RateLimiter::hit($key, 60);

        try {
            // Only support ARB for now
            if ($gateway !== 'arb') {
                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Unsupported payment gateway',
                    ], 400);
                }

                return $this->finalizeRedirect(false, 'Unsupported payment gateway');
            }

            // Extract and decrypt payment data
            $paymentData = $this->extractArbPaymentData($request);

            if (empty($paymentData)) {
                Log::warning('Empty payment data received', [
                    'gateway' => $gateway,
                    'request_data' => $request->all(),
                    'ip' => $request->ip(),
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid payment data',
                    ], 422);
                }

                return $this->finalizeRedirect(false, 'Invalid payment data');
            }

            // Validate payment was successful
            if (!isset($paymentData['result']) || $paymentData['result'] !== 'CAPTURED') {
                Log::info('ARB app payment failed or cancelled', [
                    'result' => $paymentData['result'] ?? 'unknown',
                    'payment_id' => $paymentData['transId'] ?? null,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment failed or cancelled',
                        'result' => $paymentData['result'] ?? 'unknown',
                    ], 400);
                }

                return $this->finalizeRedirect(false, 'Payment failed or cancelled');
            }

            // Extract payment information
            $paymentId = $paymentData['transId'] ?? $request->input('PaymentID');
            $appId = $paymentData['udf4'] ?? null;
            $userId = $paymentData['udf2'] ?? null;
            $paidAmount = $paymentData['amt'] ?? 0;

            // Validate required data exists
            if (!$paymentId || !$appId || !$userId) {
                Log::error('ARB app payment missing required data', [
                    'payment_id' => $paymentId,
                    'app_id' => $appId,
                    'user_id' => $userId,
                    'payment_data' => $paymentData,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Missing required payment data',
                    ], 422);
                }

                return $this->finalizeRedirect(false, 'Missing required payment data');
            }

            // Check idempotency: if transaction already processed, return success
            $existingTransaction = AppPaymentTransaction::where('payment_transaction_id', $paymentId)
                ->first();

            if ($existingTransaction && $existingTransaction->isCompleted()) {
                Log::info('Duplicate payment callback received (idempotent)', [
                    'payment_id' => $paymentId,
                    'transaction_id' => $existingTransaction->id,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Payment already processed',
                        'installation_id' => $existingTransaction->installation_id,
                        'transaction_id' => $existingTransaction->id,
                    ], 200);
                }

                return $this->finalizeRedirect(true, 'Payment already processed');
            }

            // Find installation by invoice_id (which stores the PaymentID)
            $installation = ApiInstallation::where('invoice_id', $paymentId)
                ->where('user_id', $userId)
                ->where('app_id', $appId)
                ->with('app', 'user')
                ->first();

            if (!$installation) {
                Log::error('Installation not found for ARB app payment', [
                    'payment_id' => $paymentId,
                    'app_id' => $appId,
                    'user_id' => $userId,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Installation not found',
                    ], 404);
                }

                return $this->finalizeRedirect(false, 'Installation not found');
            }

            // Verify amount matches app price (prevent tampering)
            $expectedAmount = (float) $installation->app->price;
            $paidAmount = (float) $paidAmount;

            if (abs($paidAmount - $expectedAmount) > 0.01) {
                Log::warning('ARB app payment amount mismatch', [
                    'expected' => $expectedAmount,
                    'received' => $paidAmount,
                    'installation_id' => $installation->id,
                    'app_id' => $appId,
                    'user_id' => $userId,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Payment amount mismatch',
                    ], 422);
                }

                return $this->finalizeRedirect(false, 'Payment amount mismatch');
            }

            // Create or update transaction record
            $transaction = AppPaymentTransaction::updateOrCreate(
                ['payment_transaction_id' => $paymentId],
                [
                    'user_id' => $userId,
                    'installation_id' => $installation->id,
                    'app_id' => $appId,
                    'gateway' => 'arb',
                    'amount' => $paidAmount,
                    'currency' => 'SAR',
                    'status' => 'completed',
                    'gateway_response' => $paymentData,
                    'verified_at' => now(),
                    'metadata' => [
                        'payment_processed_at' => now()->toIso8601String(),
                        'recurring_id' => $paymentData['RecurringId'] ?? null,
                    ],
                ]
            );

            // Calculate subscription period end date
            $subscriptionDuration = $installation->app->subscription_duration ?? 30; // Default 30 days
            $currentPeriodEnd = now()->addDays($subscriptionDuration);

            // Update installation if not already installed (for backward compatibility with old flow)
            // New flow: installation is already installed, just mark transaction as complete
            try {
                if ($installation->status !== InstallStatus::Installed) {
                    // Only transition if not already installed (backward compatibility)
                    $this->stateMachine->transition(
                        $installation,
                        InstallStatus::Installed,
                        [
                            'recurring_id' => $paymentData['RecurringId'] ?? null,
                            'payment_subscription_id' => $paymentData['RecurringId'] ?? null,
                            'current_period_end' => $currentPeriodEnd,
                        ]
                    );
                } else {
                    // Installation already installed, update recurring_id and current_period_end
                    $updateData = [
                        'current_period_end' => $currentPeriodEnd,
                    ];
                    
                    if (isset($paymentData['RecurringId'])) {
                        $updateData['recurring_id'] = $paymentData['RecurringId'];
                        $updateData['payment_subscription_id'] = $paymentData['RecurringId'];
                    }
                    
                    $installation->update($updateData);
                }

                Log::info('App payment processed via ARB payment callback', [
                    'installation_id' => $installation->id,
                    'user_id' => $userId,
                    'app_id' => $appId,
                    'app_name' => $installation->app->name,
                    'payment_id' => $paymentId,
                    'amount' => $paidAmount,
                    'transaction_id' => $transaction->id,
                    'was_already_installed' => $installation->status === InstallStatus::Installed,
                ]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'success',
                        'message' => 'Payment processed successfully',
                        'installation_id' => $installation->id,
                        'transaction_id' => $transaction->id,
                        'app_name' => $installation->app->name,
                    ], 200);
                }

                return $this->finalizeRedirect(true, 'Payment processed successfully');

            } catch (\App\Exceptions\Installation\InvalidStatusTransitionException $e) {
                Log::error('Invalid status transition for app installation', [
                    'installation_id' => $installation->id,
                    'current_status' => $installation->status->value,
                    'error' => $e->getMessage(),
                ]);

                // Mark transaction as failed
                $transaction->markFailed($paymentData, ['error' => $e->getMessage()]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Invalid installation status transition',
                        'error' => $e->getMessage(),
                    ], 422);
                }

                return $this->finalizeRedirect(false, 'Invalid installation status transition');
            } catch (\Exception $e) {
                Log::error('Failed to process app payment via ARB payment callback', [
                    'installation_id' => $installation->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                // Mark transaction as failed
                $transaction->markFailed($paymentData, ['error' => $e->getMessage()]);

                if ($isApiRequest) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to process payment',
                        'error' => $e->getMessage(),
                    ], 500);
                }

                return $this->finalizeRedirect(false, 'Failed to process payment');
            }

        } catch (\Exception $e) {
            Log::error('Unexpected error in app payment callback handler', [
                'gateway' => $gateway,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all(),
            ]);

            $isApiRequest = $this->isApiRequest($request);

            if ($isApiRequest) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Internal server error',
                ], 500);
            }

            return $this->finalizeRedirect(false, 'Internal server error');
        }
    }

    /**
     * Get payment status for an installation
     *
     * @param Request $request
     * @param int $installationId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentStatus(Request $request, int $installationId)
    {
        $user = $request->user();

        // Verify user owns the installation
        $installation = ApiInstallation::where('id', $installationId)
            ->where('user_id', $user->id)
            ->with('app')
            ->first();

        if (!$installation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Installation not found',
            ], 404);
        }

        // Get latest transaction for this installation
        $transaction = AppPaymentTransaction::where('installation_id', $installationId)
            ->latest()
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'pending',
                'message' => 'No payment transaction found',
                'installation_status' => $installation->status->value,
            ], 200);
        }

        return response()->json([
            'status' => $transaction->status,
            'transaction_id' => $transaction->payment_transaction_id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'gateway' => $transaction->gateway,
            'created_at' => $transaction->created_at->toIso8601String(),
            'verified_at' => $transaction->verified_at?->toIso8601String(),
            'installation_status' => $installation->status->value,
        ], 200);
    }

    /**
     * Manually verify a payment
     *
     * @param Request $request
     * @param int $appId
     * @return \Illuminate\Http\JsonResponse
     */
    public function verifyPayment(Request $request, int $appId)
    {
        $validator = Validator::make($request->all(), [
            'payment_transaction_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $paymentId = $request->input('payment_transaction_id');

        // Find transaction
        $transaction = AppPaymentTransaction::where('payment_transaction_id', $paymentId)
            ->where('user_id', $user->id)
            ->where('app_id', $appId)
            ->with('installation', 'app')
            ->first();

        if (!$transaction) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment transaction not found',
            ], 404);
        }

        // If already completed, return success
        if ($transaction->isCompleted()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Payment already verified',
                'transaction_id' => $transaction->id,
                'installation_id' => $transaction->installation_id,
            ], 200);
        }

        // Verify with ARB API
        $verification = $this->verificationService->verifyPayment($paymentId);

        if ($verification['verified']) {
            // Update transaction
            $transaction->markCompleted($verification['details']);

            // Calculate subscription period end date
            $installation = $transaction->installation;
            $subscriptionDuration = $installation && $installation->app 
                ? ($installation->app->subscription_duration ?? 30) 
                : 30; // Default 30 days
            $currentPeriodEnd = now()->addDays($subscriptionDuration);

            // Update installation if not already installed (for backward compatibility)
            if ($installation && $installation->status !== InstallStatus::Installed) {
                try {
                    $this->stateMachine->transition(
                        $installation,
                        InstallStatus::Installed,
                        [
                            'recurring_id' => $verification['details']['RecurringId'] ?? null,
                            'payment_subscription_id' => $verification['details']['RecurringId'] ?? null,
                            'current_period_end' => $currentPeriodEnd,
                        ]
                    );

                    Log::info('App installation activated via manual verification', [
                        'installation_id' => $installation->id,
                        'transaction_id' => $transaction->id,
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to activate installation during verification', [
                        'installation_id' => $installation->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else if ($installation) {
                // Installation already installed, update recurring_id and current_period_end
                $updateData = [
                    'current_period_end' => $currentPeriodEnd,
                ];
                
                if (isset($verification['details']['RecurringId'])) {
                    $updateData['recurring_id'] = $verification['details']['RecurringId'];
                    $updateData['payment_subscription_id'] = $verification['details']['RecurringId'];
                }
                
                $installation->update($updateData);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment verified successfully',
                'transaction_id' => $transaction->id,
                'installation_id' => $transaction->installation_id,
            ], 200);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Payment verification failed',
            'details' => $verification['details'],
        ], 422);
    }

    /**
     * Get payment history for authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaymentHistory(Request $request)
    {
        $user = $request->user();

        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');

        $query = AppPaymentTransaction::where('user_id', $user->id)
            ->with(['app', 'installation'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        $transactions = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $transactions->items(),
            'pagination' => [
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'per_page' => $transactions->perPage(),
                'total' => $transactions->total(),
            ],
        ], 200);
    }

    /**
     * Check if request is an API request
     *
     * @param Request $request
     * @return bool
     */
    protected function isApiRequest(Request $request): bool
    {
        return $request->expectsJson() 
            || $request->wantsJson() 
            || $request->is('api/*')
            || $request->header('Accept') === 'application/json';
    }

    /**
     * Finalize redirect - return success or failed view for web requests
     * Returns views that send postMessage to parent window (for iframe/popup scenarios)
     *
     * @param bool $success
     * @param string $message
     * @return \Illuminate\Contracts\View\View
     */
    protected function finalizeRedirect(bool $success, string $message): \Illuminate\Contracts\View\View
    {
        // Get language and basic settings for the views
        $currentLang = \App\Models\Language::where('is_default', 1)->first();
        $bs = $currentLang ? $currentLang->basic_setting : \App\Models\BasicSetting::first();
        
        if (!$success) {
            return view('front.failed', [
                'bs' => $bs,
                'rtl' => $bs->rtl ?? 0
            ]);
        }

        // Return success page that notifies parent window (React/Next.js frontend)
        // The view will send postMessage("payment_success") to notify the frontend
        return view('front.success', [
            'bs' => $bs,
            'rtl' => $bs->rtl ?? 0
        ]);
    }

    /**
     * Extract and decrypt ARB payment data from request
     *
     * @param Request $request
     * @return array
     */
    protected function extractArbPaymentData(Request $request): array
    {
        try {
            $paymentMethod = PaymentGateway::where('keyword', 'arb')->first();
            if (!$paymentMethod) {
                return [];
            }

            $paydata = $paymentMethod->convertAutoData();

            if (!$request->has('trandata')) {
                return [];
            }

            $arbController = app(\App\Http\Controllers\Payment\ArbController::class);
            $decrypted = $arbController->decryption($request->input('trandata'), $paydata['resource_key']);

            if (!$decrypted) {
                return [];
            }

            $raw = urldecode($decrypted);
            $dataArr = json_decode($raw, true);

            if (!empty($dataArr) && is_array($dataArr)) {
                return $dataArr[0] ?? [];
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Failed to extract ARB payment data', [
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }
}
