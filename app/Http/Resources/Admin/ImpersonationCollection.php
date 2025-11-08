<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Impersonation Collection
 *
 * Transforms collection of impersonations for API responses
 */
class ImpersonationCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->transform(function ($impersonation) {
                return [
                    'id' => $impersonation->id,
                    'admin' => [
                        'id' => $impersonation->admin->uuid ?? null,
                        'full_name' => $impersonation->admin->full_name ?? null,
                        'email' => $impersonation->admin->email ?? null,
                    ],
                    'user' => [
                        'id' => $impersonation->user->uuid ?? null,
                        'full_name' => $impersonation->user->full_name ?? null,
                        'email' => $impersonation->user->email ?? null,
                    ],
                    'started_at' => $impersonation->started_at?->toIso8601String(),
                    'ended_at' => $impersonation->ended_at?->toIso8601String(),
                    'duration' => $impersonation->formatted_duration,
                    'ip_address' => $impersonation->ip_address,
                    'reason' => $impersonation->reason,
                    'actions_count' => $impersonation->actions_count,
                    'status' => $impersonation->status,
                    'is_active' => $impersonation->isActive(),
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
        if (method_exists($this->resource, 'total')) {
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

        return [
            'meta' => [
                'total' => $this->collection->count(),
            ],
        ];
    }
}

