<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateTransactionResource extends JsonResource
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
            'affiliate' => $this->when($this->relationLoaded('affiliate'), function () {
                return [
                    'id' => $this->affiliate->id,
                    'fullname' => $this->affiliate->fullname,
                    'user' => $this->when($this->affiliate->relationLoaded('user'), function () {
                        return [
                            'id' => $this->affiliate->user->id,
                            'uuid' => $this->affiliate->user->uuid,
                            'username' => $this->affiliate->user->username,
                            'email' => $this->affiliate->user->email,
                        ];
                    }),
                ];
            }),
            'referred_user' => $this->when($this->relationLoaded('referredUser'), function () {
                return $this->referredUser ? [
                    'id' => $this->referredUser->id,
                    'uuid' => $this->referredUser->uuid,
                    'username' => $this->referredUser->username,
                    'email' => $this->referredUser->email,
                ] : null;
            }),
            'type' => $this->type,
            'amount' => (float) $this->amount,
            'note' => $this->note,
            'image' => $this->image,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

