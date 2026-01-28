<?php

namespace App\Http\Resources\Api\V1\Analytics;

use Illuminate\Http\Resources\Json\JsonResource;

class TopPageResource extends JsonResource
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
            'slug' => $this->resource['slug'] ?? null,
            'dynamic_slug' => $this->resource['dynamic_slug'] ?? null,
            'path' => $this->resource['path'] ?? null,
            'page_type' => $this->resource['page_type'] ?? null,
            'views' => $this->resource['views'] ?? 0,
        ];
    }
}
