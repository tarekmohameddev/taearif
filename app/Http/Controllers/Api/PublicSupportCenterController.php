<?php

namespace App\Http\Controllers\Api;

use App\Http\Responses\ErrorResponse;
use App\Domain\SupportCenter\Services\SupportCenterCategoryService;
use App\Domain\SupportCenter\Services\SupportCenterArticleService;
use App\Http\Resources\Api\SupportCenter\SupportCenterCategoryResource;
use App\Http\Resources\Api\SupportCenter\SupportCenterArticleListResource;
use App\Http\Resources\Api\SupportCenter\SupportCenterArticleDetailResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicSupportCenterController extends BaseApiController
{
    protected SupportCenterCategoryService $categoryService;
    protected SupportCenterArticleService $articleService;

    public function __construct(
        SupportCenterCategoryService $categoryService,
        SupportCenterArticleService $articleService
    ) {
        $this->categoryService = $categoryService;
        $this->articleService = $articleService;
    }

    /**
     * List categories that have at least one published article.
     */
    public function categories(Request $request): JsonResponse
    {
        $categories = $this->categoryService->getAllCategories();

        $categoriesWithArticles = $categories->filter(function ($category) {
            $publishedCount = $category->articles()
                ->where('status', 'published')
                ->where(function ($query) {
                    $query->whereNull('published_at')
                        ->orWhere('published_at', '<=', now());
                })
                ->count();

            $category->published_articles_count = $publishedCount;

            return $publishedCount > 0;
        });

        $resolved = SupportCenterCategoryResource::collection($categoriesWithArticles)->resolve();
        $categoriesData = $resolved['data'] ?? $resolved;

        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categoriesData,
            ],
        ]);
    }

    /**
     * Paginated articles for a category by slug.
     */
    public function categoryArticles(Request $request, string $slug): JsonResponse
    {
        $category = $this->categoryService->getCategoryBySlug($slug);

        if (!$category) {
            return ErrorResponse::notFound('Category');
        }

        $filters = [
            'published_only' => true,
            'category_id' => $category->id,
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'published_at'),
            'order_dir' => $request->input('order_dir', 'desc'),
        ];

        $perPage = min((int) $request->input('per_page', 20), 50);
        $articles = $this->articleService->getArticles($filters, $perPage);

        $articlesList = SupportCenterArticleListResource::collection($articles)->resolve();
        $articlesData = $articlesList['data'] ?? $articlesList;
        $pagination = [
            'per_page' => $articles->perPage(),
            'current_page' => $articles->currentPage(),
            'from' => $articles->firstItem(),
            'to' => $articles->lastItem(),
            'total' => $articles->total(),
            'last_page' => $articles->lastPage(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'category' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'short_description' => $category->short_description,
                    'icon_image' => $category->icon_image ? asset($category->icon_image) : null,
                ],
                'articles' => $articlesData,
            ],
            'meta' => [
                'pagination' => $pagination,
            ],
        ]);
    }

    /**
     * List published articles with optional category filter and search.
     */
    public function articles(Request $request): JsonResponse
    {
        $categoryIds = $this->parseCategoryIds($request);
        $filters = [
            'published_only' => true,
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'published_at'),
            'order_dir' => $request->input('order_dir', 'desc'),
        ];
        if (!empty($categoryIds)) {
            $filters['category_ids'] = $categoryIds;
        }

        $perPage = min((int) $request->input('per_page', 20), 50);
        $articles = $this->articleService->getArticles($filters, $perPage);

        $articlesList = SupportCenterArticleListResource::collection($articles)->resolve();
        $articlesData = $articlesList['data'] ?? $articlesList;
        $pagination = [
            'per_page' => $articles->perPage(),
            'current_page' => $articles->currentPage(),
            'from' => $articles->firstItem(),
            'to' => $articles->lastItem(),
            'total' => $articles->total(),
            'last_page' => $articles->lastPage(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => [
                'articles' => $articlesData,
            ],
            'meta' => [
                'pagination' => $pagination,
            ],
        ]);
    }

    /**
     * Single published article by slug.
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $article = $this->articleService->getArticleBySlug($slug);

            if ($article->status !== \App\Enums\ArticleStatus::PUBLISHED) {
                return ErrorResponse::notFound('Article');
            }

            if ($article->published_at && $article->published_at->isFuture()) {
                return ErrorResponse::notFound('Article');
            }

            $articleData = (new SupportCenterArticleDetailResource($article))->resolve();

            return response()->json([
                'status' => 'success',
                'data' => [
                    'article' => $articleData,
                ],
            ]);
        } catch (\Exception $e) {
            return ErrorResponse::notFound('Article');
        }
    }

    /**
     * Parse category filter from request.
     *
     * @return int[]
     */
    private function parseCategoryIds(Request $request): array
    {
        if ($request->has('categories')) {
            $raw = $request->input('categories');
            if (is_array($raw)) {
                return array_values(array_filter(array_map('intval', $raw)));
            }
            $ids = array_map('intval', array_filter(explode(',', (string) $raw)));
            return array_values(array_filter($ids));
        }
        if ($request->filled('category')) {
            $id = (int) $request->input('category');
            return $id > 0 ? [$id] : [];
        }
        return [];
    }
}
