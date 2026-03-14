<?php

namespace App\Http\Controllers\Admin;

use App\Models\Api\marketing\CreditTransaction;
use App\Models\Api\marketing\UserCredit;
use App\Models\Api\marketing\CreditPackage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class AdminCreditController extends Controller
{
    public function index(Request $request)
    {
        $transactions = CreditTransaction::with(['user', 'creditPackage'])
            ->when($request->user_name, function ($q) use ($request) {
                $q->whereHas('user', function ($subQ) use ($request) {
                    $subQ->where('name', 'like', '%' . $request->user_name . '%')
                         ->orWhere('username', 'like', '%' . $request->user_name . '%');
                });
            })
            ->when($request->transaction_type, function ($q) use ($request) {
                $q->where('transaction_type', $request->transaction_type);
            })
            ->when($request->status, function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->when($request->payment_method, function ($q) use ($request) {
                $q->where('payment_method', $request->payment_method);
            })
            ->when($request->from_date, function ($q) use ($request) {
                $q->whereDate('created_at', '>=', $request->from_date);
            })
            ->when($request->to_date, function ($q) use ($request) {
                $q->whereDate('created_at', '<=', $request->to_date);
            })
            ->orderByDesc('id')
            ->paginate(20);

        return view('admin.credit_transactions.index', compact('transactions'));
    }

    public function show($id)
    {
        $transaction = CreditTransaction::with(['user', 'creditPackage', 'createdBy'])
            ->findOrFail($id);

        return view('admin.credit_transactions.show', compact('transaction'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,completed,failed,refunded',
        ]);

        $transaction = CreditTransaction::findOrFail($id);
        
        // If changing to completed, add credits to user
        if ($request->status === 'completed' && $transaction->status !== 'completed') {
            $userCredit = UserCredit::getOrCreateForUser($transaction->user_id);
            $userCredit->addCredits(
                $transaction->credits_amount,
                $transaction->credit_package_id,
                "Admin approved transaction #{$transaction->reference_number}"
            );
        }

        $transaction->status = $request->status;
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction status updated successfully.',
        ]);
    }

    public function refund(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $transaction = CreditTransaction::findOrFail($id);

        if ($transaction->status !== 'completed') {
            return response()->json([
                'status' => 'error',
                'message' => 'Only completed transactions can be refunded.',
            ], 400);
        }

        // Create refund transaction
        $refundTransaction = CreditTransaction::create([
            'user_id' => $transaction->user_id,
            'credit_package_id' => $transaction->credit_package_id,
            'transaction_type' => 'refund',
            'credits_amount' => -$transaction->credits_amount, // Negative amount for refund
            'amount_paid' => -$transaction->amount_paid, // Negative amount for refund
            'currency' => $transaction->currency,
            'payment_method' => $transaction->payment_method,
            'status' => 'completed',
            'reference_number' => CreditTransaction::generateReferenceNumber(),
            'description' => "Refund for transaction #{$transaction->reference_number}: {$request->reason}",
            'metadata' => [
                'original_transaction_id' => $transaction->id,
                'refund_reason' => $request->reason,
                'refunded_by' => auth()->id(),
                'refunded_at' => now()->toISOString(),
            ],
        ]);

        // Deduct credits from user
        $userCredit = UserCredit::getOrCreateForUser($transaction->user_id);
        $userCredit->useCredits($transaction->credits_amount, "Refund for transaction #{$transaction->reference_number}");

        // Update original transaction status
        $transaction->status = 'refunded';
        $transaction->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Transaction refunded successfully.',
            'refund_transaction_id' => $refundTransaction->id,
        ]);
    }

    public function destroy($id)
    {
        $transaction = CreditTransaction::findOrFail($id);
        
        // Only allow deletion of pending or failed transactions
        if (!in_array($transaction->status, ['pending', 'failed'])) {
            return redirect()->back()->with('error', 'Only pending or failed transactions can be deleted.');
        }

        $transaction->delete();

        return redirect()->back()->with('success', 'Transaction deleted successfully.');
    }

    public function statistics()
    {
        $stats = [
            'total_transactions' => CreditTransaction::count(),
            'total_revenue' => CreditTransaction::where('status', 'completed')
                ->where('transaction_type', 'purchase')
                ->sum('amount_paid'),
            'total_credits_sold' => CreditTransaction::where('status', 'completed')
                ->where('transaction_type', 'purchase')
                ->sum('credits_amount'),
            'pending_transactions' => CreditTransaction::where('status', 'pending')->count(),
            'failed_transactions' => CreditTransaction::where('status', 'failed')->count(),
            'refunded_transactions' => CreditTransaction::where('status', 'refunded')->count(),
        ];

        // Monthly statistics
        $monthlyStats = CreditTransaction::where('status', 'completed')
            ->where('transaction_type', 'purchase')
            ->whereYear('created_at', now()->year)
            ->selectRaw('MONTH(created_at) as month, SUM(amount_paid) as revenue, SUM(credits_amount) as credits')
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        return view('admin.credit_transactions.statistics', compact('stats', 'monthlyStats'));
    }

    public function userCredits(Request $request)
    {
        $userCredits = UserCredit::with('user')
            ->when($request->user_name, function ($q) use ($request) {
                $q->whereHas('user', function ($subQ) use ($request) {
                    $subQ->where('name', 'like', '%' . $request->user_name . '%')
                         ->orWhere('username', 'like', '%' . $request->user_name . '%');
                });
            })
            ->when($request->min_balance, function ($q) use ($request) {
                $q->where('total_credits', '>=', $request->min_balance);
            })
            ->when($request->max_balance, function ($q) use ($request) {
                $q->where('total_credits', '<=', $request->max_balance);
            })
            ->orderByDesc('total_credits')
            ->paginate(20);

        return view('admin.credit_transactions.user_credits', compact('userCredits'));
    }

    public function addCredits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'credits' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $userCredit = UserCredit::getOrCreateForUser($request->user_id);
        
        // Create admin credit addition transaction
        $transaction = CreditTransaction::create([
            'user_id' => $request->user_id,
            'credit_package_id' => null,
            'transaction_type' => 'admin_add',
            'credits_amount' => $request->credits,
            'amount_paid' => 0, // Free credits from admin
            'currency' => 'SAR',
            'payment_method' => 'admin',
            'status' => 'completed',
            'reference_number' => CreditTransaction::generateReferenceNumber(),
            'description' => "Admin added credits: {$request->reason}",
            'metadata' => [
                'added_by' => auth()->id(),
                'added_at' => now()->toISOString(),
                'reason' => $request->reason,
            ],
        ]);

        // Add credits to user
        $userCredit->addCredits($request->credits, null, "Admin added credits: {$request->reason}");

        return response()->json([
            'status' => 'success',
            'message' => 'Credits added successfully.',
            'transaction_id' => $transaction->id,
            'new_balance' => $userCredit->fresh()->total_credits,
        ]);
    }

    public function removeCredits(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'credits' => 'required|integer|min:1',
            'reason' => 'required|string|max:255',
        ]);

        $userCredit = UserCredit::getOrCreateForUser($request->user_id);

        if (!$userCredit->hasEnoughCredits($request->credits)) {
            return response()->json([
                'status' => 'error',
                'message' => 'User does not have enough credits.',
            ], 400);
        }

        // Create admin credit removal transaction
        $transaction = CreditTransaction::create([
            'user_id' => $request->user_id,
            'credit_package_id' => null,
            'transaction_type' => 'admin_remove',
            'credits_amount' => -$request->credits, // Negative amount for removal
            'amount_paid' => 0,
            'currency' => 'SAR',
            'payment_method' => 'admin',
            'status' => 'completed',
            'reference_number' => CreditTransaction::generateReferenceNumber(),
            'description' => "Admin removed credits: {$request->reason}",
            'metadata' => [
                'removed_by' => auth()->id(),
                'removed_at' => now()->toISOString(),
                'reason' => $request->reason,
            ],
        ]);

        // Remove credits from user
        $userCredit->useCredits($request->credits, "Admin removed credits: {$request->reason}");

        return response()->json([
            'status' => 'success',
            'message' => 'Credits removed successfully.',
            'transaction_id' => $transaction->id,
            'new_balance' => $userCredit->fresh()->total_credits,
        ]);
    }
}
