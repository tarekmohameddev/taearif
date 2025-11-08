<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Dashboard Resource
 *
 * Transforms dashboard metrics for API responses
 */
class DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return $this->resource;
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with($request): array
    {
        $activeRequest = $request ?? request();

        $period = 30;
        $metric = 'all';

        if ($activeRequest) {
            $period = (int) $activeRequest->input('period', 30);
            $metric = (string) $activeRequest->input('metric', 'all');
        }

        return [
            'meta' => [
                'generated_at' => now()->toIso8601String(),
                'period' => $period . ' days',
                'metric' => $metric,
            ],
        ];
    }
}

