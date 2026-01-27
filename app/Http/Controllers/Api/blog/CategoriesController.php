<?php

namespace App\Http\Controllers\Api\blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\blog\StoreCategoryRequest;
use App\Http\Requests\Api\blog\UpdateCategoryRequest;
use App\Http\Resources\Api\blog\CategoryResource;
use App\Models\Api\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoriesController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Category::query();
        
        // Include posts if requested
        if ($request->boolean('with_posts')) {
            $userId = $request->user()->id;
            
            // Load posts filtered by user_id and optionally by status
            $query->with(['posts' => function ($q) use ($userId, $request) {
                $q->where('user_id', $userId)
                  ->with('thumbnail');
                
                // Filter by status if provided
                $status = $request->input('post_status');
                if ($status === 'draft') {
                    $q->where('status', 'draft');
                } elseif ($status === 'published') {
                    $q->where('status', 'published');
                }
                
                $q->orderByDesc('published_at')
                  ->orderByDesc('created_at');
            }]);
        }
        
        $categories = $query->orderBy('name')->get();

        return CategoryResource::collection($categories)->response();
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = Category::create($request->only(['name']));

        return (new CategoryResource($category))->response()->setStatusCode(201);
    }

    public function update(UpdateCategoryRequest $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->update($request->only(['name']));

        return (new CategoryResource($category))->response();
    }

    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'message' => 'Category deleted successfully',
        ], 200);
    }
}
