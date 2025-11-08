<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user' => $this->when($this->relationLoaded('user'), function () {
                return [
                    'id' => $this->user->id,
                    'uuid' => $this->user->uuid,
                    'username' => $this->user->username,
                    'email' => $this->user->email,
                ];
            }),
            'fullname' => $this->fullname,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'iban' => $this->iban,
            'commission_percentage' => (float) $this->commission_percentage,
            'pending_amount' => (float) $this->pending_amount,
            'request_status' => $this->request_status,
            'start_date' => $this->start_date_value?->format('Y-m-d'),
            'end_date' => $this->to_date_value?->format('Y-m-d'),
            'total_earnings' => $this->when($this->relationLoaded('transactions'), function () {
                return (float) $this->total_earnings;
            }),
            'paid_earnings' => $this->when($this->relationLoaded('transactions'), function () {
                return (float) $this->paid_earnings;
            }),
            'transactions_count' => $this->when($this->relationLoaded('transactions'), function () {
                return $this->transactions->count();
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

