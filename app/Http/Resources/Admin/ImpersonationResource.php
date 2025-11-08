<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Impersonation Resource
 *
 * Transforms AdminImpersonation model for API responses
 */
class ImpersonationResource extends JsonResource
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
            'admin' => [
                'id' => $this->admin->uuid ?? null,
                'full_name' => $this->admin->full_name ?? null,
                'email' => $this->admin->email ?? null,
            ],
            'user' => [
                'id' => $this->user->uuid ?? null,
                'full_name' => $this->user->full_name ?? null,
                'email' => $this->user->email ?? null,
                'company_name' => $this->user->company_name ?? null,
            ],
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),
            'duration' => $this->formatted_duration,
            'ip_address' => $this->ip_address,
            'reason' => $this->reason,
            'actions_count' => $this->actions_count,
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}

