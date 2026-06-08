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
}
