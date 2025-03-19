<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\Region;

class RegionController extends Controller
{
    public function index()
    {
        return response()->json(Region::with('governorates')->get());
    }

    public function show(Region $region)
    {
        return response()->json($region->load('governorates'));
    }
}
