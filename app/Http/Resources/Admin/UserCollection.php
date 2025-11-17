<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * User Collection
 *
 * Transforms collection of users for API responses
 */
class UserCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->transform(function ($user) {
                return [
                    'id' => $user->id,
                    'email' => $user->email,
                    'username' => $user->username,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'photo' => $user->photo ? url($user->photo) : null,
                    'company_name' => $user->company_name,
                    'phone' => $user->phone,
                    'active' => $user->active,
                    'featured' => $user->featured === 1,
                    'email_verified' => $user->email_verified,
                    'has_active_subscription' => $user->hasActiveSubscription(),
                    'referred_by' => $user->referrer ? [
                        'id' => $user->referrer->id,
                        'name' => $user->referrer->full_name,
                    ] : null,
                    'active_membership' => $user->activeMembership ? [
                        'package_title' => $user->activeMembership->package?->title,
                        'expire_date' => $user->activeMembership->expire_date,
                        'is_trial' => $user->activeMembership->is_trial,
                    ] : null,
                    'created_at' => $user->created_at?->toIso8601String(),
                ];
            }),
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with($request): array
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

