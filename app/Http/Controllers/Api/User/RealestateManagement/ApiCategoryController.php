<?php

namespace App\Http\Controllers\Api\User\RealestateManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\RealestateManagement\ApiUserCategory;

class ApiCategoryController extends Controller
{
    public function index()
    {
        $categories = ApiUserCategory::all();

        return response()->json([
            'success' => true,
            'data' => $categories
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:property,project',
            'is_active' => 'required|in:0,1',
            'serial_number' => 'nullable|integer',
            'icon' => 'nullable|string',
        ]);

        $category = ApiUserCategory::storeCategory($validated);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 201);
    }

    public function show($id)
    {
        $category = ApiUserCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $category = ApiUserCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string',
            'type' => 'required|in:property,project',
            'is_active' => 'required|in:0,1',
            'serial_number' => 'nullable|integer',
            'icon' => 'nullable|string',
        ]);

        $category->updateCategory($validated);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 200);
    }

    public function destroy($id)
    {
        $category = ApiUserCategory::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ], 200);
    }
}
