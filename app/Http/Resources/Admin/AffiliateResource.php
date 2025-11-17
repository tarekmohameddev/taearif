<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $transactions = $this->relationLoaded('transactions')
            ? $this->transactions
            : $this->transactions()->get();
        $referralsCount = $transactions->count();
        $transfersCount = $transactions->where('type', 'collected')->count();
        $totalEarnings = $transactions->sum('amount');
        $joiningDate = $this->start_date_value?->format('Y-m-d') ?? $this->created_at?->toDateString();

        $partner = $this->relationLoaded('user') && $this->user
            ? [
                'id' => $this->user->id,
                'name' => $this->fullname ?: $this->user->username,
                'username' => $this->user->username,
                'email' => $this->user->email,
            ]
            : [
                'id' => $this->user_id,
                'name' => $this->fullname,
            ];

        return [
            // 'id' => $this->id,
            // 'user_id' => $this->user_id,
            // 'user' => $this->when($this->relationLoaded('user'), function () {
            //     return [
            //         'id' => $this->user->id,
            //         'username' => $this->user->username,
            //         'email' => $this->user->email,
            //     ];
            // }),
            // 'fullname' => $this->fullname,
            // 'bank_name' => $this->bank_name,
            // 'bank_account_number' => $this->bank_account_number,
            // 'iban' => $this->iban,
            // 'commission_percentage' => (float) $this->commission_percentage,
            // 'pending_amount' => (float) $this->pending_amount,
            // 'request_status' => $this->request_status,
            // 'start_date' => $this->start_date_value?->format('Y-m-d'),
            // 'end_date' => $this->to_date_value?->format('Y-m-d'),
            // 'total_earnings' => (float) $totalEarnings,
            // 'paid_earnings' => (float) $paidEarnings,
            // 'transactions_count' => $referralsCount,
            // 'created_at' => $this->created_at?->toIso8601String(),
            // 'updated_at' => $this->updated_at?->toIso8601String(),
            
            // Dashboard-friendly fields
            'id' => $this->id,   
            'partner' => $partner,
            'referrals' => $referralsCount,
            'transfers' => $transfersCount,
            'earnings' => (float) $totalEarnings,
            'commission_percentage' => (float) $this->commission_percentage,
            'pending_amount' => (float) $this->pending_amount,
            'request_status' => $this->request_status,
            'joining_date' => $joiningDate,
        ];
    }
}

