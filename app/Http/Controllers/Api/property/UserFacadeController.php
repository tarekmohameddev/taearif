<?php

namespace App\Http\Controllers\Api\Property;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User\RealestateManagement\UserFacade;
use Illuminate\Http\JsonResponse;

class UserFacadeController extends Controller
{
    public function index(): JsonResponse
    {
        $facades = UserFacade::select('id', 'name')->get();

        return response()->json([
            'status' => 'success',
            'data' => $facades
        ]);
    }
}
