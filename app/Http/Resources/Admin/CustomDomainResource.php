<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomDomainResource extends JsonResource
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
                $user = $this->user;

                if (!$user) {
                    return null;
                }

                return [
                    'id' => $user->id,
                    'uuid' => $user->uuid,
                    'username' => $user->username,
                    'email' => $user->email,
                ];
            }),
            'requested_domain' => $this->requested_domain,
            'current_domain' => $this->current_domain,
            'status' => $this->status,
            'is_active' => $this->status,
            'is_approved' => $this->isApproved(),
            'is_pending' => $this->isPending(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

