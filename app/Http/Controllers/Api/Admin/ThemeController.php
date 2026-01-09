<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\BaseController;
use App\Http\Resources\Admin\ThemeResource;
use App\Http\Resources\Admin\ThemeCollection;
use App\Domain\Themes\Services\ThemeService;
use App\Models\Api\ApiThemeSettings;
use App\Exceptions\ResourceNotFoundException;
use App\Exceptions\BusinessLogicException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Throwable;

class ThemeController extends BaseController
{
    protected ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    /**
     * Get all themes with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'category' => $request->input('category'),
                'is_enabled' => $request->input('is_enabled'),
                'is_free' => $request->input('is_free'),
            ];

            $themes = ApiThemeSettings::query();

            if (isset($filters['category']) && $filters['category'] !== 'all') {
                $themes->where('category', $filters['category']);
            }

            if (isset($filters['is_enabled'])) {
                $themes->where('is_enabled', $filters['is_enabled']);
            }

            if (isset($filters['is_free']) !== null) {
                $themes->where('is_free', $filters['is_free']);
            }

            $themes = $themes->orderBy('is_free', 'desc')->orderBy('name')->get();

            return $this->successResponse(
                ThemeCollection::make($themes),
                'Themes retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve themes.');
        }
    }

    /**
     * Create new theme
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'theme_id' => 'required|string|unique:api_themes_settings,theme_id|max:50',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'thumbnail' => 'required|string|max:500',
                'category' => 'nullable|string|max:100',
                'is_free' => 'boolean',
                'is_enabled' => 'boolean',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'popular' => 'boolean',
            ]);

            // If setting as free, remove price
            if (isset($validated['is_free']) && $validated['is_free']) {
                $validated['price'] = null;
            }

            $theme = ApiThemeSettings::create($validated);

            return $this->successResponse(
                new ThemeResource($theme),
                'Theme created successfully',
                Response::HTTP_CREATED
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to create theme.');
        }
    }

    /**
     * Get theme details
     */
    public function show(string $themeId): JsonResponse
    {
        try {
            $theme = $this->themeService->getThemeById($themeId);

            return $this->successResponse(
                new ThemeResource($theme),
                'Theme details retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve theme details.');
        }
    }

    /**
     * Update theme
     */
    public function update(Request $request, string $themeId): JsonResponse
    {
        try {
            $theme = $this->themeService->getThemeById($themeId);

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'thumbnail' => 'sometimes|string|max:500',
                'category' => 'nullable|string|max:100',
                'is_free' => 'boolean',
                'is_enabled' => 'boolean',
                'price' => 'nullable|numeric|min:0',
                'currency' => 'nullable|string|size:3',
                'popular' => 'boolean',
            ]);

            // If setting as free, remove price
            if (isset($validated['is_free']) && $validated['is_free']) {
                $validated['price'] = null;
            }

            $theme->update($validated);

            return $this->successResponse(
                new ThemeResource($theme->fresh()),
                'Theme updated successfully'
            );
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update theme.');
        }
    }

    /**
     * Delete theme
     */
    public function destroy(string $themeId): JsonResponse
    {
        try {
            $theme = $this->themeService->getThemeById($themeId);

            // Check if theme has purchases
            if ($theme->userThemes()->exists()) {
                return $this->errorResponse(
                    'Cannot delete theme with existing purchases',
                    'THEME_HAS_PURCHASES',
                    Response::HTTP_BAD_REQUEST
                );
            }

            $themeName = $theme->name;
            $theme->delete();

            return $this->successResponse(
                [
                    'theme_id' => $themeId,
                    'theme_name' => $themeName,
                ],
                'Theme deleted successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to delete theme.');
        }
    }

    /**
     * Toggle theme enabled status
     */
    public function toggleEnabled(string $themeId): JsonResponse
    {
        try {
            $theme = $this->themeService->getThemeById($themeId);
            
            $theme->update(['is_enabled' => !$theme->is_enabled]);

            $status = $theme->is_enabled ? 'enabled' : 'disabled';

            return $this->successResponse(
                new ThemeResource($theme->fresh()),
                "Theme has been {$status} successfully"
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to update theme status.');
        }
    }

    /**
     * Get theme categories
     */
    public function categories(): JsonResponse
    {
        try {
            $categories = ApiThemeSettings::select('category')
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category')
                ->map(function ($category) {
                    return ['id' => $category, 'name' => $category];
                })
                ->values();

            return $this->successResponse(
                $categories,
                'Categories retrieved successfully'
            );
        } catch (Throwable $e) {
            return $this->handleException($e, 'Failed to retrieve categories.');
        }
    }

    protected function handleException(Throwable $e, string $fallbackMessage): JsonResponse
    {
        if ($e instanceof ValidationException) {
            throw $e;
        }

        if ($e instanceof ResourceNotFoundException) {
            return $this->errorResponse(
                $e->getMessage(),
                'NOT_FOUND',
                Response::HTTP_NOT_FOUND
            );
        }

        if ($e instanceof BusinessLogicException) {
            $status = $e->getCode() ?: Response::HTTP_BAD_REQUEST;
            $errorCode = method_exists($e, 'getErrorCode') ? $e->getErrorCode() : 'BUSINESS_LOGIC_ERROR';

            return $this->errorResponse(
                $e->getMessage(),
                $errorCode,
                $status,
                ['error_code' => $errorCode]
            );
        }

        return $this->errorResponse(
            $fallbackMessage,
            'THEME_ERROR',
            Response::HTTP_INTERNAL_SERVER_ERROR,
            ['error' => $e->getMessage()]
        );
    }
}
