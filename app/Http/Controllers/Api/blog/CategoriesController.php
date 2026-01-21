<?php

namespace App\Http\Controllers\Api\Blog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Blog\StoreCategoryRequest;
use App\Http\Requests\Api\Blog\UpdateCategoryRequest;
use App\Http\Resources\Api\Blog\CategoryResource;
use App\Models\Api\Category;
use Illuminate\Http\JsonResponse;

class CategoriesController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = Category::orderBy('name')->get();

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
