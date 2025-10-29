<?php

namespace App\Services\Matching;

use App\Repositories\PropertyRepository;
use App\Support\DTO\UnifiedRequest;
use Illuminate\Database\Eloquent\Builder;

class PropertySearchService
{
    public function __construct(private PropertyRepository $properties)
    {
    }

    /**
     * Build a pre-filtered query for candidate properties based on hard constraints.
     */
    public function buildCandidatesQuery(UnifiedRequest $request): Builder
    {
        $q = $this->properties->baseQuery();

        // Scope to the same owner/tenant
        if ($request->userId) {
            $q->where('user_id', $request->userId);
        }

        // Location: region/city/district
        if ($request->regionId) {
            $q->where('region_id', $request->regionId);
        }
        if ($request->cityId) {
            $q->whereHas('contents', function ($qq) use ($request) {
                $qq->where('city_id', $request->cityId);
            });
        }
        // District join skipped: no district_id found in characteristics model

        // Type/category/purpose
        if ($request->categoryId) {
            $q->where('category_id', $request->categoryId);
        }
        if ($request->propertyType) {
            $q->where('type', $request->propertyType);
        }
        if ($request->purpose) {
            $q->where('purpose', $request->purpose);
        }

        // Bedrooms/Bathrooms
        if ($request->bedrooms) {
            $q->where('beds', '>=', $request->bedrooms);
        }
        if ($request->bathrooms) {
            $q->where('bath', '>=', $request->bathrooms);
        }

        // Size/area
        if ($request->areaFrom) {
            $q->where('area', '>=', $request->areaFrom);
        }
        if ($request->areaTo) {
            $q->where('area', '<=', $request->areaTo);
        }
        if ($request->minAreaSqm) {
            $q->where('area', '>=', $request->minAreaSqm);
        }
        if ($request->maxAreaSqm) {
            $q->where('area', '<=', $request->maxAreaSqm);
        }

        // Budget: web uses range; whatsapp may be single value
        if ($request->budgetFrom) {
            $q->where('price', '>=', $request->budgetFrom);
        }
        if ($request->budgetTo) {
            $q->where('price', '<=', $request->budgetTo);
        }
        if ($request->budget && !$request->budgetFrom && !$request->budgetTo) {
            $q->whereBetween('price', [$request->budget * 0.9, $request->budget * 1.1]);
        }

        // Only available
        $q->where(function ($qq) {
            $qq->whereNull('property_status')->orWhere('property_status', 'available');
        });

        return $q;
    }
}


