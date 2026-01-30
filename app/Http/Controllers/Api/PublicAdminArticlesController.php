<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Responses\ErrorResponse;
use App\Domain\AdminArticles\Services\ArticleCategoryService;
use App\Domain\AdminArticles\Services\ArticleService;
use App\Http\Resources\Api\Articles\ArticleResource;
use App\Http\Resources\Api\Articles\ArticleListResource;
use App\Http\Resources\Api\Articles\CategoryResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PublicAdminArticlesController extends BaseApiController
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
     * List categories that have published articles (admin_articles_categories).
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

        $resolved = CategoryResource::collection($categoriesWithArticles)->resolve();
        $categories = $resolved['data'] ?? $resolved;
        return response()->json([
            'status' => 'success',
            'data' => [
                'categories' => $categories,
            ],
        ]);
    }

    /**
     * Articles for a specific category by slug.
     */
    public function categoryArticles(Request $request, string $slug): JsonResponse
    {
        try {
            $categories = $this->categoryService->getAllCategories();
            $category = $categories->firstWhere('slug', $slug);

            if (!$category) {
                return ErrorResponse::notFound('Category');
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

            $articlesList = ArticleListResource::collection($articles)->resolve()['data'] ?? ArticleListResource::collection($articles)->resolve();
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
                        'description' => $category->description,
                    ],
                    'articles' => $articlesList,
                ],
                'meta' => [
                    'pagination' => $pagination,
                ],
            ]);
        } catch (\Exception $e) {
            return ErrorResponse::notFound('Category');
        }
    }

    /**
     * List published articles (admin_articles).
     */
    public function articles(Request $request): JsonResponse
    {
        $filters = [
            'published_only' => true,
            'status' => $request->input('status'),
            'category_id' => $request->input('category'),
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'published_at'),
            'order_dir' => $request->input('order_dir', 'desc'),
        ];

        $perPage = min((int) $request->input('per_page', 20), 50);
        $articles = $this->articleService->getArticles($filters, $perPage);

        $articlesList = ArticleListResource::collection($articles)->resolve()['data'] ?? ArticleListResource::collection($articles)->resolve();
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
                'articles' => $articlesList,
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

            $articleData = (new ArticleResource($article))->resolve();
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
}
