<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportCenter\StoreSupportCenterArticleRequest;
use App\Http\Requests\Admin\SupportCenter\UpdateSupportCenterArticleRequest;
use App\Domain\SupportCenter\Services\SupportCenterArticleService;
use App\Domain\SupportCenter\Services\SupportCenterCategoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Mews\Purifier\Facades\Purifier;

class SupportCenterArticleController extends Controller
{
    protected SupportCenterArticleService $articleService;
    protected SupportCenterCategoryService $categoryService;

    public function __construct(
        SupportCenterArticleService $articleService,
        SupportCenterCategoryService $categoryService
    ) {
        $this->articleService = $articleService;
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of articles
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\SupportCenterArticle::class);

        $filters = $request->only(['status', 'category_id', 'search']);
        $perPage = min((int) $request->input('per_page', 20), 50);
        $articles = $this->articleService->getArticles($filters, $perPage);
        $categories = $this->categoryService->getAllCategories();

        return view('admin.support_center.articles.index', [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a new article
     */
    public function create()
    {
        $this->authorize('create', \App\Models\SupportCenterArticle::class);

        $categories = $this->categoryService->getAllCategories();

        return view('admin.support_center.articles.create', [
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created article
     */
    public function store(StoreSupportCenterArticleRequest $request)
    {
        $this->authorize('create', \App\Models\SupportCenterArticle::class);

        try {
            $data = $request->validated();
            if (isset($data['body'])) {
                $data['body'] = Purifier::clean($data['body']);
            }
            $adminId = Auth::guard('admin')->id();
            $this->articleService->createArticle($data, $adminId);

            Session::flash('success', __('Article created successfully!'));
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified article
     */
    public function show($id)
    {
        try {
            $article = $this->articleService->getArticleById((int) $id);
            $this->authorize('view', $article);

            return view('admin.support_center.articles.show', [
                'article' => $article,
            ]);
        } catch (\Exception $e) {
            Session::flash('error', __('Article not found.'));
            return redirect()->route('admin.support_center.articles.index');
        }
    }

    /**
     * Show the form for editing the specified article
     */
    public function edit($id)
    {
        try {
            $article = $this->articleService->getArticleById((int) $id);
            $this->authorize('update', $article);

            $categories = $this->categoryService->getAllCategories();

            return view('admin.support_center.articles.edit', [
                'article' => $article,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            Session::flash('error', __('Article not found.'));
            return redirect()->route('admin.support_center.articles.index');
        }
    }

    /**
     * Update the specified article
     */
    public function update(UpdateSupportCenterArticleRequest $request, $id)
    {
        try {
            $article = $this->articleService->getArticleById((int) $id);
            $this->authorize('update', $article);

            $data = $request->validated();
            if (isset($data['body'])) {
                $data['body'] = Purifier::clean($data['body']);
            }
            $this->articleService->updateArticle((int) $id, $data);

            Session::flash('success', __('Article updated successfully!'));
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified article
     */
    public function destroy(Request $request, $id)
    {
        try {
            $article = $this->articleService->getArticleById((int) $id);
            $this->authorize('delete', $article);

            $this->articleService->deleteArticle((int) $id);
            Session::flash('success', __('Article deleted successfully!'));
            return back();
        } catch (\Exception $e) {
            Session::flash('error', __('Failed to delete article.'));
            return back();
        }
    }

    /**
     * Upload image for article body (Summernote/editor)
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            $path = $this->articleService->handleImageUpload(
                $request->file('image'),
                'support_center/articles'
            );
            return response()->json([
                'url' => $path ? asset($path) : '',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
