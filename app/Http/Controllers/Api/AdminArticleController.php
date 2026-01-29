<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Domain\AdminArticles\Services\ArticleService;
use App\Http\Resources\Api\Articles\ArticleResource;
use App\Http\Resources\Api\Articles\ArticleListResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdminArticleController extends Controller
{
    protected ArticleService $articleService;

    public function __construct(ArticleService $articleService)
    {
        $this->articleService = $articleService;
    }

    /**
     * Get list of published articles
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'published_only' => true, // Only published articles for public API
            'status' => $request->input('status'),
            'category_id' => $request->input('category'),
            'search' => $request->input('search'),
            'order_by' => $request->input('order_by', 'published_at'),
            'order_dir' => $request->input('order_dir', 'desc'),
        ];

        $perPage = min((int) $request->input('per_page', 20), 50);
        $articles = $this->articleService->getArticles($filters, $perPage);

        return response()->json([
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
    }

    /**
     * Get single article by slug
     *
     * @param string $slug
     * @return JsonResponse
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $article = $this->articleService->getArticleBySlug($slug);

            // Only return published articles for public API
            if ($article->status !== \App\Enums\ArticleStatus::PUBLISHED) {
                return response()->json([
                    'message' => 'Article not found'
                ], 404);
            }

            // Check if scheduled and not yet published
            if ($article->published_at && $article->published_at->isFuture()) {
                return response()->json([
                    'message' => 'Article not found'
                ], 404);
            }

            return (new ArticleResource($article))->response();
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Article not found'
            ], 404);
        }
    }
}
