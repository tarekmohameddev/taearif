<?php

namespace App\Http\Resources\Api\V1\Analytics;

use Illuminate\Http\Resources\Json\JsonResource;

class Ga4TopPageResource extends JsonResource
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
            'page_path' => $this->resource['page_path'] ?? null,
            'page_title' => $this->resource['page_title'] ?? null,
            'views' => $this->resource['views'] ?? 0,
            'sessions' => $this->resource['sessions'] ?? 0,
            'users' => $this->resource['users'] ?? 0,
            'percentage' => $this->resource['percentage'] ?? 0,
        ];
    }
}
