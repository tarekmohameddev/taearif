<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateDetailResource extends JsonResource
{
    public function toArray($request): array
    {
        $transactions = $this->relationLoaded('transactions')
            ? $this->transactions->loadMissing('referredUser')
            : $this->transactions()->with('referredUser')->get();

        $referralsCount = $transactions->count();
        $collectedTransactions = $transactions->where('type', 'collected');
        $transfersCount = $collectedTransactions->count();

        $totalEarnings = (float) $this->total_earnings;
        $paidEarnings = (float) $this->paid_earnings;
        $pendingEarnings = (float) $this->pending_amount;

        $partner = [
            'id' => $this->user?->id,
            'name' => $this->fullname ?: $this->user?->username,
            'email' => $this->user?->email,
            'phone' => $this->user->phone ?? null,
            'request_status' => $this->request_status,
            'commission_percentage' => (float) $this->commission_percentage,
        ];

        $referralLink = $this->user->referral_link ?? ($this->user->referral_code ? url('/ref/' . $this->user->referral_code) : null);

        return [
            'partner' => $partner,
            'cards' => [
                'total_earnings' => $totalEarnings,
                'pending_earnings' => $pendingEarnings,
                'paid_earnings' => $paidEarnings,
                'referrals_count' => $referralsCount,
                'transfers_count' => $transfersCount,
            ],
            'referral_link' => $referralLink,
            'referrals_history' => $transactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'referred_user' => $transaction->referredUser ? [
                        'id' => $transaction->referredUser->id,
                        'username' => $transaction->referredUser->username,
                        'email' => $transaction->referredUser->email,
                    ] : null,
                    'amount' => (float) $transaction->amount,
                    'status' => $transaction->type,
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                ];
            })->values(),
            'payouts_history' => $collectedTransactions->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'amount' => (float) $transaction->amount,
                    'note' => $transaction->note,
                    'created_at' => $transaction->created_at?->toIso8601String(),
                ];
            })->values(),
            'joining_date' => $this->start_date_value?->format('Y-m-d') ?? $this->created_at?->toDateString(),
            'last_activity_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}


