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
                return [
                    'id' => $subscription->id,
                    'user' => [
                        'id' => $subscription->user?->uuid,
                        'name' => $subscription->user?->full_name,
                        'email' => $subscription->user?->email,
                    ],
                    'plan' => [
                        'id' => $subscription->package?->id,
                        'title' => $subscription->package?->title,
                    ],
                    'price' => (float) $subscription->price,
                    'currency' => $subscription->currency,
                    'is_active' => $subscription->isActive(),
                    'is_trial' => $subscription->is_trial,
                    'start_date' => $subscription->start_date?->format('Y-m-d'),
                    'expire_date' => $subscription->expire_date?->format('Y-m-d'),
                    'days_until_expiration' => $subscription->days_until_expiration,
                    'created_at' => $subscription->created_at?->toIso8601String(),
                ];
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
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

