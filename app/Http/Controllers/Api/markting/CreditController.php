<?php

namespace App\Http\Controllers\Api\markting;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Api\Marketing\PurchaseCreditPackageRequest;
use App\Http\Requests\Api\markting\GetTransactionsRequest;
use App\Models\Api\markting\UserCredit;
use App\Models\Api\markting\CreditPackage;
use App\Models\Api\markting\CreditTransaction;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditController extends BaseApiController
{
    /**
     * Get user's language preference
     */
    private function getUserLocale(Request $request): string
    {
        // Check for language in request header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage && str_contains($acceptLanguage, 'en')) {
            return 'en';
        }
        
        // Check for language in request parameters
        if ($request->has('lang') && $request->get('lang') === 'en') {
            return 'en';
        }
        
        // Check user's saved language preference if available
        $user = Auth::user();
        if ($user && isset($user->language) && $user->language === 'en') {
            return 'en';
        }
        
        // Default to Arabic
        return 'ar';
    }

    /**
     * Get localized purchase description
     */
    private function getPurchaseDescription($package, $locale = 'en'): string
    {
        $packageName = $package->getLocalizedName($locale);
        
        if ($locale === 'ar') {
            return "شراء باقة {$packageName}";
        }
        
        return "Purchase of {$packageName} package";
    }

    /**
     * Get user's current credit balance and statistics
     */
    public function getBalance(): JsonResponse
    {
        try {
            $userCredit = UserCredit::getOrCreateForUser(Auth::id());

            $balance = [
                'user_id' => Auth::id(),
                'current_balance' => $userCredit->total_credits,
                'available_credits' => $userCredit->available_credits,
                'used_credits' => $userCredit->used_credits,
                'monthly_limit' => $userCredit->monthly_limit,
                'monthly_usage_percentage' => $userCredit->monthly_usage_percentage,
                'average_cost_per_credit' => $userCredit->average_cost_per_credit,
                'reset_date' => $userCredit->reset_date,
                'is_active' => $userCredit->is_active,
            ];

            return $this->ok($balance, 'Credit balance retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve credit balance: ' . $e->getMessage());
        }
    }

    /**
     * Get available credit packages
     */
    public function getPackages(Request $request): JsonResponse
    {
        try {
            $locale = $request->get('locale', 'en');
            $packages = CreditPackage::getActivePackages();

            $formattedPackages = $packages->map(function ($package) use ($locale) {
                return [
                    'id' => $package->id,
                    'name' => $package->getLocalizedName($locale),
                    'description' => $package->getLocalizedDescription($locale),
                    'credits' => $package->credits,
                    'price' => $package->price,
                    'currency' => $package->currency,
                    'discounted_price' => $package->discounted_price,
                    'savings_amount' => $package->savings_amount,
                    'savings_percentage' => $package->getDisplaySavingsPercentage(),
                    'price_per_credit' => $package->price_per_credit,
                    'is_popular' => $package->is_popular,
                    'features' => $package->getPackageFeatures(),
                    'is_recommended' => $package->isRecommended(),
                ];
            });

            return $this->ok($formattedPackages, 'Credit packages retrieved successfully');
        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve credit packages: ' . $e->getMessage());
        }
    }

    /**
     * Purchase credit package
     */
    public function purchasePackage(PurchaseCreditPackageRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $package = CreditPackage::findOrFail($validated['package_id']);

            if (!$package->is_active) {
                return $this->fail('Package is not available', 400);
            }

            // Get user credit record
            $userCredit = UserCredit::getOrCreateForUser(Auth::id());

            DB::beginTransaction();

            try {
                // Get user's language preference
                $locale = $this->getUserLocale($request);
                
                // Create pending transaction
                $transaction = CreditTransaction::create([
                    'user_id' => Auth::id(),
                    'credit_package_id' => $package->id,
                    'transaction_type' => 'purchase',
                    'credits_amount' => $package->credits,
                    'amount_paid' => $package->discounted_price,
                    'currency' => $package->currency,
                    'payment_method' => $validated['payment_method'],
                    'status' => 'pending',
                    'reference_number' => CreditTransaction::generateReferenceNumber(),
                    'description' => $this->getPurchaseDescription($package, $locale),
                    'metadata' => [
                        'package_name' => $package->name,
                        'original_price' => $package->price,
                        'discount_applied' => $package->hasDiscount(),
                        'purchase_initiated_at' => now()->toISOString(),
                    ],
                ]);

                // Process payment based on payment method
                $paymentResult = $this->processPayment($request, $package, $transaction);

                if ($paymentResult['success']) {
                    $gatewayResponse = $paymentResult['gateway_response'];
                    
                    // Check if payment requires redirect (ARB, MyFatoorah)
                    if (isset($gatewayResponse['status']) && $gatewayResponse['status'] === 'redirect_required') {
                        // Update transaction with redirect info
                        $transaction->update([
                            'status' => 'pending',
                            'payment_transaction_id' => $paymentResult['transaction_id'],
                            'metadata' => array_merge($transaction->metadata ?? [], [
                                'payment_initiated_at' => now()->toISOString(),
                                'payment_gateway_response' => $gatewayResponse,
                                'redirect_required' => true,
                            ]),
                        ]);

                        DB::commit();

                        return $this->ok([
                            'transaction_id' => $transaction->id,
                            'reference_number' => $transaction->reference_number,
                            'payment_status' => 'redirect_required',
                            'redirect_url' => $gatewayResponse['redirect_url'],
                            'payment_token' => $gatewayResponse['payment_token'] ?? null,
                            'message' => 'Please complete payment by visiting the redirect URL',
                        ], 'Payment redirect required');
                    } else {
                        // Immediate payment completion (test mode)
                        $transaction->update([
                            'status' => 'completed',
                            'payment_transaction_id' => $paymentResult['transaction_id'],
                            'metadata' => array_merge($transaction->metadata ?? [], [
                                'payment_completed_at' => now()->toISOString(),
                                'payment_gateway_response' => $gatewayResponse,
                            ]),
                        ]);

                        // Add credits to user
                        $userCredit->addCredits($package->credits, $package->id, $this->getPurchaseDescription($package, $locale));

                        DB::commit();

                        return $this->ok([
                            'transaction_id' => $transaction->id,
                            'reference_number' => $transaction->reference_number,
                            'credits_added' => $package->credits,
                            'amount_paid' => $package->discounted_price,
                            'new_balance' => $userCredit->total_credits,
                            'payment_status' => 'completed',
                        ], 'Credit package purchased successfully');
                    }
                } else {
                    // Update transaction status to failed
                    $transaction->update([
                        'status' => 'failed',
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'payment_failed_at' => now()->toISOString(),
                            'failure_reason' => $paymentResult['error'],
                        ]),
                    ]);

                    DB::rollback();

                    return $this->fail('Payment failed: ' . $paymentResult['error'], 400);
                }

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Exception $e) {
            return $this->fail('Failed to purchase credit package: ' . $e->getMessage());
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactions(GetTransactionsRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $query = CreditTransaction::where('user_id', Auth::id())
                ->with(['creditPackage', 'createdBy']);

            // Apply filters
            if (!empty($validated['type'])) {
                $query->where('transaction_type', $validated['type']);
            }

            if (!empty($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (!empty($validated['from_date']) && !empty($validated['to_date'])) {
                $query->dateRange($validated['from_date'], $validated['to_date']);
            }

            $perPage = $validated['per_page'] ?? 20;
            $transactions = $query->orderBy('created_at', 'desc')->paginate($perPage);

            $formattedTransactions = $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'reference_number' => $transaction->reference_number,
                    'transaction_type' => $transaction->transaction_type,
                    'transaction_type_display' => $transaction->transaction_type_display,
                    'credits_amount' => $transaction->credits_amount,
                    'absolute_credits' => $transaction->absolute_credits,
                    'amount_paid' => $transaction->amount_paid,
                    'currency' => $transaction->currency,
                    'payment_method' => $transaction->payment_method,
                    'status' => $transaction->status,
                    'status_display' => $transaction->status_display,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at,
                    'package' => $transaction->creditPackage ? [
                        'id' => $transaction->creditPackage->id,
                        'name' => $transaction->creditPackage->name,
                        'credits' => $transaction->creditPackage->credits,
                    ] : null,
                    'is_positive' => $transaction->isPositive(),
                    'is_negative' => $transaction->isNegative(),
                ];
            });

            return $this->ok([
                'transactions' => $formattedTransactions,
                'pagination' => [
                    'current_page' => $transactions->currentPage(),
                    'last_page' => $transactions->lastPage(),
                    'per_page' => $transactions->perPage(),
                    'total' => $transactions->total(),
                ],
            ], 'Transaction history retrieved successfully');

        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve transaction history: ' . $e->getMessage());
        }
    }

    /**
     * Get usage analytics
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

            $startDate = $request->get('from_date', now()->startOfMonth());
            $endDate = $request->get('to_date', now()->endOfMonth());

            // Get user credit record
            $userCredit = UserCredit::getOrCreateForUser(Auth::id());

            // Get transaction statistics
            $statistics = CreditTransaction::getUserStatistics(Auth::id(), $startDate, $endDate);

            // Get usage by number (if needed for marketing channels)
            $usageByNumber = $this->getUsageByNumber($startDate, $endDate);

            $analytics = [
                'period' => [
                    'from_date' => $startDate,
                    'to_date' => $endDate,
                ],
                'current_balance' => [
                    'total_credits' => $userCredit->total_credits,
                    'available_credits' => $userCredit->available_credits,
                    'used_credits' => $userCredit->used_credits,
                    'monthly_limit' => $userCredit->monthly_limit,
                    'monthly_usage_percentage' => $userCredit->monthly_usage_percentage,
                ],
                'statistics' => $statistics,
                'usage_by_number' => $usageByNumber,
                'message_type_costs' => UserCredit::getMessageTypeCosts(),
                'generated_at' => now()->toISOString(),
            ];

            return $this->ok($analytics, 'Usage analytics retrieved successfully');

        } catch (\Exception $e) {
            return $this->fail('Failed to retrieve usage analytics: ' . $e->getMessage());
        }
    }

    /**
     * Process payment using existing payment system
     */
    private function processPayment(Request $request, CreditPackage $package, CreditTransaction $transaction)
    {
        try {
            $paymentMethod = $request->payment_method;

            switch ($paymentMethod) {
                case 'arb':
                    return $this->processArbPayment($request, $package, $transaction);
                
                case 'myfatoorah':
                    return $this->processMyFatoorahPayment($request, $package, $transaction);
                
                case 'test':
                    // For testing purposes only
                    return [
                        'success' => true,
                        'transaction_id' => 'TEST_' . time() . '_' . rand(1000, 9999),
                        'gateway_response' => [
                            'status' => 'success',
                            'method' => 'test',
                            'amount' => $package->discounted_price,
                        ],
                    ];
                
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported payment method. Supported methods: arb, myfatoorah, test',
                    ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Process ARB payment
     */
    private function processArbPayment(Request $request, CreditPackage $package, CreditTransaction $transaction)
    {
        try {
            $arbController = new \App\Http\Controllers\Payment\ArbController();
            
            // Create a mock request for ARB payment process
            $arbRequest = new Request([
                'amount' => $package->discounted_price,
                'title' => "Credit Package: {$package->name}",
                'user_id' => Auth::id(),
                'context' => 'CREDIT_PURCHASE',
                'app_id' => 0,
                'transaction_id' => $transaction->id,
                'package_id' => $package->id,
            ]);

            // Generate success and cancel URLs for credit purchase
            $successUrl = route('api.credits.payment.success', [
                'transaction_id' => $transaction->id,
                'gateway' => 'arb'
            ]);
            $cancelUrl = route('api.credits.payment.cancel', [
                'transaction_id' => $transaction->id,
                'gateway' => 'arb'
            ]);

            // Call ARB payment process
            $result = $arbController->paymentProcess(
                $arbRequest,
                $package->discounted_price,
                $successUrl,
                $cancelUrl,
                "Credit Package: {$package->name}",
                Auth::id(),
                'CREDIT_PURCHASE',
                0
            );

            if (isset($result['redirect_url'])) {
                return [
                    'success' => true,
                    'transaction_id' => $transaction->reference_number,
                    'gateway_response' => [
                        'status' => 'redirect_required',
                        'method' => 'arb',
                        'amount' => $package->discounted_price,
                        'redirect_url' => $result['redirect_url'],
                        'payment_token' => $result['payment_token'] ?? null,
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to initialize ARB payment',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'ARB payment error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Process MyFatoorah payment
     */
    private function processMyFatoorahPayment(Request $request, CreditPackage $package, CreditTransaction $transaction)
    {
        try {
            $myfatoorahController = new \App\Http\Controllers\Payment\MyFatoorahController();
            
            // Create a mock request for MyFatoorah payment process
            $mfRequest = new Request([
                'amount' => $package->discounted_price,
                'title' => "Credit Package: {$package->name}",
                'user_id' => Auth::id(),
                'transaction_id' => $transaction->id,
                'package_id' => $package->id,
            ]);

            // Get basic extended settings for currency
            $currentLang = session()->has('lang') ? 
                (\App\Models\Language::where('code', session()->get('lang'))->first()) :
                (\App\Models\Language::where('is_default', 1)->first());
            $be = $currentLang->basic_extended;

            // Generate success and cancel URLs for credit purchase
            $successUrl = route('api.credits.payment.success', [
                'transaction_id' => $transaction->id,
                'gateway' => 'myfatoorah'
            ]);
            $cancelUrl = route('api.credits.payment.cancel', [
                'transaction_id' => $transaction->id,
                'gateway' => 'myfatoorah'
            ]);

            // Call MyFatoorah payment process
            $result = $myfatoorahController->paymentProcess(
                $mfRequest,
                $package->discounted_price,
                $successUrl,
                $cancelUrl,
                "Credit Package: {$package->name}",
                $be
            );

            if (isset($result['redirect_url'])) {
                return [
                    'success' => true,
                    'transaction_id' => $transaction->reference_number,
                    'gateway_response' => [
                        'status' => 'redirect_required',
                        'method' => 'myfatoorah',
                        'amount' => $package->discounted_price,
                        'redirect_url' => $result['redirect_url'],
                        'payment_token' => $result['payment_token'] ?? null,
                    ],
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to initialize MyFatoorah payment',
                ];
            }

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'MyFatoorah payment error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Handle payment success callback
     */
    public function paymentSuccess(Request $request, $transactionId, $gateway)
    {
        try {
            $transaction = CreditTransaction::findOrFail($transactionId);

            if ($transaction->status === 'completed') {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Payment already processed',
                    'transaction_id' => $transaction->reference_number,
                ]);
            }

            // If gateway indicates failure/cancel (e.g. ARB sends user to same URL with result param), treat as cancel
            if ($this->requestIndicatesPaymentFailedOrCancelled($request)) {
                $transaction->update([
                    'status' => 'failed',
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'payment_cancelled_at' => now()->toISOString(),
                        'gateway' => $gateway,
                        'cancellation_reason' => 'Gateway reported failure or user cancelled',
                        'callback_data' => $request->all(),
                    ]),
                ]);
                return response()->json([
                    'status' => 'cancelled',
                    'message' => 'Payment was cancelled',
                    'transaction_id' => $transaction->reference_number,
                ]);
            }

            // Update transaction status to completed
            $transaction->update([
                'status' => 'completed',
                'payment_transaction_id' => $request->get('payment_id') ?? $request->get('transaction_id'),
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_completed_at' => now()->toISOString(),
                    'gateway' => $gateway,
                    'callback_data' => $request->all(),
                ]),
            ]);

            // Add credits to user
            $userCredit = UserCredit::getOrCreateForUser($transaction->user_id);
            $userCredit->addCredits(
                $transaction->credits_amount,
                $transaction->credit_package_id,
                "Credit purchase via {$gateway}"
            );

            return response()->json([
                'status' => 'success',
                'message' => 'Payment successful and credits added',
                'transaction_id' => $transaction->reference_number,
                'credits_added' => $transaction->credits_amount,
                'new_balance' => $userCredit->fresh()->total_credits,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment success: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check if request contains gateway params indicating payment failed or was cancelled
     */
    private function requestIndicatesPaymentFailedOrCancelled(Request $request): bool
    {
        $result = strtoupper((string) ($request->input('result') ?? $request->input('payment_result') ?? $request->input('status') ?? ''));
        $failedValues = ['NOT CAPTURED', 'NOT_CAPTURED', 'CANCELLED', 'CANCELED', 'FAILED', 'ERROR', 'DECLINED'];
        if (in_array($result, $failedValues, true)) {
            return true;
        }
        if ($request->has('error') || $request->has('payment_error')) {
            return true;
        }
        return false;
    }

    /**
     * Handle payment cancel callback
     */
    public function paymentCancel(Request $request, $transactionId, $gateway)
    {
        try {
            $transaction = CreditTransaction::findOrFail($transactionId);
            
            if ($transaction->status === 'completed') {
                return response()->json([
                    'status' => 'info',
                    'message' => 'Payment was already completed',
                    'transaction_id' => $transaction->reference_number,
                ]);
            }

            // Update transaction status to failed
            $transaction->update([
                'status' => 'failed',
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_cancelled_at' => now()->toISOString(),
                    'gateway' => $gateway,
                    'cancellation_reason' => 'User cancelled payment',
                    'callback_data' => $request->all(),
                ]),
            ]);

            return response()->json([
                'status' => 'cancelled',
                'message' => 'Payment was cancelled',
                'transaction_id' => $transaction->reference_number,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment cancellation: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get usage by number (for marketing channels)
     */
    private function getUsageByNumber($startDate, $endDate)
    {
        // This would integrate with your marketing channels to show usage per number
        // For now, returning sample data
        return [
            [
                'channel_id' => 1,
                'channel_name' => 'Company Main Number',
                'channel_type' => 'whatsapp',
                'number' => '+966501234567',
                'credits_used' => 250,
                'messages_sent' => 250,
                'cost_per_message' => 1,
            ],
            [
                'channel_id' => 2,
                'channel_name' => 'Customer Service Number',
                'channel_type' => 'whatsapp',
                'number' => '+966559876543',
                'credits_used' => 150,
                'messages_sent' => 150,
                'cost_per_message' => 1,
            ],
        ];
    }

    /**
     * Get usage statistics for all marketing channels
     */
    public static function getUsage($userId)
    {
        try {
            // Get all marketing channels for the user
            $channels = \App\Models\Api\markting\MarketingChannel::where('user_id', $userId)
                ->where('is_connected', true)
                ->get();

            // Get pricing information for each channel type
            $pricing = \App\Models\Api\markting\MarketingChannelPricing::where('is_active', true)
                ->get()
                ->keyBy('channel_type');

            $usage = $channels->map(function ($channel) use ($pricing) {
                // Get pricing info for this channel type
                $channelPricing = $pricing->get($channel->type);
                $creditsPerMessage = $channelPricing?->credits_per_message ?? 1;
                $pricePerCredit = $channelPricing?->price_per_credit ?? 0.05;
                $effectivePricePerMessage = $channelPricing?->effective_price_per_message ?? 0.05;
                
                // Calculate credits used based on sent messages
                $creditsUsed = $channel->sent_messages_count * $creditsPerMessage;
                
                // Calculate total cost in currency
                $totalCostCurrency = $channel->sent_messages_count * $effectivePricePerMessage;

                return (object) [
                    'channel_id' => $channel->id,
                    'channel_name' => $channel->name,
                    'channel_type' => $channel->type,
                    'credits_used' => $creditsUsed,
                    'messages_sent' => $channel->sent_messages_count,
                    'messages_received' => $channel->received_messages_count,
                    'cost_per_message_credits' => $creditsPerMessage,
                    'cost_per_message_currency' => $effectivePricePerMessage,
                    'total_cost_credits' => $creditsUsed,
                    'total_cost_currency' => round($totalCostCurrency, 4),
                ];
            });

            return $usage;

        } catch (\Exception $e) {
            return collect([]);
        }
    }

    /**
     * Use credits (called by marketing channels when sending messages)
     */
    public static function useCredits($userId, $credits, $description = null, $metadata = [])
    {
        try {
            $userCredit = UserCredit::getOrCreateForUser($userId);

            if (!$userCredit->hasEnoughCredits($credits)) {
                return [
                    'success' => false,
                    'error' => 'Insufficient credits',
                    'available_credits' => $userCredit->available_credits,
                    'required_credits' => $credits,
                ];
            }

            if (!$userCredit->isWithinMonthlyLimit($credits)) {
                return [
                    'success' => false,
                    'error' => 'Monthly credit limit exceeded',
                    'used_credits' => $userCredit->used_credits,
                    'monthly_limit' => $userCredit->monthly_limit,
                ];
            }

            $success = $userCredit->useCredits($credits, $description);

            if ($success) {
                return [
                    'success' => true,
                    'credits_used' => $credits,
                    'remaining_credits' => $userCredit->available_credits,
                    'monthly_usage' => $userCredit->used_credits,
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to use credits',
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
