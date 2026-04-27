<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DistrictController extends Controller
{
    //
    public function index(Request $request)
    {
        $cityId = $request->query('city_id');

        $districts = DB::table('user_districts')
            ->when($cityId, function ($query) use ($cityId) {
                $query->where('city_id', $cityId);
            })
            ->orderBy('id')
            ->get();

        return response()->json(['data' => $districts]);
    }
}
