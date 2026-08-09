<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Shared property list/export filter chain.
 * Accepts both `property_type` and `type` for the type filter (controller vs export key drift).
 */
class PropertyFilterQuery
{
    public static function apply(Builder $query, array $filters): Builder
    {
        // Apply property IDs filter if provided
        if (!empty($filters['ids']) && is_array($filters['ids']) && count($filters['ids']) > 0) {
            $query->whereIn('id', $filters['ids']);
        }

        // Apply date range filter
        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        // Apply purpose filter
        if (!empty($filters['purposes_filter'])) {
            $query->where('purpose', $filters['purposes_filter']);
        }
        if (!empty($filters['purpose'])) {
            $query->where('purpose', $filters['purpose']);
        }

        // Apply type filter (accept both property_type and type keys)
        $propertyType = $filters['property_type'] ?? $filters['type'] ?? null;
        if (!empty($propertyType)) {
            $query->where('property_type', $propertyType);
        }

        // Apply price filters
        if (!empty($filters['price_from'])) {
            $query->where('price', '>=', $filters['price_from']);
        }
        if (!empty($filters['price_to'])) {
            $query->where('price', '<=', $filters['price_to']);
        }

        // Apply area filters
        if (!empty($filters['area_from'])) {
            $query->where('area', '>=', $filters['area_from']);
        }
        if (!empty($filters['area_to'])) {
            $query->where('area', '<=', $filters['area_to']);
        }

        // Apply beds filter
        if (!empty($filters['beds'])) {
            $query->where('beds', $filters['beds']);
        }

        // Apply bath filter
        if (!empty($filters['bath'])) {
            $query->where('bath', $filters['bath']);
        }

        // Apply category filter
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        // Apply status filter
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }

        // Apply featured filter
        if (isset($filters['featured']) && $filters['featured'] !== '') {
            $query->where('featured', $filters['featured']);
        }

        // Apply city filter
        if (!empty($filters['city_id'])) {
            $query->whereHas('contents', function ($q) use ($filters) {
                $q->where('city_id', $filters['city_id']);
            });
        }

        // Apply district filter
        if (!empty($filters['district_id'])) {
            $query->whereHas('contents', function ($q) use ($filters) {
                $q->where('state_id', $filters['district_id']);
            });
        }

        // Apply search filter (title/description/address, plus numeric property ID)
        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $numericId = self::parsePositiveIntSearchId($search);
            $query->where(function ($q) use ($search, $numericId) {
                $q->whereHas('contents', function ($cq) use ($search) {
                    $cq->where(function ($inner) use ($search) {
                        $inner->where('title', 'like', "%{$search}%")
                              ->orWhere('description', 'like', "%{$search}%")
                              ->orWhere('address', 'like', "%{$search}%");
                    });
                });
                if ($numericId !== null) {
                    $q->orWhere('id', $numericId);
                }
            });
        }

        // Apply features filter
        if (!empty($filters['features'])) {
            $featuresArray = explode(',', $filters['features']);
            foreach ($featuresArray as $feature) {
                $feature = trim($feature);
                $query->whereJsonContains('features', $feature);
            }
        }

        return $query;
    }

    private static function parsePositiveIntSearchId(?string $searchTerm): ?int
    {
        $searchTerm = trim((string) $searchTerm);
        if ($searchTerm === ''
            || !ctype_digit($searchTerm)
            || strlen($searchTerm) > 18
            || (int) $searchTerm <= 0
        ) {
            return null;
        }

        return (int) $searchTerm;
    }
}
