<?php

namespace App\Http\Controllers\Api\User\RealestateManagement;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\Api\ApiUserCategorySetting;
use Illuminate\Support\Facades\Auth;

class ApiCategoryController extends Controller
{

    public function index()
    {
        $user = Auth::user();
        $categories = ApiUserCategorySetting::where('user_id', $user->id)->with('category')->get(['category_id', 'is_active']);
        $allCategories = ApiUserCategory::all();
        $existingCategoryIds = $categories->pluck('category_id')->toArray();
        $missingCategories = $allCategories->whereNotIn('id', $existingCategoryIds);

        foreach ($missingCategories as $category) {
            ApiUserCategorySetting::create([
                'user_id' => $user->id,
                'category_id' => $category->id,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $categories = ApiUserCategorySetting::where('user_id', $user->id)->with('category')->get(['category_id', 'is_active']);

        $formattedCategories = $categories->map(function ($categorySetting) {
            return [
                'id' => $categorySetting->category_id,
                'is_active' => $categorySetting->is_active
            ];
        });

        return response()->json([
            'status' => 'success',
            'categories' => $formattedCategories
        ], 200);
    }

    public function show($id)
    {
        $category = ApiUserCategory::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $category
        ], 200);
    }

    public function update(Request $request)
    {
        $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:api_user_categories,id',
            'categories.*.is_active' => 'required|boolean',
        ]);

        $user = Auth::user();
        $categories = ApiUserCategory::all();

        // get user all categories
        foreach ($categories as $category) {
            // Check if the category setting already exists for the user
            $categorySetting = ApiUserCategorySetting::where('user_id', $user->id)->where('category_id', $category->id)->first();

            // If the category setting doesn't exist, create it with default values
            if (!$categorySetting) {
                ApiUserCategorySetting::create([
                    'user_id' => $user->id,
                    'category_id' => $category->id,
                    'is_active' => true,  // Default to active
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Loop in each category and update the settings
        foreach ($request->categories as $categoryData) {
            // Find category setting for the user and category
            $categorySetting = ApiUserCategorySetting::where('user_id', $user->id)->where('category_id', $categoryData['id'])->first();

            // If the category setting exists, update it
            if ($categorySetting) {
                $categorySetting->is_active = $categoryData['is_active'];
                $categorySetting->save();
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => "Category ID {$categoryData['id']} not found for this user."
                ], 404);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Categories updated successfully.'
        ], 200);
    }


    public function toggleVisibility(Request $request, $categoryId)
    {
        $user = Auth::user();
        $category = ApiUserCategory::findOrFail($categoryId);

        $setting = ApiUserCategorySetting::firstOrCreate(
            ['user_id' => $user->id, 'category_id' => $categoryId],
            ['is_active' => $category->is_active]
        );

        $setting->is_active = $request->input('is_active', 0);
        $setting->save();

        return response()->json(['message' => 'Category visibility updated']);
    }

}
