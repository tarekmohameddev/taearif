<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\UserCity;
class CityController extends Controller
{
    //
    public function index(Request $request)
    {
        $countryId = $request->query('country_id');

        $cities = UserCity::when($countryId, function ($query) use ($countryId) {
            $query->where('country_id', $countryId);
        })->get();

        return response()->json(['data' => $cities]);
    }
}
