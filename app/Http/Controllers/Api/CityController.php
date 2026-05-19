<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User\UserDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    /**
     * GET /api/cities
     * GET /api/cities?country_id=1
     *
     * Returns distinct cities from user_districts. country_id is optional.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:user_cities,country_id'],
        ]);

        $cities = UserDistrict::query()
            ->whereNotNull('city_id')
            ->whereNotNull('city_name_ar')
            ->when($request->filled('country_id'), function ($query) use ($request) {
                $query->whereHas('city', function ($q) use ($request) {
                    $q->where('country_id', (int) $request->input('country_id'));
                });
            })
            ->select('city_id as id', 'city_name_ar as name_ar', 'city_name_en as name_en')
            ->distinct()
            ->orderBy('city_name_ar')
            ->get();

        return response()->json(['data' => $cities]);
    }
}
