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
            'pages' => $this->resource['pages'] ?? 0,
            'views' => $this->resource['views'] ?? 0,
            'unique_visitors' => $this->resource['unique_visitors'] ?? 0,
            'total_visits' => $this->resource['total_visits'] ?? 0,
            'total_unique_visitors' => $this->resource['total_unique_visitors'] ?? 0,
            'active_days' => $this->resource['active_days'] ?? 0,
            'visitor_data' => $this->resource['visitor_data'] ?? [],
            'most_visited_pages' => $this->resource['most_visited_pages'] ?? [],
            'properties_visits' => $this->resource['properties_visits'] ?? 0,
            'time_range' => $this->resource['time_range'] ?? 30,
            'period' => $this->resource['period'] ?? [],
        ];
    }
}
