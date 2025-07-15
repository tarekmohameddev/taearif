<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiAffiliateUser;
use App\Models\AffiliateTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
class AffiliateController extends Controller
{
    /**
     * List affiliates with optional search.
    */

    public function index(Request $request)
    {
        $query = ApiAffiliateUser::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('id', 'like', "%{$search}%")
                  ->orWhere('fullname', 'like', "%{$search}%")
                  ->orWhere('bank_name', 'like', "%{$search}%")
                  ->orWhere('bank_account_number', 'like', "%{$search}%")
                  ->orWhere('iban', 'like', "%{$search}%")
                  ->orWhere('request_status', 'like', "%{$search}%");
            });
        }

        $affiliates = $query->latest()->paginate(10);

        $allaffiliates = ApiAffiliateUser::all();

        $summary = [
            'pending_count'  => $allaffiliates->where('request_status', 'pending')->count(),
            'approved_count' => $allaffiliates->where('request_status', 'approved')->count(),
            'total_affiliates' => $allaffiliates->count(),
        ];
        return view('admin.affiliate.index', compact('affiliates','allaffiliates', 'summary'));
    }

    /**
     * Update an affiliate’s request_status (pending | approved | rejected).
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'request_status' => 'required|in:pending,approved,rejected',
        ]);

        ApiAffiliateUser::whereKey($id)->update([
            'request_status' => $request->request_status,
        ]);

        return back()->with('success', 'Affiliate status updated successfully.');
    }

    /**
     * Simple payment-history page: only pending-amount & transactions.
    */

    public function paymentHistory($id)
    {
        $affiliate    = ApiAffiliateUser::findOrFail($id);
        // paginate all transactions
        $transactions = $affiliate->transactions()->latest()->paginate(10, ['*'], 'transactions_page');
        // grab the one “pending” transaction
        $pendingTx = $affiliate->transactions()->where('type','pending')->oldest()->first();

        return view('admin.affiliate.payment-history', [
            'affiliate'      => $affiliate,
            'pending_amount' => $affiliate->pending_amount,
            'transactions'   => $transactions,
            'pendingTx'      => $pendingTx,
        ]);
    }

    /**
     * Approve all pending transactions for an affiliate.
     * Note: This is a bulk operation, so it should be used with caution.
    */

    public function approveAllPending(Request $request, $affiliateId)
    {
        $request->validate([
            'note'  => 'nullable|string|max:255',
            'image' => 'nullable|image|max:2048',
        ]);

        try {
            DB::beginTransaction();

            //affiliate
            $affiliate = ApiAffiliateUser::lockForUpdate()->findOrFail($affiliateId);

            //fetch all pending transactions
            $pendingTxs = AffiliateTransaction::where('affiliate_id', $affiliate->id)->where('type','pending')->with('referralUser')->get();

            if ($pendingTxs->isEmpty()) {
                return back()->with('error', __('No pending transactions to approve.'));
            }

            //note & image =optional
            $baseNote = trim($request->note) !== '' ? $request->note : __('Collected');

            $imagePath = null;
            if ($request->hasFile('image')) {
                $dir = public_path('affiliate_transactions/');
                if (! file_exists($dir)) mkdir($dir, 0755, true);
                $filename = uniqid() .'.'. $request->file('image')->getClientOriginalExtension();
                $request->file('image')->move($dir, $filename);
                $imagePath = 'affiliate_transactions/' . $filename;
            }

            //mark each pending tx collected
            foreach ($pendingTxs as $tx) {
                $user = $tx->referralUser ?? User::find($tx->referral_user_id);
                $username = $user->username ?? ($user->name ?? "user#{$tx->referral_user_id}");

                $tx->update([
                    'type'  => 'collected',
                    'note'  => sprintf(
                        '%s (المُحيل: %s,  الشريك: %s)',
                        $baseNote,
                        $username,
                        $affiliate->fullname
                    ),
                    'image' => $imagePath,
                ]);
            }

            //pending balance
            $total = $pendingTxs->sum('amount');
            $affiliate->decrement('pending_amount', $total);

            DB::commit();

            return back()->with('success', __('All pending transactions have been collected'));
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', __('Failed to collect pending transactions'));
        }
    }

    /**
     * AJAX balance summary ( pending_amount ).
    */

    public function getBalanceSummary($id)
    {
        $affiliate = ApiAffiliateUser::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => [
                'pending_amount' => number_format($affiliate->pending_amount, 2),
            ],
        ]);
    }
}
