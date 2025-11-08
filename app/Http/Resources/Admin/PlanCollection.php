<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Plan Collection
 *
 * Transforms collection of plans for API responses
 */
class PlanCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'data' => $this->collection->transform(function ($plan) {
                return [
                    'id' => $plan->id,
                    'title' => $plan->title,
                    'subtitle' => $plan->subtitle,
                    'slug' => $plan->slug,
                    'price' => (float) $plan->price,
                    'term' => $plan->term,
                    'icon' => $plan->icon ? url($plan->icon) : null,
                    'is_active' => $plan->is_active,
                    'featured' => $plan->featured === 1,
                    'is_trial' => $plan->is_trial,
                    'trial_days' => $plan->trial_days,
                    'subscribers_count' => $plan->subscribers_count ?? 0,
                    'serial_number' => $plan->serial_number,
                    'created_at' => $plan->created_at?->toIso8601String(),
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

