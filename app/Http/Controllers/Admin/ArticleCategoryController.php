<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Articles\StoreCategoryRequest;
use App\Http\Requests\Admin\Articles\UpdateCategoryRequest;
use App\Domain\AdminArticles\Services\ArticleCategoryService;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Session;

class ArticleCategoryController extends Controller
{
    protected ArticleCategoryService $categoryService;

    public function __construct(ArticleCategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $filters = $request->only(['search']);
        $categories = $this->categoryService->getAllCategories($filters);

        $data = [
            'categories' => $categories,
        ];

        return view('admin.articles.categories.index', $data);
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        return view('admin.articles.categories.create');
    }

    /**
     * Store a newly created category
     */
    public function store(StoreCategoryRequest $request)
    {
        try {
            $this->categoryService->createCategory($request->validated());
            Session::flash('success', 'Category created successfully!');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit($id)
    {
        try {
            $category = $this->categoryService->getCategoryById($id);
            $data = [
                'category' => $category,
            ];
            return view('admin.articles.categories.edit', $data);
        } catch (\Exception $e) {
            Session::flash('error', 'Category not found.');
            return redirect()->route('admin.articles.categories.index');
        }
    }

    /**
     * Update the specified category
     */
    public function update(UpdateCategoryRequest $request, $id)
    {
        try {
            $this->categoryService->updateCategory($id, $request->validated());
            Session::flash('success', 'Category updated successfully!');
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage()
            ], 422);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Request $request, $id)
    {
        try {
            $this->categoryService->deleteCategory($id);
            Session::flash('success', 'Category deleted successfully!');
            return back();
        } catch (BusinessLogicException $e) {
            Session::flash('warning', $e->getMessage());
            return back();
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to delete category.');
            return back();
        }
    }
}
