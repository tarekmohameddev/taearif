<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\UserDistrict;

class DistrictController extends Controller
{
    //
    public function index(Request $request)
    {
        $cityId = $request->query('city_id');

        $districts = UserDistrict::when($cityId, function ($query) use ($cityId) {
            $query->where('city_id', $cityId);
        })->get();

        return response()->json(['data' => $districts]);
    }
}
