<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Articles\StoreArticleRequest;
use App\Http\Requests\Admin\Articles\UpdateArticleRequest;
use App\Domain\AdminArticles\Services\ArticleService;
use App\Domain\AdminArticles\Services\ArticleCategoryService;
use App\Services\ImageUploadService;
use App\Models\AdminArticleCategory;
use Illuminate\Http\Request;
use Session;
use Auth;

class ArticleController extends Controller
{
    protected ArticleService $articleService;
    protected ArticleCategoryService $categoryService;
    protected ImageUploadService $imageService;

    public function __construct(
        ArticleService $articleService,
        ArticleCategoryService $categoryService,
        ImageUploadService $imageService
    ) {
        $this->articleService = $articleService;
        $this->categoryService = $categoryService;
        $this->imageService = $imageService;
    }

    /**
     * Display a listing of articles
     */
    public function index(Request $request)
    {
        $filters = $request->only(['status', 'category_id', 'search']);
        $perPage = $request->input('per_page', 20);

        $articles = $this->articleService->getArticles($filters, $perPage);
        $categories = $this->categoryService->getAllCategories();

        $data = [
            'articles' => $articles,
            'categories' => $categories,
            'filters' => $filters,
        ];

        return view('admin.articles.articles.index', $data);
    }

    /**
     * Show the form for creating a new article
     */
    public function create()
    {
        $categories = $this->categoryService->getAllCategories();

        $data = [
            'categories' => $categories,
        ];

        return view('admin.articles.articles.create', $data);
    }

    /**
     * Store a newly created article
     */
    public function store(StoreArticleRequest $request)
    {
        try {
            $data = $request->validated();
            $adminId = \Illuminate\Support\Facades\Auth::guard('admin')->id();

            $this->articleService->createArticle($data, $adminId);

            Session::flash('success', 'Article created successfully!');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Display the specified article
     */
    public function show($id)
    {
        try {
            $article = $this->articleService->getArticleById($id);

            $data = [
                'article' => $article,
            ];

            return view('admin.articles.articles.show', $data);
        } catch (\Exception $e) {
            Session::flash('error', 'Article not found.');
            return redirect()->route('admin.articles.index');
        }
    }

    /**
     * Show the form for editing the specified article
     */
    public function edit($id)
    {
        try {
            $article = $this->articleService->getArticleById($id);
            $categories = $this->categoryService->getAllCategories();

            $data = [
                'article' => $article,
                'categories' => $categories,
            ];

            return view('admin.articles.articles.edit', $data);
        } catch (\Exception $e) {
            Session::flash('error', 'Article not found.');
            return redirect()->route('admin.articles.index');
        }
    }

    /**
     * Update the specified article
     */
    public function update(UpdateArticleRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $this->articleService->updateArticle($id, $data);

            Session::flash('success', 'Article updated successfully!');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified article
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->articleService->deleteArticle($id);
            Session::flash('success', 'Article deleted successfully!');
            return back();
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to delete article.');
            return back();
        }
    }

    /**
     * Upload image for Summernote editor
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp|max:5120',
        ]);

        try {
            $path = $this->imageService->uploadArticleImage($request->file('image'));
            return response()->json([
                'url' => asset($path)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 422);
        }
    }
}
