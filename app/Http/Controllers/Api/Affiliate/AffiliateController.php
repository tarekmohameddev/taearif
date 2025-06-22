<?php

namespace App\Http\Controllers\Api\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\ApiAffiliateUser;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Support\Facades\Auth;


class AffiliateController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'fullname' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_account_number' => 'required|string|max:30',
            'iban' => 'required|string|max:34',
        ]);

        $user = $request->user();

        // Check if the user is already registered as an affiliate
        if ($user->affiliateUser) {
            return response()->json([
                'status' => 'info',
                'message' => 'You have already submitted an affiliate registration.',
                'data' => [
                    'request_status' => $user->affiliateUser->request_status
                ]
            ], 200);
        }

        // Create a new affiliate user record
        $affiliate = ApiAffiliateUser::create([
            'user_id' => $user->id,
            'fullname' => $request->fullname,
            'bank_name' => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'iban' => $request->iban,
            'request_status' => 'pending'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Affiliate registration submitted successfully.',
            'data' => $affiliate
        ], 201);
    }

    // \Log::info($user);

    public function index()
    {
        $user = Auth::user();

        $affiliate = ApiAffiliateUser::where('user_id', $user->id)->first();

        if (!$affiliate) {
            return response()->json([
                'status' => 'not_registered',
                'message' => 'Affiliate user not registered.',
            ], 404);
        }

        // Get all users registered by current user's referral code using relationship
        $referredUsers = $user->referredUsers;

        // Get only subscribed users from referrals using the relationship
        $subscribedReferrals = $user->subscribedReferrals()->get();

        // Calculate total earnings
        $defaultCommissionRate = 0.15; // 15% default commission
        $commissionRate = $affiliate->commission_percentage ?? $defaultCommissionRate;

        // Count of subscribed referrals
        $subscribedCount = $subscribedReferrals->count();

        // Calculate earnings based on subscribed referrals
        $totalEarnings = 0;
        $thisMonthEarnings = 0;

        foreach ($subscribedReferrals as $referredUser) {
            // Use subscription_amount from users table
            $subscriptionAmount = $referredUser->subscription_amount;

            if ($subscriptionAmount > 0) {
                $commission = $subscriptionAmount * $commissionRate;
                $totalEarnings += $commission;
            }

            // Get memberships that started this month for this month earnings
            $thisMonthMemberships = $referredUser->memberships()
                ->where('status', 1)
                ->whereYear('start_date', now()->year)
                ->whereMonth('start_date', now()->month)
                ->sum('price');

            if ($thisMonthMemberships > 0) {
                $thisMonthEarnings += ($thisMonthMemberships * $commissionRate);
            }
        }

        // Calculate pending amount
        // Pending = Total Earned - Already Withdrawn
        $pendingAmount = $totalEarnings - $affiliate->withdrawn_amount;

        // Update affiliate's earnings and pending amount
        $affiliate->total_earned = $totalEarnings;
        $affiliate->pending_amount = $pendingAmount;
        $affiliate->save();

        $messages = [
            'pending'  => 'Your affiliate request is still under review.',
            'approved' => 'Your affiliate request has been approved.',
            'rejected' => 'Your affiliate request was rejected.',
        ];

        return response()->json([
            'status' => $affiliate->request_status,
            'message' => $messages[$affiliate->request_status] ?? 'not_found',
            'affiliate_data' => [
                'user_referral_code' => $user->referral_code,
                'total_referrals' => $referredUsers->count(),
                'subscribed_referrals' => $subscribedCount,
                'commission_rate' => $commissionRate,
                'total_earned' => number_format($totalEarnings, 2),
                'this_month_earned' => number_format($thisMonthEarnings, 2),
                'total_commission' => $affiliate->total_commission,
                'pending_amount' => number_format($pendingAmount, 2),
                'withdrawn_amount' => number_format($affiliate->withdrawn_amount, 2),
            ],
            'referral_details' => $subscribedReferrals->map(function($referredUser) use ($commissionRate) {
                $latestMembership = $referredUser->memberships()
                                               ->where('status', 1)
                                               ->orderBy('id', 'DESC')
                                               ->first();

                return [
                    'user_id' => $referredUser->id,
                    'name' => $referredUser->first_name . ' ' . $referredUser->last_name,
                    'email' => $referredUser->email,
                    'username' => $referredUser->username,
                    'subscribed' => $referredUser->subscribed == 1,
                    'referred_by' => $referredUser->referred_by,
                    'registered_at' => $referredUser->created_at->format('Y-m-d'),
                    'subscription_amount' => number_format($referredUser->subscription_amount, 2),
                    'commission_earned' => number_format($referredUser->subscription_amount * $commissionRate, 2),
                ];
            })
        ]);
    }

}
