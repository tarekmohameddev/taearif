<?php

namespace App\Http\Controllers\Api\blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\blog\StoreCategoryRequest;
use App\Http\Requests\Api\blog\UpdateCategoryRequest;
use App\Http\Resources\Api\blog\CategoryResource;
use App\Http\Resources\Api\blog\PostListResource;
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

    public function posts(Request $request, int $id): JsonResponse
    {
        $userId = $request->user()->id;
        
        $category = Category::findOrFail($id);
        
        $query = $category->posts()
            ->where('user_id', $userId)
            ->with('thumbnail');
        
        // Filter by status if provided
        $status = $request->input('status');
        if ($status === 'draft') {
            $query->where('status', 'draft');
        } elseif ($status === 'published') {
            $query->where('status', 'published');
        }
        // If no status, show both draft and published
        
        // Order by published_at for published posts, created_at for drafts
        $query->orderByDesc($status === 'draft' ? 'created_at' : 'published_at')
            ->orderByDesc('created_at');
        
        $perPage = min((int) $request->input('per_page', 15), 50);
        $posts = $query->paginate($perPage);
        
        return response()->json([
            'category' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ],
            'data' => PostListResource::collection($posts),
            'pagination' => [
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'from' => $posts->firstItem(),
                'to' => $posts->lastItem(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }
}
