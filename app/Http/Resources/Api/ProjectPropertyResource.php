<?php

namespace App\Http\Resources\Api;

use App\Http\Resources\Concerns\ExposesPropertyBrokerFields;
use App\Http\Resources\Concerns\FormatsPropertyCreator;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectPropertyResource extends JsonResource
{
    use ExposesPropertyBrokerFields;
    use FormatsPropertyCreator;
    public function toArray($request): array
    {
        $property = $this->resource;
        $content = $property->contents->first();
        $districtId = optional($content)->state_id;

        return array_merge([
            'id' => $property->id,
            'project_id' => $property->project_id,
            'title' => optional($content)->title ?? '',
            'slug' => optional($content)->slug ?? '',
            'address' => optional($content)->address ?? '',
            'description' => optional($content)->description ?? '',
            'price' => $property->price,
            'pricePerMeter' => $property->pricePerMeter,
            'purpose' => $property->purpose,
            'listing_purpose' => $property->listing_purpose,
            'unit_status' => $property->unit_status,
            'publish_status' => $property->publish_status,
            'property_type' => $property->property_type,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => $property->area,
            'size' => $property->size,
            'featured_image' => $property->featured_image ? asset($property->featured_image) : null,
            'gallery' => $property->relationLoaded('galleryImages')
                ? $property->galleryImages->map(fn ($image) => asset($image->image))->toArray()
                : [],
            'location' => [
                'latitude' => $property->latitude,
                'longitude' => $property->longitude,
            ],
            'city_id' => optional($content)->city_id,
            'state_id' => $districtId,
            'district_id' => $districtId,
            'status' => (bool) $property->status,
            'featured' => (bool) $property->featured,
            'show_reservations' => (bool) $property->show_reservations,
            'property_status' => $property->property_status,
            'features' => $property->features ?? [],
            'faqs' => $property->faqs ?? [],
            'category_id' => $property->category_id,
            'payment_method' => $property->payment_method,
            'video_url' => $property->video_url ? asset($property->video_url) : null,
            'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
            'advertising_license' => $property->advertising_license,
            'created_at' => $property->created_at?->toISOString(),
            'updated_at' => $property->updated_at?->toISOString(),
            'created_by' => $this->formatCreator($property->creator),
        ], $this->brokerFields($request, $property));
    }
}

