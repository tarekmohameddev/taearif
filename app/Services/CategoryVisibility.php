<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
// use App\Models\Api\ApiUserCategory;
use App\Models\User\RealestateManagement\ApiUserCategory;

use App\Models\Api\ApiUserCategorySetting;

class CategoryVisibility
{
    public function forTenant(int $tenantId, Request $request, bool $showEvenIfEmpty)
    {
        $activeIds = ApiUserCategorySetting::where('user_id', $tenantId)
            ->where('is_active', 1)
            ->pluck('category_id');

        $query = ApiUserCategory::whereIn('id', $activeIds)
            ->where('is_active', 1)
            ->when(
                $request->filled('type') &&
                in_array($request->type, ['commercial', 'residential']),
                fn ($q) => $q->where('type', $request->type)
            );

        if (! $showEvenIfEmpty) {
            $query->whereHas('properties',
                fn ($q) => $q->where('user_id', $tenantId));
        }

        return $query->get();
    }
}

