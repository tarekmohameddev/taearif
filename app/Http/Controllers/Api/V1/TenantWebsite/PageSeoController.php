<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TenantWebsite\PageSeoStoreRequest;
use App\Http\Requests\Api\V1\TenantWebsite\PageSeoUpdateRequest;
use App\Models\User;
use App\Services\TenantWebsite\PageSeoService;
use App\Support\TenantWebsite\PageSeoPath;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageSeoController extends Controller
{
    public function __construct(private PageSeoService $pageSeo) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->pageSeo->listPages($this->tenantUser($request)),
            'message' => 'Page SEO entries retrieved successfully',
        ]);
    }

    public function show(Request $request, string $pageKey): JsonResponse
    {
        if (! PageSeoPath::isValidPageKey($pageKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        }

        $page = $this->pageSeo->getPage($this->tenantUser($request), $pageKey);
        if ($page === null) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $page,
            'message' => 'Page SEO retrieved successfully',
        ]);
    }

    public function store(PageSeoStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $pageKey = $this->resolvePageKey($validated);
        if ($pageKey === null) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['page_key' => ['Either page_key or path is required.']],
            ], 422);
        }

        if (! PageSeoPath::isValidPageKey($pageKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => ['page_key' => ['Invalid page key format.']],
            ], 422);
        }

        $page = $this->pageSeo->upsertPage($this->tenantUser($request), $pageKey, $validated);

        return response()->json([
            'success' => true,
            'data' => $page,
            'message' => 'Page SEO saved successfully',
        ]);
    }

    public function update(PageSeoUpdateRequest $request, string $pageKey): JsonResponse
    {
        if (! PageSeoPath::isValidPageKey($pageKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        }

        $page = $this->pageSeo->upsertPage(
            $this->tenantUser($request),
            $pageKey,
            $request->metaChanges()
        );

        return response()->json([
            'success' => true,
            'data' => $page,
            'message' => 'Page SEO updated successfully',
        ]);
    }

    public function destroy(Request $request, string $pageKey): JsonResponse
    {
        if (! PageSeoPath::isValidPageKey($pageKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Not found',
            ], 404);
        }

        $deleted = $this->pageSeo->deletePage($this->tenantUser($request), $pageKey);

        return response()->json([
            'success' => true,
            'data' => ['deleted' => $deleted],
            'message' => $deleted ? 'Page SEO override removed' : 'No override to remove',
        ]);
    }

    protected function tenantUser(Request $request): User
    {
        return $request->user()->tenantOwner();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    protected function resolvePageKey(array $validated): ?string
    {
        if (! empty($validated['page_key'])) {
            return (string) $validated['page_key'];
        }

        if (! empty($validated['path'])) {
            return PageSeoPath::pathToPageKey((string) $validated['path']);
        }

        return null;
    }
}
