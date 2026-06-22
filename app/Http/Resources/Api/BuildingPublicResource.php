<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BuildingPublicResource extends JsonResource
{
    public function toArray($request): array
    {
        $building = $this->resource;

        return [
            'id' => $building->id,
            'name' => $building->name,
            'slug' => $building->slug,
            'description' => $building->description,
            'featured_image' => $building->featured_image
                ? (str_starts_with($building->featured_image, 'http') ? $building->featured_image : asset($building->featured_image))
                : ($building->image_url ?? null),
            'address' => $building->address,
            'city_id' => $building->city_id,
            'state_id' => $building->state_id,
            'location' => [
                'lat' => $building->latitude ? (float) $building->latitude : null,
                'lng' => $building->longitude ? (float) $building->longitude : null,
            ],
        ];
    }
}
