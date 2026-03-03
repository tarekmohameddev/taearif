<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\UserFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class UserFacadeController extends Controller
{
    public function index(): JsonResponse
    {
        // Cache as plain array to avoid Eloquent serialization overhead
        // Reduced TTL from 1 hour to 5 minutes to reduce stale data risk
        // Observer now handles automatic invalidation on facade changes
        $facades = Cache::remember('api_property_facades_list', 300, function () {
            return UserFacade::select('id', 'name')->get()->toArray();
        });

        return response()->json([
            'status' => 'success',
            'data' => $facades
        ]);
    }
}
