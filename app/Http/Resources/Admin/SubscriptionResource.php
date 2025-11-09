<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Subscription Resource
 *
 * Transforms Subscription model for API responses
 */
class SubscriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->uuid,
                'name' => $this->user?->full_name,
                'email' => $this->user?->email,
                'company' => $this->user?->company_name,
            ],
            'plan' => [
                'id' => $this->package?->id,
                'title' => $this->package?->title,
                'slug' => $this->package?->slug,
            ],
            'pricing' => [
                'package_price' => (float) $this->package_price,
                'discount' => (float) $this->discount,
                'final_price' => (float) $this->price,
                'currency' => $this->currency,
                'currency_symbol' => $this->currency_symbol,
                'coupon_code' => $this->coupon_code,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'transaction_id' => $this->transaction_id,
                'receipt' => $this->receipt,
            ],
            'status' => [
                'is_active' => $this->isActive(),
                'is_expired' => $this->isExpired(),
                'status_code' => $this->status,
            ],
            'trial' => [
                'is_trial' => $this->is_trial,
                'trial_days' => $this->trial_days,
            ],
            'dates' => [
                'start_date' => $this->start_date?->format('Y-m-d'),
                'expire_date' => $this->expire_date?->format('Y-m-d'),
                'days_until_expiration' => $this->days_until_expiration,
                'created_at' => $this->created_at?->toIso8601String(),
            ],
        ];
    }
}

