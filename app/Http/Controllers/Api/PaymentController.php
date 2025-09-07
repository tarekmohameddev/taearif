<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\Package;
use App\Models\Api\ApiApp;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
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
                'period' => 'required|integer|min:1'
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
                // Validate period for monthly and yearly packages
                $period = (int) $request->period;
                
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

        $packages = Package::where('is_active', true)
            ->with(['memberships' => function ($query) {
                $query->where('expire_date', '>=', now());
            }])
            ->get();

        $plansMonthly = [];
        $plansYearly = [];

        foreach ($packages as $package) {
            $isCurrent = $package->memberships->contains('user_id', $user->id);

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
