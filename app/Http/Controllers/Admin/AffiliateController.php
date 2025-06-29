<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Api\ApiAffiliateUser;
use App\Models\AffiliateTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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
        $affiliate     = ApiAffiliateUser::findOrFail($id);
        $transactions  = $affiliate->transactions()
                                   ->latest()
                                   ->paginate(10, ['*'], 'transactions_page');

        return view('admin.affiliate.payment-history', [
            'affiliate'     => $affiliate,
            'pending_amount'=> $affiliate->pending_amount,
            'transactions'  => $transactions,
        ]);
    }

    /**
     * Accept (collect) part-or-all of an affiliate’s pending balance.
     * - Decreases pending_amount immediately.
     * - Optionally uploads a receipt image.
     * - Optionally adds a note.
     * - Creates an AffiliateTransaction with type “collected”.
     * - If the amount exceeds pending_amount, returns an error.
     * - If successful, commits the transaction.
     * - If fails, rolls back the transaction and logs the error.
     * - Logs a “collected” AffiliateTransaction for auditing.
     */
    public function approvePendingAmount(Request $request, $id) //payment_history.blade.php
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'image'  => 'nullable|image|max:2048',   // optional receipt
            'note'   => 'nullable|string', // optional note

        ]);

        try {
            DB::beginTransaction();

            $affiliate = ApiAffiliateUser::lockForUpdate()->findOrFail($id);
            $amount    = $request->amount;

            if ($amount > $affiliate->pending_amount) {
                return back()->with('error', __('Amount exceeds pending balance.'));
            }

            // ↓ Subtract the approved amount from the affiliate's pending balance
            $affiliate->pending_amount -= $amount;

            // optional receipt upload
            $imagePath = null;
            if ($request->hasFile('image')) {
                $directory = public_path('affiliate_transactions/');
                $filename = uniqid() . '.' . $request->file('image')->getClientOriginalExtension();

                // Ensure the directory exists
                if (!file_exists($directory)) {
                    mkdir($directory, 0755, true);
                }

                $request->file('image')->move($directory, $filename);
                $imagePath = 'affiliate_transactions/' . $filename;
            }

            $affiliate->save();
            $noteToSave = trim($request->note) !== ''? $request->note: 'Collected by admin';

            AffiliateTransaction::create([
                'affiliate_id' => $affiliate->id,
                'type'         => 'collected',      // collected | Collected | suspend
                'amount'       => $amount,
                'note'         => $noteToSave,
                'image'        => $imagePath,
            ]);

            DB::commit();
            return back()->with('success', __('Pending amount accepted successfully.'));
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('approvePendingAmount error: '.$e->getMessage());
            return back()->with('error', __('Failed to accept pending amount.'));
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
