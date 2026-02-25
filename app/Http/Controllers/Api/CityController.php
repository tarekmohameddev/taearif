<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\UserDistrict;

class CityController extends Controller
{
    /**
     * GET /api/user/cities?country_id=1
     * Returns distinct cities from user_districts (table: user_districts).
     * Optional country_id filters via city relation when present.
     */
    public function index(Request $request)
    {
        $countryId = $request->query('country_id');

        $cities = UserDistrict::query()
            ->whereNotNull('city_id')
            ->whereNotNull('city_name_ar')
            ->when($countryId, function ($query) use ($countryId) {
                $query->whereHas('city', function ($q) use ($countryId) {
                    $q->where('country_id', $countryId);
                });
            })
            ->select('city_id as id', 'city_name_ar as name_ar', 'city_name_en as name_en')
            ->distinct()
            ->orderBy('city_name_ar')
            ->get();

        return response()->json(['data' => $cities]);
    }
}
