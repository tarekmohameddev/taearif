<?php

declare(strict_types=1);

namespace App\Domain\PropertyRequests\Services;

use App\Domain\PropertyRequests\Support\LocationLookup;
use App\Models\Api\UserPropertyRequest;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Rules\PropertyTypeRule;
use Illuminate\Support\Collection;

class PropertyRequestMapPinResolver
{
    public const LEGEND = [
        [
            'property_type' => 'residential',
            'label_ar' => 'سكني',
            'label_en' => 'Residential',
            'color' => '#2563eb',
        ],
        [
            'property_type' => 'commercial',
            'label_ar' => 'تجاري',
            'label_en' => 'Commercial',
            'color' => '#dc2626',
        ],
        [
            'property_type' => 'industrial',
            'label_ar' => 'صناعي',
            'label_en' => 'Industrial',
            'color' => '#7c3aed',
        ],
        [
            'property_type' => 'agricultural',
            'label_ar' => 'زراعي',
            'label_en' => 'Agricultural',
            'color' => '#16a34a',
        ],
    ];

    public function __construct(
        private readonly LocationLookup $locationLookup,
    ) {}

    /**
     * @param  Collection<int, UserPropertyRequest>  $requests
     * @param  array<int, object>  $propertiesById
     * @param  array<int, UserCity>  $citiesById
     * @param  array<int, int>  $districtCityIdsByDistrictId
     * @return array{pins: array<int, array<string, mixed>>, skipped_count: int}
     */
    public function resolvePins(
        Collection $requests,
        array $propertiesById,
        array $citiesById,
        array $districtCityIdsByDistrictId,
    ): array {
        $pins = [];
        $skippedCount = 0;

        foreach ($requests as $request) {
            $pin = $this->resolvePinForRequest($request, $propertiesById, $citiesById, $districtCityIdsByDistrictId);
            if ($pin === null) {
                $skippedCount++;
                continue;
            }

            $pins[] = $pin;
        }

        return [
            'pins' => $pins,
            'skipped_count' => $skippedCount,
        ];
    }

