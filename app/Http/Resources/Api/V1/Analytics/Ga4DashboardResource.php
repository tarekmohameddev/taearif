<?php

namespace App\Http\Resources\Api\V1\Analytics;

use Illuminate\Http\Resources\Json\JsonResource;

class Ga4DashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'total_views' => $this->resource['total_views'] ?? 0,
            'total_sessions' => $this->resource['total_sessions'] ?? 0,
            'total_users' => $this->resource['total_users'] ?? 0,
            'unique_pages' => $this->resource['unique_pages'] ?? 0,
            'active_days' => $this->resource['active_days'] ?? 0,
            'daily_trend' => $this->resource['daily_trend'] ?? [],
            'period' => $this->resource['period'] ?? [],
        ];
    }
}
