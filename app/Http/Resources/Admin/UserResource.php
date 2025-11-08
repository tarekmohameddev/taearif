<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * User Resource
 *
 * Transforms User model for API responses
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'email' => $this->email,
            'username' => $this->username,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'photo' => $this->photo ? url($this->photo) : null,
            'phone' => $this->phone,
            'company_name' => $this->company_name,
            'city' => $this->city,
            'state' => $this->state,
            'address' => $this->address,
            'country' => $this->country,
            'status' => [
                'active' => $this->active,
                'status_code' => $this->status,
                'featured' => $this->featured === 1,
                'email_verified' => $this->email_verified,
                'online' => $this->online_status,
            ],
            'subscription' => [
                'has_active' => $this->hasActiveSubscription(),
                'amount' => $this->subscription_amount,
                'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            ],
            'referral' => [
                'code' => $this->referral_code,
                'referred_by' => $this->when($this->referrer, [
                    'id' => $this->referrer?->uuid,
                    'name' => $this->referrer?->full_name,
                    'email' => $this->referrer?->email,
                ]),
                'referrals_count' => $this->referrals()->count(),
            ],
            'active_membership' => $this->when($this->activeMembership, function () {
                return [
                    'id' => $this->activeMembership->id,
                    'package' => [
                        'id' => $this->activeMembership->package?->id,
                        'title' => $this->activeMembership->package?->title,
                    ],
                    'price' => $this->activeMembership->price,
                    'currency' => $this->activeMembership->currency,
                    'start_date' => $this->activeMembership->start_date,
                    'expire_date' => $this->activeMembership->expire_date,
                    'is_trial' => $this->activeMembership->is_trial,
                    'trial_days' => $this->activeMembership->trial_days,
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}

