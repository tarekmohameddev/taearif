<?php

declare(strict_types=1);

namespace App\Domain\PropertyRequests\Services;

use App\Domain\PropertyRequests\Support\LocationLookup;
use App\Models\User\UserCity;

class PropertyRequestLocationNormalizer
{
    private const LOCATION_FIELDS = [
        'region',
        'city_id',
        'districts_id',
        'city',
        'district',
        'latitude',
        'longitude',
    ];

    private const MAX_SNAP_KM = 150.0;

    public function __construct(
        private readonly LocationLookup $locationLookup,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function normalize(array $attributes, ?string $source = null): array
    {
        $data = $attributes;

        if (! empty($data['region']) && empty($data['city_id'])) {
            $regionId = (int) $data['region'];
            if ($regionId > 0) {
                $data['city_id'] = $regionId;
            }
        }

        if (empty($data['city_id']) && ! empty($data['city']) && is_string($data['city'])) {
            $resolvedCityId = $this->locationLookup->resolveCityIdByName($data['city']);
            if ($resolvedCityId !== null) {
                $data['city_id'] = $resolvedCityId;
            }
        }

        $cityId = isset($data['city_id']) ? (int) $data['city_id'] : null;
        if ($cityId !== null && $cityId <= 0) {
            unset($data['city_id']);
            $cityId = null;
        }

        if (empty($data['districts_id']) && ! empty($data['district']) && is_string($data['district'])) {
            $resolvedDistrictId = $this->locationLookup->resolveDistrictIdByName($data['district'], $cityId);
            if ($resolvedDistrictId !== null) {
                $data['districts_id'] = $resolvedDistrictId;
            }
        }

        if (isset($data['districts_id'])) {
            $districtId = (int) $data['districts_id'];
            if ($districtId <= 0) {
                unset($data['districts_id']);
            }
        }

        if (array_key_exists('latitude', $data)) {
            $data['latitude'] = $this->normalizeCoordinate($data['latitude'], -90.0, 90.0);
        }
        if (array_key_exists('longitude', $data)) {
            $data['longitude'] = $this->normalizeCoordinate($data['longitude'], -180.0, 180.0);
        }

        // Reverse geocode: pin with no city -> nearest known city
        $resolvedCityId = isset($data['city_id']) ? (int) $data['city_id'] : null;
        $lat = $data['latitude'] ?? null;
        $lng = $data['longitude'] ?? null;
        if (($resolvedCityId === null || $resolvedCityId <= 0) && $lat !== null && $lng !== null) {
            $nearestCityId = $this->nearestCityIdByCoordinates((float) $lat, (float) $lng);
            if ($nearestCityId !== null) {
                $data['city_id'] = $nearestCityId;
            }
        }

        $resolvedCityId = isset($data['city_id']) ? (int) $data['city_id'] : null;
        if (
            ($data['latitude'] ?? null) === null
            && ($data['longitude'] ?? null) === null
            && $resolvedCityId !== null
            && $resolvedCityId > 0
        ) {
            $city = UserCity::query()->find($resolvedCityId);
            if ($city && $this->coordinatesAreValid($city->latitude, $city->longitude)) {
                $data['latitude'] = (float) $city->latitude;
                $data['longitude'] = (float) $city->longitude;
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function hasLocationFields(array $payload): bool
    {
        foreach (self::LOCATION_FIELDS as $field) {
            if (array_key_exists($field, $payload)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeCoordinate(mixed $value, float $min, float $max): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;
        if ($float < $min || $float > $max) {
            return null;
        }

        return $float;
    }

    private function coordinatesAreValid(mixed $lat, mixed $lng): bool
    {
        return $this->normalizeCoordinate($lat, -90.0, 90.0) !== null
            && $this->normalizeCoordinate($lng, -180.0, 180.0) !== null;
    }

    private function nearestCityIdByCoordinates(float $lat, float $lng): ?int
    {
        $nearestId = null;
        $nearestKm = self::MAX_SNAP_KM;

        foreach (UserCity::query()->get(['id', 'latitude', 'longitude']) as $city) {
            if (! $this->coordinatesAreValid($city->latitude, $city->longitude)) {
                continue;
            }

            $cityLat = (float) $city->latitude;
            $cityLng = (float) $city->longitude;
            if ($cityLat === 0.0 && $cityLng === 0.0) {
                continue;
            }

            $distance = $this->haversineKm($lat, $lng, $cityLat, $cityLng);
            if ($distance < $nearestKm) {
                $nearestKm = $distance;
                $nearestId = (int) $city->id;
            }
        }

        return $nearestId;
    }

    private function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadiusKm = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earthRadiusKm * asin(min(1.0, sqrt($a)));
    }
}
