<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User\UserDistrict;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:user_districts,city_id'],
        ]);

        $districts = UserDistrict::query()
            ->when($request->filled('city_id'), function ($query) use ($request) {
                $query->where('city_id', (int) $request->input('city_id'));
            })
            ->orderBy('name_ar')
            ->get();

        return response()->json(['data' => $districts]);
    }
}
