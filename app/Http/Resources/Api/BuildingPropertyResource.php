<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class BuildingPropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        $property = $this->resource;
        $content = $property->contents->first();

        return [
            'id' => $property->id,
            'building_id' => $property->building_id,
            'title' => $content->title ?? 'N/A',
            'slug' => $content->slug ?? null,
            'address' => $content->address ?? 'N/A',
            'price' => $property->price,
            'pricePerMeter' => $property->pricePerMeter,
            'area' => $property->area,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'status' => $property->status,
            'property_status' => $property->property_status,
            'featured' => (bool) $property->featured,
            'featured_image' => $property->featured_image ? asset($property->featured_image) : null,
            'city' => $content && $content->district ? $content->district->city_name_ar : 'N/A',
            'state' => $content && $content->district
                ? $content->district->name_ar
                : ($content && $content->state ? $content->state->name : 'N/A'),
            'country' => $content && $content->country ? $content->country->name : 'N/A',
            'created_at' => $property->created_at?->toISOString(),
        ];
    }
}
