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

    public function index()
    {
        $user = Auth::user();
        $affiliate = ApiAffiliateUser::where('user_id', $user->id)->first();

        if (! $affiliate) {
            return response()->json([
                'status'  => 'not_registered',
                'message' => 'Affiliate user not registered.',
            ], 404);
        }

        //sum of all raw commissions ever generated
        $pendingSum   = $affiliate->transactions()->where('type','pending')->sum('amount');
        //sum of all “collected” amounts
        $collectedSum = $affiliate->transactions()->where('type','collected')->sum('amount');

        //total commissions = pending + collected
        $totalCommissions = $pendingSum + $collectedSum;
        //end-of-the-month payment
        $start   = Carbon::now()->startOfMonth();
        $end     = Carbon::now()->endOfMonth();
        $monthly = $affiliate->transactions()
                             ->where('type','collected')
                             ->whereBetween('created_at', [$start, $end])
                             ->sum('amount');

        // history of collected payments
        $history = $affiliate->transactions()
                             ->where('type','collected')
                             ->orderByDesc('created_at')
                             ->get(['id','amount','image','type','note','created_at']);

        // count of transactions
        $pendingCount   = $affiliate->transactions()->where('type','pending')->count();     // دفعات معلقه
        $collectedCount = $affiliate->transactions()->where('type','collected')->count();   // عملاء مدفوعين

        // total referrals
        $totalReferrals = $affiliate->referrals()->count(); // اجمالي المحالين

        // Get subscribed users related to this affiliate
        $Paid_Subscribers = $affiliate->referrals()->where('subscribed', true)->count(); // عملاء مدفوعين

        // build referrals array
        $referrals = $affiliate->referrals()
        ->select('id','first_name','last_name','email','created_at')
        ->get()
        ->map(function($u) use ($affiliate) {
            // total pending for this referral
            $pendingByReferral   = $affiliate
                ->transactions()
                ->where('referral_user_id', $u->id)
                ->where('type', 'pending')
                ->sum('amount');

            // total collected for this referral
            $collectedByReferral = $affiliate
                ->transactions()
                ->where('referral_user_id', $u->id)
                ->where('type', 'collected')
                ->sum('amount');

            // derive a single status
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
                'status'               => $status,  // “pending”, “collected” or “not_paid”
            ];
        });


        return response()->json([
            'success' => true,
            'data'    => [
                'referral_code'            => $user->referral_code,
                'total_referrals'          => $totalReferrals,       // إجمالي المحالين
                "Paid_Subscribers"          => $Paid_Subscribers,       // عملاء مدفوعين
                'pending_payments_count'   => $pendingCount,         // دفعات معلقه
                'collected_payments_count' => $collectedCount,
                'total_commissions'        => number_format($totalCommissions, 2), // إجمالي العمولات
                'pending_amount'           => number_format($pendingSum,   2), // المبلغ المعلق
                'available_amount'         => number_format($collectedSum, 2), // المبلغ المتاح
                'end_of_month_payment'     => number_format($monthly,      2), // دفعة نهاية الشهر
                'referrals'                => $referrals, // قائمة المحالين
                'payment_history'          => $history->map(fn($t) => [
                    'id'        => $t->id,
                    'amount'    => number_format($t->amount,2),
                    'image_url' => $t->image ? asset($t->image) : null,
                    'type'      => $t->type,
                    'note'      => $t->note,
                    'date'      => $t->created_at->toDateTimeString(),
                ]),
            ],
        ]);
    }

}
