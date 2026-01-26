<?php

namespace App\Http\Resources\Api\V1\Analytics;

use Illuminate\Http\Resources\Json\JsonResource;

class PageviewResource extends JsonResource
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
            'views_count' => $this->resource['views_count'] ?? 0,
        ];
    }
}
