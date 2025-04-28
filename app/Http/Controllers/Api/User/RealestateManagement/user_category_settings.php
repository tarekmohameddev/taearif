<?php

namespace App\Http\Controllers\Api\User\RealestateManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\RealestateManagement\ApiUserCategory;

class ApiCategoryController extends Controller
{
    // public function getCategories()
    // {
    //     $user = Auth::user();
    //     $categories = UserCategory::with(['userSettings' => function ($query) use ($user) {
    //         $query->where('user_id', $user->id);
    //     }])->get();

    //     $categories = $categories->map(function ($category) use ($user) {
    //         $setting = $category->userSettings->first();
    //         $category->category_is_active = $setting ? $setting->is_active : $category->is_active;
    //         return $category;
    //     });

    //     return response()->json($categories);
    // }

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

    public function toggleVisibility(Request $request, $categoryId)
    {
        $user = Auth::user();
        $category = UserCategory::findOrFail($categoryId);

        $setting = UserCategorySetting::firstOrCreate(
            ['user_id' => $user->id, 'category_id' => $categoryId],
            ['is_active' => $category->is_active]
        );

        $setting->is_active = $request->input('is_active', 0);
        $setting->save();

        return response()->json(['message' => 'Category visibility updated']);
    }

}
