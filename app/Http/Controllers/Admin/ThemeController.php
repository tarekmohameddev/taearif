<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Themes\Services\ThemeService;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiThemeSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class ThemeController extends Controller
{
    public function __construct(
        private ThemeService $themeService
    ) {
    }

    /**
     * Display a listing of themes
     */
    public function index(Request $request)
    {
        $filters = [
            'category' => $request->input('category'),
            'is_enabled' => $request->input('is_enabled'),
            'is_free' => $request->input('is_free'),
        ];

        $query = ApiThemeSettings::query();

        if (!empty($filters['category']) && $filters['category'] !== 'all') {
            $query->where('category', $filters['category']);
        }

        if ($request->filled('is_enabled')) {
            $query->where('is_enabled', $filters['is_enabled']);
        }

        if ($request->filled('is_free')) {
            $query->where('is_free', $filters['is_free']);
        }

        $data['themes'] = $query->orderBy('is_free', 'desc')->orderBy('name')->get();
        $data['categories'] = ApiThemeSettings::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('admin.themes.index', $data);
    }

    /**
     * Show the form for creating a new theme
     */
    public function create()
    {
        $data['categories'] = ApiThemeSettings::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('admin.themes.create', $data);
    }

    /**
     * Store a newly created theme
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'theme_id' => 'required|string|unique:api_themes_settings,theme_id|max:50',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'is_free' => 'sometimes|boolean',
            'is_enabled' => 'sometimes|boolean',
            'price' => 'nullable|required_if:is_free,0|numeric|min:0',
            'currency' => 'nullable|required_if:is_free,0|string|size:3',
            'popular' => 'sometimes|boolean',
        ]);

        // Set defaults for checkboxes (hidden fields ensure they're always present)
        $validated['is_free'] = (bool) ($request->input('is_free', 0));
        $validated['is_enabled'] = (bool) ($request->input('is_enabled', 1));
        $validated['popular'] = (bool) ($request->input('popular', 0));
        $validated['currency'] = $validated['currency'] ?? 'SAR';

        // If setting as free, remove price
        if ($validated['is_free']) {
            $validated['price'] = null;
        }

        ApiThemeSettings::create($validated);

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Theme created successfully']);
        }

        Session::flash('success', 'تم إنشاء السمة بنجاح!');
        return redirect()->route('admin.themes.index');
    }

    /**
     * Show the form for editing a theme
     */
    public function edit($themeId)
    {
        $theme = $this->themeService->getThemeById($themeId);
        $data['theme'] = $theme;
        $data['categories'] = ApiThemeSettings::select('category')
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category');

        return view('admin.themes.edit', $data);
    }

    /**
     * Update a theme
     */
    public function update(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:api_themes_settings,theme_id',
        ]);

        $theme = $this->themeService->getThemeById($request->theme_id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'thumbnail' => 'required|string|max:500',
            'category' => 'nullable|string|max:100',
            'is_free' => 'boolean',
            'is_enabled' => 'boolean',
            'price' => 'nullable|required_if:is_free,0|numeric|min:0',
            'currency' => 'nullable|required_if:is_free,0|string|size:3',
            'popular' => 'boolean',
        ]);

        // If setting as free, remove price
        if (isset($validated['is_free']) && $validated['is_free']) {
            $validated['price'] = null;
        }

        $theme->update($validated);

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Theme updated successfully']);
        }

        Session::flash('success', 'تم تحديث السمة بنجاح!');
        return redirect()->route('admin.themes.index');
    }

    /**
     * Delete a theme
     */
    public function delete(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:api_themes_settings,theme_id',
        ]);

        $theme = $this->themeService->getThemeById($request->theme_id);

        // Check if theme has purchases
        if ($theme->userThemes()->exists()) {
            Session::flash('error', 'لا يمكن حذف السمة لأنها تحتوي على مشتريات موجودة');
            return back();
        }

        $theme->delete();

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['status' => 'success', 'message' => 'Theme deleted successfully']);
        }

        Session::flash('success', 'تم حذف السمة بنجاح!');
        return redirect()->route('admin.themes.index');
    }

    /**
     * Toggle theme enabled status
     */
    public function toggleEnabled(Request $request)
    {
        $request->validate([
            'theme_id' => 'required|exists:api_themes_settings,theme_id',
        ]);

        $theme = $this->themeService->getThemeById($request->theme_id);
        $theme->update(['is_enabled' => !$theme->is_enabled]);

        // Handle AJAX requests
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'status' => 'success',
                'message' => $theme->is_enabled ? 'تم تفعيل السمة بنجاح' : 'تم إلغاء تفعيل السمة بنجاح',
                'is_enabled' => $theme->is_enabled,
            ]);
        }

        Session::flash('success', $theme->is_enabled ? 'تم تفعيل السمة بنجاح!' : 'تم إلغاء تفعيل السمة بنجاح!');
        return back();
    }
}
