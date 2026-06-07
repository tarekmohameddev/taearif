<?php

namespace App\Http\Resources\Api;

use App\Services\Property\PropertyStatusSyncService;
use Illuminate\Http\Resources\Json\JsonResource;

class PropertyListResource extends JsonResource
{
    public function toArray($request): array
    {
        $property = $this->resource;
        $content = $this->additional['content'] ?? optional($property->contents->first());

        $listingPurpose = $property->listing_purpose
            ?? PropertyStatusSyncService::resolveListingPurposeFromLegacy(
                $property->purpose,
                $property->property_status
            );
        $unitStatus = $property->unit_status
            ?? PropertyStatusSyncService::resolveUnitStatusFromLegacy(
                $property->purpose,
                $property->property_status
            );
        $publishStatus = $property->publish_status
            ?? PropertyStatusSyncService::resolvePublishStatusFromLegacy(
                $property->completion_status,
                $property->status
            );

        $purpose = $property->purpose;

        $projectData = null;
        if ($property->relationLoaded('project') && $property->project) {
            $projectContent = $property->project->contents->first();
            $projectData = [
                'id' => $property->project->id,
                'title' => optional($projectContent)->title ?? '',
                'slug' => optional($projectContent)->slug ?? '',
            ];
        }

        $buildingData = null;
        if ($property->building_id && $property->relationLoaded('building') && $property->building) {
            $buildingData = [
                'id' => $property->building->id,
                'name' => $property->building->name,
                'slug' => $property->building->slug,
            ];
        }

        return [
            'id' => $property->id,
            'visits' => (int) ($this->additional['visits'] ?? 0),
            'title' => $content->title ?? 'No Title',
            'address' => $content->address ?? 'No Address',
            'slug' => $content->slug ?? null,
            'price' => $property->price,
            'property_type' => $property->property_type,
            'beds' => $property->beds,
            'bath' => $property->bath,
            'area' => isset($property->area) ? formatNumberWithoutTrailingZeros($property->area) : null,
            'purpose' => $purpose,
            'transaction_type' => $purpose,
            'listing_purpose' => $listingPurpose,
            'unit_status' => $unitStatus,
            'publish_status' => $publishStatus,
            'property_status' => $property->property_status,
            'features' => $property->features,
            'status' => $property->status,
            'featured_image' => asset($property->featured_image),
            'featured' => (bool) $property->featured,
            'show_reservations' => (bool) $property->show_reservations,
            'created_at' => $property->created_at->toISOString(),
            'updated_at' => $property->updated_at->toISOString(),
            'payment_method' => $property->payment_method,
            'latitude' => $property->latitude !== null ? (float) $property->latitude : null,
            'longitude' => $property->longitude !== null ? (float) $property->longitude : null,
            'project_id' => $property->project_id,
            'building_id' => $property->building_id,
            'project' => $projectData,
            'building' => $buildingData,
            'creator' => $property->creator ? [
                'id' => $property->creator->id,
                'name' => trim(($property->creator->first_name ?? '') . ' ' . ($property->creator->last_name ?? ''))
                    ?: ($property->creator->username ?? $property->creator->email),
                'type' => $property->creator->account_type,
            ] : null,
        ];
    }
}
