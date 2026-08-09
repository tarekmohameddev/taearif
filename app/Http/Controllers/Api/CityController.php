<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User\UserCity;
use App\Support\LocationLookupCache;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CityController extends Controller
{
    /**
     * GET /api/cities
     * GET /api/cities?country_id=1
     *
     * Returns cities from user_cities. country_id is optional.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:user_cities,country_id'],
        ]);

        $countryId = $request->filled('country_id') ? (int) $request->input('country_id') : null;

        $cities = Cache::remember(
            LocationLookupCache::key('cities', $countryId),
            LocationLookupCache::TTL_SECONDS,
            function () use ($countryId) {
                return UserCity::query()
                    ->when($countryId !== null, function ($query) use ($countryId) {
                        $query->where('country_id', $countryId);
                    })
                    ->select('id', 'name_ar', 'name_en')
                    ->orderBy('name_ar')
                    ->get()
                    ->toArray();
            }
        );

        return response()->json(['data' => $cities]);
    }
}
