<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\UserFacade;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class UserFacadeController extends Controller
{
    public function index(): JsonResponse
    {
        $facades = Cache::remember('user_facades_list', 3600, function () {
            return UserFacade::select('id', 'name')->get();
        });

        return response()->json([
            'status' => 'success',
            'data' => $facades
        ]);
    }
}
