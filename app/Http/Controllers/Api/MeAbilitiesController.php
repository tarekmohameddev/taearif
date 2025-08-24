<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeAbilitiesController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'error', 'message' => 'Unauthorized'], 401);
        }

        // permissions (tenant-scoped by SetTenantForPermissions middleware)
        $permissions = $user->getAllPermissions()->pluck('name')->unique()->values();

        return response()->json([
            'status' => 'success',
            'data' => [
                'user_id'     => $user->id,
                'account_type'=> $user->account_type ?? 'tenant',
                'roles'       => $user->getRoleNames()->values(),
                'abilities'   => $permissions, // use this list to drive menus
            ],
        ], 200);
    }
}
