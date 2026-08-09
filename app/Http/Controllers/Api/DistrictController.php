<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User\UserDistrict;
use App\Support\LocationLookupCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DistrictController extends Controller
{
    /**
     * GET /api/districts
     * GET /api/districts?city_id=1
     *
     * Public lookup of rows from user_districts. city_id is optional.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'city_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $cityId = $request->filled('city_id') ? (int) $request->input('city_id') : null;

        $districts = Cache::remember(
            LocationLookupCache::key('districts', $cityId),
            LocationLookupCache::TTL_SECONDS,
            function () use ($cityId) {
                return UserDistrict::query()
                    ->when($cityId !== null, function ($query) use ($cityId) {
                        $query->where('city_id', $cityId);
                    })
                    ->orderBy('name_ar')
                    ->get()
                    ->toArray();
            }
        );

        return response()->json(['data' => $districts]);
    }
}
