<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Package;
use App\Models\Membership;
use App\Models\Api\ApiApp;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Payment\ArbController;

class PaymentController extends Controller
{

    public function checkoutApp(Request $request)
    {
        $request->validate(['app_id' => 'required|exists:api_apps,id']);
        $user = $request->user();
        $app  = \App\Models\Api\ApiApp::findOrFail($request->app_id);

        $arb  = app(\App\Http\Controllers\Payment\ArbController::class);
        $resp = $arb->paymentProcessForApp($user, $app);

        if ($resp === 'error') {
            return response()->json(['status'=>'error','payment_url'=>null], 422);
        }

        return response()->json([
            'status'        => 'success',
            'payment_url'   => $resp['redirect_url'],
            'payment_token' => $resp['payment_token'] ?? null,
        ]);
    }

    public function checkoutCredits(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'credits' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:arb,myfatoorah,test'
        ]);

        $user = $request->user();
        $amount = (float) $request->amount;
        $credits = (int) $request->credits;
        $paymentMethod = $request->payment_method;

        try {
            // Create a temporary transaction record for tracking
            $transaction = \App\Models\Api\markting\CreditTransaction::create([
                'user_id' => $user->id,
                'credit_package_id' => null, // Direct amount purchase
                'transaction_type' => 'purchase',
                'credits_amount' => $credits,
                'amount_paid' => $amount,
                'currency' => 'SAR',
                'payment_method' => $paymentMethod,
                'status' => 'pending',
                'reference_number' => \App\Models\Api\markting\CreditTransaction::generateReferenceNumber(),
                'description' => "Direct credit purchase: {$credits} credits for {$amount} SAR",
                'metadata' => [
                    'purchase_type' => 'direct_amount',
                    'purchase_initiated_at' => now()->toISOString(),
                ],
            ]);

            if ($paymentMethod === 'test') {
                // Test mode - immediately complete the transaction
                $transaction->update([
                    'status' => 'completed',
                    'payment_transaction_id' => 'TEST_' . time() . '_' . rand(1000, 9999),
                    'metadata' => array_merge($transaction->metadata ?? [], [
                        'payment_completed_at' => now()->toISOString(),
                        'test_mode' => true,
                    ]),
                ]);

                // Add credits to user
                $userCredit = \App\Models\Api\markting\UserCredit::getOrCreateForUser($user->id);
                $userCredit->addCredits($credits, null, "Test credit purchase: {$credits} credits");

                return response()->json([
                    'status' => 'success',
                    'payment_url' => null,
                    'payment_token' => null,
                    'transaction_id' => $transaction->reference_number,
                    'credits_added' => $credits,
                    'amount_paid' => $amount,
                    'new_balance' => $userCredit->fresh()->total_credits,
                    'message' => 'Test payment completed successfully'
                ]);
            }

            // Process payment with ARB or MyFatoorah
            if ($paymentMethod === 'arb') {
                $arb = app(\App\Http\Controllers\Payment\ArbController::class);
                $resp = $arb->paymentProcessForCredits($user, $amount, $credits, 'arb');
            } elseif ($paymentMethod === 'myfatoorah') {
                $myfatoorah = app(\App\Http\Controllers\Payment\MyFatoorahController::class);
                $resp = $myfatoorah->paymentProcessForCredits($user, $amount, $credits, 'myfatoorah');
            }

            if ($resp === 'error' || !isset($resp['redirect_url'])) {
                $transaction->update(['status' => 'failed']);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment initialization failed',
                    'payment_url' => null
                ], 422);
            }

            // Update transaction with payment details
            $transaction->update([
                'payment_transaction_id' => $resp['payment_token'] ?? null,
                'metadata' => array_merge($transaction->metadata ?? [], [
                    'payment_initiated_at' => now()->toISOString(),
                    'payment_gateway_response' => $resp,
                ]),
            ]);

            return response()->json([
                'status' => 'success',
                'payment_url' => $resp['redirect_url'],
                'payment_token' => $resp['payment_token'] ?? null,
                'transaction_id' => $transaction->reference_number,
                'amount' => $amount,
                'credits' => $credits,
                'payment_method' => $paymentMethod
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ], 500);
        }
    }


    public function checkout(Request $request)
    {
        try {
            // Get user from token instead of auth() helper
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized access'
                ], 401);
            }

            // Validate required fields
            $request->validate([
                'package_id' => 'required|exists:packages,id',
                'period' => 'nullable|integer|min:1'
            ]);

            $package = Package::find($request->package_id);
            
            if (!$package) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Package not found'
                ], 404);
            }

            // Handle lifetime packages - ignore period
            if ($package->term === 'lifetime') {
                $amount = $package->price;
                $period = 1; // For display purposes
            } else {
                //  default to 1 if not provided period from request
                $period = (int) ($request->period ?? 1);
                
                if ($period < 1) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Period must be at least 1'
                    ], 422);
                }

                // Calculate total amount based on package term and period
                $amount = $package->price * $period;
            }

            $data = $request->all();
            $data['status'] = "1";
            $data['receipt_name'] = null;
            $data['email'] = $user->email;
            $data['period'] = $period; // Pass period information for membership creation

            $title = "You are extending your membership";
            $description = "Congratulation you are going to join our membership.Please make a payment for confirming your membership now!";

            $title = $package->title;
            $description = $package->description;

            // Change success and cancel URLs to API endpoints
            $success_url = route('membership.arb.success');
            $cancel_url = route('membership.arb.cancel');

            $arbPayment = new ArbController();
            $result = $arbPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $user->id);

            if ($result == 'error') {
                return response()->json([
                    'status' => 'error',
                    'payment_url' => null,
                    'payment_token' => null
                ], 422);
            }

            return response()->json([
                'status' => 'success',
                'payment_url' => $result['redirect_url'],
                'payment_token' => $result['payment_token'] ?? null,
                'total_amount' => $amount,
                'package_price' => $package->price,
                'period' => $period,
                'package_term' => $package->term
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function index()
    {
        $user = Auth::user();

        $packages = Cache::remember('payment_active_packages', 3600, function () {
            return Package::where('is_active', true)->get();
        });
        $packageIds = $packages->pluck('id')->all();
        $userActivePackageIds = Membership::where('user_id', $user->id)
            ->whereIn('package_id', $packageIds)
            ->where('expire_date', '>=', now())
            ->pluck('package_id')
            ->all();

        $plansMonthly = [];
        $plansYearly = [];

        foreach ($packages as $package) {
            $isCurrent = in_array($package->id, $userActivePackageIds);

            $planData = [
                'id' => $package->id,
                'name' => $package->title,
                'price' => '' . number_format($package->price, 2),
                'billing' => match ($package->term) {
                    'monthly' => 'شهريًا',
                    'yearly' => 'سنويًا',
                    'trial', 'is_trial' => 'تجريبي',
                    default => '',
                },
                'features' => is_array($package->new_features)
                    ? $package->new_features
                    : json_decode($package->new_features, true, JSON_UNESCAPED_UNICODE) ?? [],
                'is_trial' => (bool) $package->is_trial,
                'cta' => $isCurrent ? 'الخطة الحالية' :  'الترقية',
            ];

            // Group by term
            if ($package->term === 'monthly') {
                $plansMonthly[] = $planData;
            } elseif ($package->term === 'yearly') {
                $plansYearly[] = $planData;
            }
        }

        return response()->json([
            'plans' => [
                'plans_monthly' => $plansMonthly,
                'plans_yearly' => $plansYearly,
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
