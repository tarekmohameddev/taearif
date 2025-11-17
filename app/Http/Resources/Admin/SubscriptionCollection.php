<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Subscription Collection
 *
 * Transforms collection of subscriptions for API responses
 */
class SubscriptionCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->transform(function ($subscription) {
                $isActive = $subscription->isActive();
                $isExpired = $subscription->isExpired();
                $isTrial = (bool) $subscription->is_trial;

                return [
                    'id' => $subscription->user?->id,
                    'username' => $subscription->user?->username,
                    'company' => $subscription->user?->company_name,
                    'tenant_name' => $subscription->user?->generalSetting?->site_name,
                    'plan' => [
                        'id' => $subscription->package?->id,
                        'title' => $subscription->package?->title,
                        'slug' => $subscription->package?->slug,
                    ],
                    'pricing' => [
                        'package_price' => $subscription->package_price !== null ? (float) $subscription->package_price : null,
                        'final_price' => $subscription->price !== null ? (float) $subscription->price : null,
                        'currency' => $subscription->currency,
                        'currency_symbol' => $subscription->currency_symbol,
                    ],
                    'payment_method' => $subscription->payment_method,
                    'status' => [
                        'is_active' => $isActive,
                        'is_trial' => $isTrial,
                        'is_expired' => $isExpired,
                        'label' => $isTrial ? 'trial' : ($isExpired ? 'expired' : ($isActive ? 'active' : 'inactive')),
                    ],
                    'upcoming_billing' => $subscription->expire_date?->format('Y-m-d'),
                    'invoice' => $subscription->latestInvoice ? [
                        'id' => $subscription->latestInvoice->id,
                        'transaction_id' => $subscription->latestInvoice->transaction_id,
                        'status' => [
                            'code' => $subscription->latestInvoice->status,
                            'label' => $subscription->latestInvoice->status_text ?? null,
                        ],
                        'amount' => [
                            'total' => $subscription->latestInvoice->price !== null ? (float) $subscription->latestInvoice->price : null,
                            'currency' => $subscription->latestInvoice->currency,
                            'currency_symbol' => $subscription->latestInvoice->currency_symbol,
                        ],
                        'payment_method' => $subscription->latestInvoice->payment_method,
                        'created_at' => $subscription->latestInvoice->created_at?->toIso8601String(),
                    ] : null,
                ];
            }),
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }
}

