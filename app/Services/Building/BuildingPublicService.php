<?php

namespace App\Services\Building;

use App\Models\Building;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BuildingPublicService
{
    public function listPublished(int $tenantUserId, int $perPage = 15): LengthAwarePaginator
    {
        return Building::query()
            ->where('user_id', $tenantUserId)
            ->where('is_archived', false)
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function findBySlug(int $tenantUserId, string $slug): ?Building
    {
        return Building::query()
            ->where('user_id', $tenantUserId)
            ->where('slug', $slug)
            ->where('is_archived', false)
            ->first();
    }

    public function publishedUnitsQuery(Building $building)
    {
        $query = Property::query()
            ->where('building_id', $building->id)
            ->where('user_id', $building->user_id)
            ->with(['contents', 'galleryImages']);

        return $query->publishedForPublic();
    }
}
