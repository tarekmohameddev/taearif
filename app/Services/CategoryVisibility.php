<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\Api\ApiUserCategorySetting;

class CategoryVisibility
{
    /**
     * @param bool $showEvenIfEmpty
     * @param int|null $languageId
     */
    public function forTenant(int $tenantId, Request $request, bool $showEvenIfEmpty, ?int $languageId = null)
    {
        $activeIds = ApiUserCategorySetting::where('user_id', $tenantId)
            ->where('is_active', 1)
            ->pluck('category_id');

        $query = ApiUserCategory::whereIn('id', $activeIds)
            ->where('is_active', 1)
            ->when(
                $request->filled('type') && in_array($request->type, ['commercial', 'residential']),
                fn ($q) => $q->where('type', $request->type)
            );

        if (! $showEvenIfEmpty) {
            $query->whereHas('properties', function ($q) use ($tenantId, $languageId) {
                $q->where('user_id', $tenantId)
                  ->where('status', 1) // <-- only active properties
                  ->when($languageId, function ($qq) use ($languageId) {
                      $qq->whereHas('contents', fn($c) => $c->where('language_id', $languageId));
                  });
            });
        }

        return $query->get();
    }
}
