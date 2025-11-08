<?php

namespace App\Http\Resources\Admin\Daily;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Daily Dashboard Resource
 *
 * Transforms daily dashboard data for admin
 */
class DailyDashboardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        $data = $this->resource;

        return [
            'date' => $data['date'] ?? now()->toDateString(),
            'statistics' => $data['statistics'] ?? [],
            'summary' => [
                'today' => $data['today_summary'] ?? [],
                'overdue' => $data['overdue_count'] ?? 0,
                'upcoming' => $data['upcoming'] ?? [],
            ],
        ];
    }
}

