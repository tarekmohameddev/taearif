<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SupportCenter\StoreSupportCenterCategoryRequest;
use App\Http\Requests\Admin\SupportCenter\UpdateSupportCenterCategoryRequest;
use App\Domain\SupportCenter\Services\SupportCenterCategoryService;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SupportCenterCategoryController extends Controller
{
    protected SupportCenterCategoryService $categoryService;

    public function __construct(SupportCenterCategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of categories
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', \App\Models\SupportCenterCategory::class);

        $filters = $request->only(['search']);
        $categories = $this->categoryService->getAllCategories($filters);

        return view('admin.support_center.categories.index', [
            'categories' => $categories,
        ]);
    }

    /**
     * Show the form for creating a new category
     */
    public function create()
    {
        $this->authorize('create', \App\Models\SupportCenterCategory::class);

        return view('admin.support_center.categories.create');
    }

    /**
     * Store a newly created category
     */
    public function store(StoreSupportCenterCategoryRequest $request)
    {
        $this->authorize('create', \App\Models\SupportCenterCategory::class);

        try {
            $this->categoryService->createCategory($request->validated());
            Session::flash('success', __('Category created successfully!'));
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Show the form for editing the specified category
     */
    public function edit($id)
    {
        try {
            $category = $this->categoryService->getCategoryById((int) $id);
            $this->authorize('update', $category);

            return view('admin.support_center.categories.edit', [
                'category' => $category,
            ]);
        } catch (\Exception $e) {
            Session::flash('error', __('Category not found.'));
            return redirect()->route('admin.support_center.categories.index');
        }
    }

    /**
     * Update the specified category
     */
    public function update(UpdateSupportCenterCategoryRequest $request, $id)
    {
        try {
            $category = $this->categoryService->getCategoryById((int) $id);
            $this->authorize('update', $category);

            $this->categoryService->updateCategory((int) $id, $request->validated());
            Session::flash('success', __('Category updated successfully!'));
            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            return response()->json([
                'error' => true,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified category
     */
    public function destroy(Request $request, $id)
    {
        try {
            $category = $this->categoryService->getCategoryById((int) $id);
            $this->authorize('delete', $category);

            $this->categoryService->deleteCategory((int) $id);
            Session::flash('success', __('Category deleted successfully!'));
            return back();
        } catch (BusinessLogicException $e) {
            Session::flash('warning', $e->getMessage());
            return back();
        } catch (\Exception $e) {
            Session::flash('error', __('Failed to delete category.'));
            return back();
        }
    }
}
