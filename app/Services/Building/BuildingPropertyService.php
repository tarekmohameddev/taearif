<?php

namespace App\Services\Building;

use App\Models\Building;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BuildingPropertyService
{
    /**
     * @return array{building: Building, properties: LengthAwarePaginator}
     */
    public function listForBuilding(
        int $tenantOwnerId,
        int $buildingId,
        int $perPage = 25,
        ?string $search = null,
    ): array {
        $building = $this->resolveBuildingForTenant($tenantOwnerId, $buildingId);
        $languageId = $this->resolveArabicLanguageId($tenantOwnerId);

        $query = $this->propertyQueryForTenant($tenantOwnerId, $languageId)
            ->where('building_id', $buildingId)
            ->orderByDesc('id');

        if ($search !== null && $search !== '') {
            $term = '%' . $search . '%';
            $query->whereHas('contents', function ($q) use ($term) {
                $q->where('title', 'like', $term)
                    ->orWhere('address', 'like', $term);
            });
        }

        return [
            'building' => $building,
            'properties' => $query->paginate($perPage),
        ];
    }

    public function attach(int $tenantOwnerId, int $buildingId, int $propertyId): Property
    {
        $this->resolveBuildingForTenant($tenantOwnerId, $buildingId);
        $property = $this->resolvePropertyForTenant($tenantOwnerId, $propertyId);

        if ((int) $property->building_id !== $buildingId) {
            $property->update(['building_id' => $buildingId]);
        }

        $languageId = $this->resolveArabicLanguageId($tenantOwnerId);

        return $this->propertyQueryForTenant($tenantOwnerId, $languageId)
            ->where('id', $propertyId)
            ->firstOrFail();
    }

    public function resolvePropertyForTenant(int $tenantOwnerId, int $propertyId): Property
    {
        $property = Property::query()
            ->whereIn('user_id', $this->allowedUserIds($tenantOwnerId))
            ->where('id', $propertyId)
            ->first();

        if (!$property) {
            throw new ModelNotFoundException('Property not found for this tenant.');
        }

        return $property;
    }

    public function resolveBuildingForTenant(int $tenantOwnerId, int $buildingId): Building
    {
        $building = Building::query()
            ->whereIn('user_id', $this->allowedUserIds($tenantOwnerId))
            ->where('id', $buildingId)
            ->first();

        if (!$building) {
            throw new ModelNotFoundException('Building not found for this tenant.');
        }

        return $building;
    }

    private function resolveArabicLanguageId(int $tenantOwnerId): ?int
    {
        $language = Language::query()
            ->where('user_id', $tenantOwnerId)
            ->where('code', 'ar')
            ->first();

        return $language?->id;
    }

    private function propertyQueryForTenant(int $tenantOwnerId, ?int $languageId): Builder
    {
        return Property::query()
            ->select(
                'id',
                'building_id',
                'price',
                'pricePerMeter',
                'area',
                'beds',
                'bath',
                'status',
                'property_status',
                'listing_purpose',
                'unit_status',
                'publish_status',
                'featured',
                'featured_image',
                'created_at',
            )
            ->where('completion_status', 'complete')
            ->whereIn('user_id', $this->allowedUserIds($tenantOwnerId))
            ->with([
                'contents' => function ($q) use ($languageId) {
                    $q->select('id', 'property_id', 'language_id', 'title', 'slug', 'address', 'city_id', 'state_id', 'country_id');
                    if ($languageId) {
                        $q->where('language_id', $languageId);
                    }
                },
                'contents.district:id,name_ar,name_en,city_id,city_name_ar,city_name_en',
                'contents.state:id,name',
                'contents.country:id,name',
            ]);
    }

    /**
     * @return list<int>
     */
    private function allowedUserIds(int $tenantOwnerId): array
    {
        $allowedUserIds = [$tenantOwnerId];

        try {
            $employeeIds = User::query()
                ->where('tenant_id', $tenantOwnerId)
                ->pluck('id')
                ->all();
            $allowedUserIds = array_values(array_unique(array_merge($allowedUserIds, $employeeIds)));
        } catch (\Throwable $e) {
            // Fall back to tenant owner only.
        }

        return $allowedUserIds;
    }
}
