<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PropertyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $property = $this->resource;
        $content = $property->contents->first();
        $characteristics = optional($property->UserPropertyCharacteristics)->toArray() ?? [];
        
        // External data passed through the resource
        $views = $this->additional['views'] ?? 0;

        return array_merge([
            'id' => $property->id,
            'project_id' => $property->project_id,
            'payment_method' => $property->payment_method,
            'title' => optional($content)->title ?? '',
            'slug' => optional($content)->slug ?? '',
            'address' => optional($content)->address ?? '',
            'price' => isset($property->price) ? formatNumberWithoutTrailingZeros($property->price) : '0',
            'views' => $views,
            'pricePerMeter' => isset($property->pricePerMeter) ? formatNumberWithoutTrailingZeros($property->pricePerMeter) : null,
            'purpose' => $property->purpose,
            'type' => $property->type ?? '',
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => isset($property->area) ? formatNumberWithoutTrailingZeros($property->area) : null,
            'features' => $property->features ?? [],
            'status' => (int) $property->status,
            'featured_image' => asset($property->featured_image),
            'floor_planning_image' => $property->floor_planning_image_urls,
            'gallery' => $property->gallery_urls,
            'description' => optional($content)->description ?? '',
            'location' => [
                'latitude' => $property->latitude ? (float) $property->latitude : null,
                'longitude' => $property->longitude ? (float) $property->longitude : null,
            ],
            'featured' => (bool) $property->featured,
            'show_reservations' => (bool) $property->show_reservations,
            'city_id' => optional($content)->city_id,
            'state_id' => optional($content)->state_id,
            'video_url' => $property->video_url ? asset($property->video_url) : null,
            'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
            'video_image' => $property->video_image ? asset($property->video_image) : null,
            'category_id' => $property->category_id,
            'size' => $property->size ?? null,
            'faqs' => $property->faqs ?? [],
            'building' => $property->building,
            'water_meter_number' => $property->water_meter_number,
            'electricity_meter_number' => $property->electricity_meter_number,
            'deed_number' => $property->deed_number ? asset($property->deed_number) : null,
            'advertising_license' => $property->advertising_license,
            'created_at' => $property->created_at->toISOString(),
            'updated_at' => $property->updated_at->toISOString(),
            'creator' => $property->creator ? [
                'id'   => $property->creator->id,
                'name' => trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? '')) ?: ($property->creator->username ?? $property->creator->email),
                'type' => $property->creator->account_type,
            ] : null,
        ], $characteristics);
    }
}
