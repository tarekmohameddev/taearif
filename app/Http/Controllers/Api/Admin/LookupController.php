<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LookupController extends BaseController
{
    /**
     * GET /api/v1/admin/lookups/plans
     * Returns available packages (id, title, slug) for plan selection
     */
    public function plans(Request $request): JsonResponse
    {
        $items = DB::table('packages')
            ->select('id', 'title', 'slug')
            ->where('is_active', 1) // adjust to status=1 if preferred
            ->orderBy('title')
            ->get();

        return $this->successResponse(
            ['items' => $items, 'total' => $items->count()],
            'Plans retrieved successfully.'
        );
    }
    /**
     * GET /api/v1/admin/lookups/cities
     * Returns distinct list of cities from user_districts
     * items: [{ id: city_id, name: city_name_ar }]
     */
    public function cities(Request $request): JsonResponse
    {
        $items = DB::table('user_districts')
            ->whereNotNull('city_id')
            ->whereNotNull('city_name_ar')
            ->select('city_id as id', 'city_name_ar as name')
            ->distinct()
            ->orderBy('city_name_ar')
            ->get();

        return $this->successResponse(
            ['items' => $items, 'total' => $items->count()],
            'Cities retrieved successfully.'
        );
    }

    /**
     * GET /api/v1/admin/lookups/districts?city_id=ID
     * Returns districts filtered by city_id
     * items: [{ id, name: name_ar }]
     */
    public function districts(Request $request): JsonResponse
    {
        $request->validate([
            'city_id' => ['required', 'integer', 'exists:user_districts,city_id'],
        ]);

        $items = DB::table('user_districts')
            ->where('city_id', (int) $request->query('city_id'))
            ->whereNotNull('id')
            ->whereNotNull('name_ar')
            ->select('id', 'name_ar as name')
            ->orderBy('name_ar')
            ->get();

        return $this->successResponse(
            ['items' => $items, 'total' => $items->count()],
            'Districts retrieved successfully.'
        );
    }
}


