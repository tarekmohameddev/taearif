<?php

namespace App\Http\Resources\Api\V1\TenantWebsite;

use App\Models\Building;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\UserPropertyCharacteristic;
use App\Services\PropertyTranslationService;

class PropertyPublicResource
{
    /** @var list<string> */
    private const CHARACTERISTIC_KEYS = [
        'facade_id',
        'length',
        'width',
        'street_width_north',
        'street_width_south',
        'street_width_east',
        'street_width_west',
        'building_age',
        'rooms',
        'bathrooms',
        'floors',
        'floor_number',
        'driver_room',
        'maid_room',
        'dining_room',
        'living_room',
        'majlis',
        'storage_room',
        'basement',
        'swimming_pool',
        'kitchen',
        'balcony',
        'garden',
        'annex',
        'elevator',
        'private_parking',
        'size',
    ];

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\User\UserDistrict>  $districtsMap
     */
    public static function toListArray(Property $p, int $views, $districtsMap, PropertyTranslationService $translator): array
    {
        $content = optional($p->contents->first());
        $slug = $content?->slug;

        $district = $content && $content->state_id && isset($districtsMap[$content->state_id])
            ? $districtsMap[$content->state_id]
            : null;
        $city = $district?->city;
        $districtStr = trim(implode(' - ', array_filter([$district?->name_ar ?? null, $city?->name_ar ?? null])));

        $featured = $p->featured_image ? asset($p->featured_image) : null;
        $gallery = $p->galleryImages->pluck('image')->map(fn ($img) => asset($img))->toArray();
        $images = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

        [$normalizedPurpose, $isUnavailable] = self::normalizePurpose($p);

        $projectData = null;
        if ($p->relationLoaded('project') && $p->project) {
            $projectContent = $p->project->contents->first();
            $projectData = [
                'id' => $p->project->id,
                'title' => optional($projectContent)->title ?? '',
                'slug' => optional($projectContent)->slug ?? '',
            ];
        }

        return [
            'id' => (string) $p->id,
            'slug' => $slug,
            'title' => $content?->title ?? '',
            'district' => $districtStr,
            'price' => isset($p->price) ? formatNumberWithoutTrailingZeros($p->price) : '0',
            'views' => $views,
            'bedrooms' => (int) ($p->beds ?? 0),
            'bathrooms' => (int) ($p->bath ?? 0),
            'area' => isset($p->area) ? formatNumberWithoutTrailingZeros($p->area) : '0',
            'property_type' => $translator->translateType($p->property_type),
            'property_type_en' => $p->property_type,
            'transactionType' => $translator->translatePurpose($normalizedPurpose),
            'transactionType_en' => $normalizedPurpose,
            'image' => $featured,
            'featured' => (bool) $p->featured,
            'unit_status' => $p->unit_status ?? ($isUnavailable ? 'sold' : 'available'),
            'listing_purpose' => $p->listing_purpose,
            'publish_status' => $p->publish_status,
            'status' => $isUnavailable ? 'unavailable' : 'available',
            'show_reservations' => (bool) $p->show_reservations,
            'createdAt' => $p->created_at?->toISOString(),
            'description' => $content?->description ?? '',
            'features' => is_array($p->features) ? $p->features : [],
            'location' => [
                'lat' => $p->latitude ? (float) $p->latitude : null,
                'lng' => $p->longitude ? (float) $p->longitude : null,
                'address' => $content?->address ? ($content->address . ($city?->name_ar ? '، ' . $city->name_ar : '')) : '',
            ],
            'images' => $images,
            'project' => $projectData,
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\User\UserDistrict>  $districtsMap
     */
    public static function toDetailArray(Property $property, int $views, $districtsMap, PropertyTranslationService $translator): array
    {
        $content = $property->contents->first();

        $district = $content && $content->state_id && isset($districtsMap[$content->state_id])
            ? $districtsMap[$content->state_id]
            : null;
        $city = $district?->city;
        $districtStr = trim(implode(' - ', array_filter([$district?->name_ar ?? null, $city?->name_ar ?? null])));

        $featured = $property->featured_image ? asset($property->featured_image) : null;
        $gallery = $property->galleryImages->pluck('image')->map(fn ($img) => asset($img))->toArray();
        $images = array_values(array_unique(array_filter(array_merge([$featured], $gallery))));

        [$normalizedPurpose, $isUnavailable] = self::normalizePurpose($property);

        $projectData = null;
        if ($property->relationLoaded('project') && $property->project) {
            $projectContent = $property->project->contents->first();
            $projectData = [
                'id' => $property->project->id,
                'title' => optional($projectContent)->title ?? '',
                'slug' => optional($projectContent)->slug ?? '',
                'featured_image' => $property->project->featured_image ? asset($property->project->featured_image) : null,
            ];
        }

        $data = [
            'id' => (string) $property->id,
            'slug' => $content?->slug ?? '',
            'title' => $content?->title ?? '',
            'district' => $districtStr,
            'price' => isset($property->price) ? formatNumberWithoutTrailingZeros($property->price) : '0',
            'views' => $views,
            'bedrooms' => (int) ($property->beds ?? 0),
            'bathrooms' => (int) ($property->bath ?? 0),
            'area' => isset($property->area) ? formatNumberWithoutTrailingZeros($property->area) : '0',
            'property_type' => $translator->translateType($property->property_type),
            'property_type_en' => $property->property_type ?? '',
            'transactionType' => $translator->translatePurpose($normalizedPurpose),
            'transactionType_en' => $normalizedPurpose,
            'image' => $featured,
            'unit_status' => $property->unit_status ?? ($isUnavailable ? 'sold' : 'available'),
            'listing_purpose' => $property->listing_purpose,
            'publish_status' => $property->publish_status,
            'status' => $isUnavailable ? 'unavailable' : 'available',
            'show_reservations' => (bool) $property->show_reservations,
            'createdAt' => $property->created_at?->toISOString(),
            'description' => $content?->description ?? '',
            'features' => is_string($property->features) ? [$property->features] : (is_array($property->features) ? $property->features : []),
            'location' => [
                'lat' => $property->latitude ? (float) $property->latitude : null,
                'lng' => $property->longitude ? (float) $property->longitude : null,
                'address' => $content?->address ? ($content->address . ($city?->name_ar ? '، ' . $city->name_ar : '')) : '',
            ],
            'images' => $images,
            'payment_method' => $translator->translatePaymentMethod($property->payment_method),
            'payment_method_en' => $property->payment_method,
            'pricePerMeter' => isset($property->pricePerMeter) ? formatNumberWithoutTrailingZeros($property->pricePerMeter) : null,
            'floor_planning_image' => collect($property->floor_planning_image)->map(fn ($img) => asset($img))->toArray(),
            'video_url' => $property->video_url ? asset($property->video_url) : null,
            'virtual_tour' => $property->virtual_tour ? asset($property->virtual_tour) : null,
            'video_image' => $property->video_image ? asset($property->video_image) : null,
            'faqs' => $property->faqs ?? [],
            'building' => self::formatBuilding(self::resolveBuildingModel($property)),
            'project' => $projectData,
        ];

        return array_merge($data, self::publicCharacteristics($property->UserPropertyCharacteristics));
    }

    /**
     * Resolve linked Building model. The legacy `building` string column on user_properties
     * shadows the building() relation when accessed as $property->building.
     */
    private static function resolveBuildingModel(Property $property): ?Building
    {
        if (! $property->building_id) {
            return null;
        }

        if ($property->relationLoaded('building')) {
            $relation = $property->getRelation('building');

            return $relation instanceof Building ? $relation : null;
        }

        return $property->building()->first();
    }

    public static function formatBuilding(?Building $building): ?array
    {
        if (! $building) {
            return null;
        }

        return [
            'id' => $building->id,
            'name' => $building->name,
            'slug' => $building->slug,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function publicCharacteristics(?UserPropertyCharacteristic $characteristics): array
    {
        if (! $characteristics) {
            return [];
        }

        $data = [];
        foreach (self::CHARACTERISTIC_KEYS as $key) {
            if (isset($characteristics->{$key})) {
                $data[$key] = $characteristics->{$key};
            }
        }

        if (isset($data['facade_id']) && $data['facade_id'] && $characteristics->relationLoaded('UserFacade')) {
            $facade = $characteristics->UserFacade;
            if ($facade) {
                $data['facade_name'] = $facade->name;
            }
        }

        return $data;
    }

    /**
     * @return array{0: string, 1: bool}
     */
    private static function normalizePurpose(Property $property): array
    {
        $normalizedPurpose = match ($property->purpose) {
            'rented' => 'rent',
            'sold' => 'sale',
            default => $property->purpose,
        };
        $isUnavailable = in_array($property->purpose, ['rented', 'sold'], true);

        return [$normalizedPurpose, $isUnavailable];
    }
}
