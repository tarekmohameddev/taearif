<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\AdminArticles\Services\ArticleCategoryService;
use App\Domain\AdminArticles\Services\ArticleService;
use App\Http\Resources\Api\Articles\CategoryResource;
use App\Http\Resources\Api\Articles\ArticleListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminArticleCategoryController extends Controller
{
    protected ArticleCategoryService $categoryService;
    protected ArticleService $articleService;

    public function __construct(
        ArticleCategoryService $categoryService,
        ArticleService $articleService
    ) {
        $this->categoryService = $categoryService;
        $this->articleService = $articleService;
    }

    /**
     * Get list of categories with published articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();

        // Filter categories that have published articles and add count
        $categoriesWithArticles = $categories->filter(function ($category) {
            $publishedCount = $category->articles()
                ->where('status', 'published')
                ->where(function ($query) {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->count();

            // Add published articles count to category
            $category->published_articles_count = $publishedCount;

            return $publishedCount > 0;
        });

        return CategoryResource::collection($categoriesWithArticles)->response();
    }

    /**
     * Get articles for a specific category by slug
     *
     * @param Request $request
     * @param string $slug
     * @return JsonResponse
     */
    public function articles(Request $request, string $slug): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategories();
            $category = $categories->firstWhere('slug', $slug);

            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            $filters = [
                'published_only' => true,
                'category_id' => $category->id,
                'status' => $request->input('status'),
                'search' => $request->input('search'),
                'order_by' => $request->input('order_by', 'published_at'),
                'order_dir' => $request->input('order_dir', 'desc'),
            ];

            $perPage = min((int) $request->input('per_page', 20), 50);
            $articles = $this->articleService->getArticles($filters, $perPage);

            return response()->json([
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ],
                'data' => ArticleListResource::collection($articles),
                'pagination' => [
                    'per_page' => $articles->perPage(),
                    'current_page' => $articles->currentPage(),
                    'from' => $articles->firstItem(),
                    'to' => $articles->lastItem(),
                    'total' => $articles->total(),
                    'last_page' => $articles->lastPage(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Category not found'
            ], 404);
        }
    }
}