    /**
     * @param  array<int, object>  $propertiesById
     * @param  array<int, UserCity>  $citiesById
     * @param  array<int, int>  $districtCityIdsByDistrictId
     * @return array<string, mixed>|null
     */
    private function resolvePinForRequest(
        UserPropertyRequest $request,
        array $propertiesById,
        array $citiesById,
        array $districtCityIdsByDistrictId,
    ): ?array {
        $cityId = $request->city_id ? (int) $request->city_id : null;
        $districtsId = $request->districts_id ? (int) $request->districts_id : null;

        $resolved = $this->resolveCoordinates(
            $request,
            $propertiesById,
            $citiesById,
            $districtCityIdsByDistrictId,
            $cityId,
            $districtsId,
        );

        if ($resolved === null) {
            return null;
        }

        [$lat, $lng, $locationSource, $clickedPropertyId] = $resolved;
        $typeMeta = $this->resolvePropertyTypeMeta($request->property_type);

        $districtRelation = $request->getRelationValue('district');
        $districtName = ($districtRelation && is_object($districtRelation))
            ? ($districtRelation->name_ar ?? null)
            : ($request->getAttribute('district') ?: null);

        return [
            'request_id' => $request->id,
            'lat' => $lat,
            'lng' => $lng,
            'property_type' => $typeMeta['property_type'],
            'property_type_ar' => $typeMeta['label_ar'],
            'pin_color' => $typeMeta['color'],
            'location_source' => $locationSource,
            'initial_property_id' => $request->initial_property_id ? (int) $request->initial_property_id : null,
            'clicked_property_id' => $clickedPropertyId,
            'city_id' => $cityId,
            'districts_id' => $districtsId,
            'district_name' => $districtName,
            'label' => $request->full_name,
            'created_at' => $request->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  array<int, object>  $propertiesById
     * @param  array<int, UserCity>  $citiesById
     * @param  array<int, int>  $districtCityIdsByDistrictId
     * @return array{0: float, 1: float, 2: string, 3: ?int}|null
     */
    private function resolveCoordinates(
        UserPropertyRequest $request,
        array $propertiesById,
        array $citiesById,
        array $districtCityIdsByDistrictId,
        ?int $cityId,
        ?int $districtsId,
    ): ?array {
        if ($request->initial_property_id) {
            $propertyId = (int) $request->initial_property_id;
            $property = $propertiesById[$propertyId] ?? null;
            if ($property !== null) {
                $coords = $this->validatedCoordinates($property->latitude ?? null, $property->longitude ?? null);
                if ($coords !== null) {
                    return [$coords[0], $coords[1], 'clicked_property', $propertyId];
                }
            }
        }

        $coords = $this->validatedCoordinates($request->latitude, $request->longitude);
        if ($coords !== null) {
            return [$coords[0], $coords[1], 'request_coordinates', null];
        }

        if ($cityId !== null && $cityId > 0) {
            $cityCoords = $this->cityCoordinates($cityId, $citiesById);
            if ($cityCoords !== null) {
                return [$cityCoords[0], $cityCoords[1], 'city_fallback', null];
            }
        }

        if ($districtsId !== null && $districtsId > 0) {
            $districtCityId = $districtCityIdsByDistrictId[$districtsId] ?? null;
            if ($districtCityId !== null && $districtCityId > 0) {
                $cityCoords = $this->cityCoordinates($districtCityId, $citiesById);
                if ($cityCoords !== null) {
                    return [$cityCoords[0], $cityCoords[1], 'district_city_fallback', null];
                }
            }
        }

        if ($cityId === null && $districtsId === null) {
            $legacy = $this->resolveLegacyNameFallback($request, $citiesById, $districtCityIdsByDistrictId);
            if ($legacy !== null) {
                return $legacy;
            }
        }

        return null;
    }

    /**
     * @param  array<int, UserCity>  $citiesById
     * @param  array<int, int>  $districtCityIdsByDistrictId
     * @return array{0: float, 1: float, 2: string, 3: ?int}|null
     */
    private function resolveLegacyNameFallback(
        UserPropertyRequest $request,
        array $citiesById,
        array $districtCityIdsByDistrictId,
    ): ?array {
        $cityId = null;
        $districtsId = null;

        if (! empty($request->city) && is_string($request->city)) {
            $cityId = $this->locationLookup->resolveCityIdByName($request->city);
        }

        if (! empty($request->district) && is_string($request->district)) {
            $districtsId = $this->locationLookup->resolveDistrictIdByName($request->district, $cityId);
            if ($districtsId !== null && $cityId === null) {
                $cityId = $districtCityIdsByDistrictId[$districtsId]
                    ?? UserDistrict::query()->where('id', $districtsId)->value('city_id');
                $cityId = $cityId ? (int) $cityId : null;
            }
        }

        if ($cityId !== null && $cityId > 0) {
            $cityCoords = $this->cityCoordinates($cityId, $citiesById);
            if ($cityCoords !== null) {
                return [$cityCoords[0], $cityCoords[1], 'legacy_name_fallback', null];
            }
        }

        if ($districtsId !== null && $districtsId > 0) {
            $districtCityId = $districtCityIdsByDistrictId[$districtsId]
                ?? UserDistrict::query()->where('id', $districtsId)->value('city_id');
            $districtCityId = $districtCityId ? (int) $districtCityId : null;
            if ($districtCityId !== null && $districtCityId > 0) {
                $cityCoords = $this->cityCoordinates($districtCityId, $citiesById);
                if ($cityCoords !== null) {
                    return [$cityCoords[0], $cityCoords[1], 'legacy_name_fallback', null];
                }
            }
        }

        return null;
    }

    /**
     * @param  array<int, UserCity>  $citiesById
     * @return array{0: float, 1: float}|null
     */
    private function cityCoordinates(int $cityId, array $citiesById): ?array
    {
        $city = $citiesById[$cityId] ?? UserCity::query()->find($cityId);
        if ($city === null) {
            return null;
        }

        return $this->validatedCoordinates($city->latitude ?? null, $city->longitude ?? null);
    }

    /**
     * @return array{0: float, 1: float}|null
     */
    private function validatedCoordinates(mixed $lat, mixed $lng): ?array
    {
        if ($lat === null || $lng === null || $lat === '' || $lng === '') {
            return null;
        }

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return null;
        }

        $latF = (float) $lat;
        $lngF = (float) $lng;

        if ($latF < -90.0 || $latF > 90.0 || $lngF < -180.0 || $lngF > 180.0) {
            return null;
        }

        return [$latF, $lngF];
    }

    /**
     * @return array{property_type: ?string, label_ar: ?string, color: string}
     */
    private function resolvePropertyTypeMeta(?string $propertyType): array
    {
        $normalized = PropertyTypeRule::normalize($propertyType);
        if ($normalized === null) {
            return [
                'property_type' => null,
                'label_ar' => null,
                'color' => '#6b7280',
            ];
        }

        foreach (self::LEGEND as $entry) {
            if ($entry['property_type'] === $normalized) {
                return [
                    'property_type' => $normalized,
                    'label_ar' => $entry['label_ar'],
                    'color' => $entry['color'],
                ];
            }
        }

        return [
            'property_type' => $normalized,
            'label_ar' => null,
            'color' => '#6b7280',
        ];
    }
}
