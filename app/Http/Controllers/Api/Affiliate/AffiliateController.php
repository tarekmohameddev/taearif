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

        if (!$affiliate) {
            return response()->json([
                'status' => 'not_registered',
                'message' => 'Affiliate user not registered.',
            ], 404);
        }
        // sum of all raw commissions ever generated
        $pending   = $affiliate->transactions()->where('type','pending')->sum('amount');
        // sum of all “collected” amounts
        $available = $affiliate->transactions()->where('type','collected')->sum('amount');
        // “Cash still waiting for approval”
        // $pending   = $sumPending - $sumCollected;
        // “Cash that’s been approved (available for the affiliate)”
        // $available = $pending;
        // end-of-the-month payment
        $start = Carbon::now()->startOfMonth();
        $end   = Carbon::now()->endOfMonth();
        $monthly = $affiliate->transactions()->where('type','collected')->whereBetween('created_at', [$start, $end])->sum('amount');
        // history of collected payments
        $history = $affiliate->transactions()->where('type','collected')->orderByDesc('created_at')->get(['id','amount','image','type','note','created_at']);

        // If the user has no transactions, return an empty array
        if ($history->isEmpty()) {
            $history = collect([]);
        }
        // If the user has no pending or available amounts, set them to 0
        if ($pending === null) {
            $pending = 0;
        }
        // If the user has no available amount, set it to 0
        if ($available === null) {
            $available = 0;
        }
        // If the user has no monthly amount, set it to 0
        if ($monthly === null) {
            $monthly = 0;
        }

        $referrals = $affiliate->referrals()
        ->select('id','first_name','last_name','email','created_at')
        ->get()
        ->map(fn($u) => [
            'id'         => $u->id,
            'name'       => "{$u->first_name} {$u->last_name}",
            'email'      => $u->email,
            'joined_at'  => $u->created_at->toDateTimeString(),
            'commission' => $affiliate->transactions()
                                ->where('referral_user_id', $u->id)
                                ->sum('amount'),
        ]);

        return response()->json([
            'success' => true,
            'data'    => [
              'referral_code' => $user->referral_code,
              'referrals'     => $referrals,
              'pending_amount'       => number_format($pending, 2),
              'available_amount'     => number_format($available, 2),
              'end_of_month_payment' => number_format($monthly, 2),
              'payment_history'      => $history->map(fn($t) => [
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
