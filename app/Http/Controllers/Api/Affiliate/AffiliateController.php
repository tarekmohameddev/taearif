<?php

namespace App\Http\Controllers\Api\Affiliate;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Api\ApiAffiliateUser;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AffiliateController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'fullname'             => 'required|string|max:255',
            'bank_name'            => 'required|string|max:255',
            'bank_account_number'  => 'required|string|max:30',
            'iban'                 => 'required|string|max:34',
        ]);

        $user = $request->user();

        if ($user->affiliateUser) {
            return response()->json([
                'status'  => 'info',
                'message' => 'You have already submitted an affiliate registration.',
                'data'    => [
                    'request_status' => $user->affiliateUser->request_status
                ],
            ], 200);
        }

        $affiliate = ApiAffiliateUser::create([
            'user_id'           => $user->id,
            'fullname'          => $request->fullname,
            'bank_name'         => $request->bank_name,
            'bank_account_number' => $request->bank_account_number,
            'iban'              => $request->iban,
            'request_status'    => 'pending',
        ]);

        return response()->json([
            'status'  => 'success',
            'message' => 'Affiliate registration submitted successfully.',
            'data'    => $affiliate,
        ], 201);
    }

    public function index()
    {
        $user      = Auth::user();
        $affiliate = ApiAffiliateUser::where('user_id', $user->id)->first();

        if (! $affiliate) {
            return response()->json([
                'status'  => 'not_registered',
                'message' => 'Affiliate user not registered.',
            ], 404);
        }

        //  Metrics
        $pendingSum     = $affiliate->transactions()->where('type', 'pending')->sum('amount');
        $collectedSum   = $affiliate->transactions()->where('type', 'collected')->sum('amount');
        $totalCommissions = $pendingSum + $collectedSum;

        $startMonthly   = Carbon::now()->startOfMonth();
        $endMonthly     = Carbon::now()->endOfMonth();
        $monthly        = $affiliate->transactions()
                             ->where('type', 'collected')
                             ->whereBetween('created_at', [$startMonthly, $endMonthly])
                             ->sum('amount');

        $history        = $affiliate->transactions()
                             ->orderByDesc('created_at')
                             ->get(['id','amount','image','type','note','created_at']);

        $pendingCount   = $affiliate->transactions()->where('type', 'pending')->count();
        $collectedCount = $affiliate->transactions()->where('type', 'collected')->count();

        $totalReferrals   = $affiliate->referrals()->count();
        $paidSubscribers  = $affiliate->referrals()->where('subscribed', true)->count();

        // Build referrals overview
        $referrals = $affiliate
            ->referrals()
            ->select('id','first_name','last_name','email','created_at')
            ->get()
            ->map(function($u) use ($affiliate) {
                $pendingByReferral   = $affiliate->transactions()
                    ->where('referral_user_id', $u->id)
                    ->where('type', 'pending')
                    ->sum('amount');
                $collectedByReferral = $affiliate->transactions()
                    ->where('referral_user_id', $u->id)
                    ->where('type', 'collected')
                    ->sum('amount');

                if ($pendingByReferral > 0) {
                    $status = 'pending';
                } elseif ($collectedByReferral > 0) {
                    $status = 'collected';
                } else {
                    $status = 'not_paid';
                }

                return [
                    'id'                   => $u->id,
                    'name'                 => "{$u->first_name} {$u->last_name}",
                    'email'                => $u->email,
                    'joined_at'            => $u->created_at->toDateTimeString(),
                    'pending_commission'   => number_format($pendingByReferral, 2),
                    'collected_commission' => number_format($collectedByReferral, 2),
                    'status'               => $status,
                ];
            });

        //  Referral‐details for subscribed users
        $subscribedReferrals = $affiliate->referrals()->where('subscribed', true)->get();
        $commissionRate       = $affiliate->commission_percentage ?? 0.15;

        return response()->json([
            'success' => true,
            'data'    => [
                'referral_code'            => $user->referral_code,
                'total_referrals'          => $totalReferrals,
                'paid_subscribers_count'   => $paidSubscribers,
                'pending_payments_count'   => $pendingCount,
                'collected_payments_count' => $collectedCount,
                'total_commissions'        => number_format($totalCommissions, 2),
                'pending_amount'           => number_format($pendingSum, 2),
                'available_amount'         => number_format($collectedSum, 2),
                'end_of_month_payment'     => number_format($monthly, 2),
                'referrals'                => $referrals,
                'payment_history'          => $history->map(fn($t) => [
                    'id'        => $t->id,
                    'amount'    => number_format($t->amount, 2),
                    'image_url' => $t->image ? asset($t->image) : null,
                    'type'      => $t->type,
                    'note'      => $t->note,
                    'date'      => $t->created_at->toDateTimeString(),
                ]),
            ],
            'referral_details' => $subscribedReferrals->map(function($referred) use ($commissionRate) {
                $latestMembership = $referred->memberships()
                    ->where('status', 1)
                    ->orderByDesc('id')
                    ->first();

                return [
                    'user_id'             => $referred->id,
                    'name'                => "{$referred->first_name} {$referred->last_name}",
                    'email'               => $referred->email,
                    'username'            => $referred->username,
                    'subscribed'          => (bool) $referred->subscribed,
                    'referred_by'         => $referred->referred_by,
                    'registered_at'       => $referred->created_at->toDateString(),
                    'subscription_amount' => number_format($referred->subscription_amount, 2),
                    'commission_earned'   => number_format($referred->subscription_amount * $commissionRate, 2),
                ];
            }),
        ], 200);
    }
}
