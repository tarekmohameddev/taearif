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
        $isActive = $this->isActive();
        $isExpired = $this->isExpired();
        $isTrial = (bool) $this->is_trial;

        return [
            'id' => $this->user?->id,
            'username' => $this->user?->username,
            'company' => $this->user?->company_name,
            'tenant_name' => $this->user?->generalSetting?->site_name,
            'plan' => [
                'id' => $this->package?->id,
                'title' => $this->package?->getDisplayTitle('ar'),
                'slug' => $this->package?->slug,
            ],
            'pricing' => [
                'package_price' => $this->package_price !== null ? (float) $this->package_price : null,
                'discount' => $this->discount !== null ? (float) $this->discount : null,
                'final_price' => $this->price !== null ? (float) $this->price : null,
                'currency' => $this->currency,
                'currency_symbol' => $this->currency_symbol,
                'coupon_code' => $this->coupon_code,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'transaction_id' => $this->transaction_id,
                'receipt' => $this->receipt,
            ],
            'invoice' => $this->latestInvoice ? [
                'id' => $this->latestInvoice->id,
                'transaction_id' => $this->latestInvoice->transaction_id,
                'status' => [
                    'code' => $this->latestInvoice->status,
                    'label' => $this->latestInvoice->status_text ?? null,
                ],
                'amount' => [
                    'total' => $this->latestInvoice->price !== null ? (float) $this->latestInvoice->price : null,
                    'currency' => $this->latestInvoice->currency,
                    'currency_symbol' => $this->latestInvoice->currency_symbol,
                ],
                'payment_method' => $this->latestInvoice->payment_method,
                'created_at' => $this->latestInvoice->created_at?->toIso8601String(),
            ] : null,
            'status' => [
                'is_active' => $isActive,
                'is_trial' => $isTrial,
                'is_expired' => $isExpired,
                'label' => $isTrial ? 'trial' : ($isExpired ? 'expired' : ($isActive ? 'active' : 'inactive')),
                'status_code' => $this->status,
            ],
            'trial' => [
                'is_trial' => $isTrial,
                'trial_days' => $this->trial_days,
            ],
            'dates' => [
                'start_date' => $this->start_date?->format('Y-m-d'),
                'upcoming_billing' => $this->expire_date?->format('Y-m-d'),
                'created_at' => $this->created_at?->toIso8601String(),
            ],
        ];
    }
}

