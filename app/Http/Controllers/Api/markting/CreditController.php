<?php

namespace App\Http\Controllers\Api\markting;

use App\Http\Controllers\Api\BaseApiController;
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
    public function purchasePackage(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'package_id' => 'required|exists:credit_packages,id',
                'payment_method' => 'required|string',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

            $package = CreditPackage::findOrFail($request->package_id);

            if (!$package->is_active) {
                return $this->fail('Package is not available', 400);
            }

            // Get user credit record
            $userCredit = UserCredit::getOrCreateForUser(Auth::id());

            DB::beginTransaction();

            try {
                // Create pending transaction
                $transaction = CreditTransaction::create([
                    'user_id' => Auth::id(),
                    'credit_package_id' => $package->id,
                    'transaction_type' => 'purchase',
                    'credits_amount' => $package->credits,
                    'amount_paid' => $package->discounted_price,
                    'currency' => $package->currency,
                    'payment_method' => $request->payment_method,
                    'status' => 'pending',
                    'reference_number' => CreditTransaction::generateReferenceNumber(),
                    'description' => "Purchase of {$package->name} package",
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
                    // Update transaction status
                    $transaction->update([
                        'status' => 'completed',
                        'payment_transaction_id' => $paymentResult['transaction_id'],
                        'metadata' => array_merge($transaction->metadata ?? [], [
                            'payment_completed_at' => now()->toISOString(),
                            'payment_gateway_response' => $paymentResult['gateway_response'],
                        ]),
                    ]);

                    // Add credits to user
                    $userCredit->addCredits($package->credits, $package->id, "Purchase of {$package->name} package");

                    DB::commit();

                    return $this->ok([
                        'transaction_id' => $transaction->id,
                        'reference_number' => $transaction->reference_number,
                        'credits_added' => $package->credits,
                        'amount_paid' => $package->discounted_price,
                        'new_balance' => $userCredit->total_credits,
                        'payment_status' => 'completed',
                    ], 'Credit package purchased successfully');
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
    public function getTransactions(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'nullable|in:purchase,usage,refund,admin_add,admin_remove',
                'status' => 'nullable|in:pending,completed,failed,refunded',
                'from_date' => 'nullable|date',
                'to_date' => 'nullable|date|after_or_equal:from_date',
                'per_page' => 'nullable|integer|min:1|max:100',
            ]);

            if ($validator->fails()) {
                return $this->fail('Validation failed', 422, $validator->errors());
            }

            $query = CreditTransaction::where('user_id', Auth::id())
                ->with(['creditPackage', 'createdBy']);

            // Apply filters
            if ($request->type) {
                $query->where('transaction_type', $request->type);
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->from_date && $request->to_date) {
                $query->dateRange($request->from_date, $request->to_date);
            }

            $perPage = $request->get('per_page', 20);
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
            // This integrates with your existing payment system
            // You can modify this based on your specific payment gateway implementation

            $paymentMethod = $request->payment_method;

            // For now, we'll simulate a successful payment
            // In production, you would integrate with your existing payment controllers
            // like StripeController, PayPalController, etc.

            return [
                'success' => true,
                'transaction_id' => 'PAY_' . time() . '_' . rand(1000, 9999),
                'gateway_response' => [
                    'status' => 'success',
                    'method' => $paymentMethod,
                    'amount' => $package->discounted_price,
                ],
            ];

            // Example integration with existing payment system:
            /*
            switch ($paymentMethod) {
                case 'stripe':
                    $stripeController = new \App\Http\Controllers\Payment\StripeController();
                    return $stripeController->creditPurchaseProcess($request, $package, $transaction);
                
                case 'paypal':
                    $paypalController = new \App\Http\Controllers\Payment\PayPalController();
                    return $paypalController->creditPurchaseProcess($request, $package, $transaction);
                
                // Add other payment methods as needed
                
                default:
                    return [
                        'success' => false,
                        'error' => 'Unsupported payment method',
                    ];
            }
            */

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
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
