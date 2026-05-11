<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\TenantWebsite\StaticPageStoreRequest;
use App\Http\Requests\Api\V1\TenantWebsite\StaticPageUpdateRequest;
use App\Models\TenantStaticPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaticPageController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $ids = TenantStaticPage::DASHBOARD_PAGE_IDS;
        $rows = TenantStaticPage::query()
            ->where('user_id', $user->id)
            ->whereIn('page_id', $ids)
            ->get()
            ->keyBy('page_id');

        $pages = [];
        foreach ($ids as $pageId) {
            $row = $rows->get($pageId);
            $pages[] = [
                'page_id' => $pageId,
                'components' => $row?->components ?? [],
                'url' => $row?->url,
            ];
        }

        return response()->json([
            'status' => 'success',
            'data' => ['pages' => $pages],
        ]);
    }

    public function show(Request $request, string $pageId): JsonResponse
    {
        if (! $this->isAllowedPageId($pageId)) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $user = $request->user();
        $row = TenantStaticPage::query()
            ->where('user_id', $user->id)
            ->where('page_id', $pageId)
            ->first();

        return response()->json([
            'status' => 'success',
            'data' => [
                'page' => [
                    'page_id' => $pageId,
                    'components' => $row?->components ?? [],
                    'url' => $row?->url,
                ],
            ],
        ]);
    }

    public function store(StaticPageStoreRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $pageId = $validated['page_id'];
        $payload = ['components' => $validated['components']];
        if (array_key_exists('url', $validated)) {
            $payload['url'] = $validated['url'];
        }
        $normalized = TenantStaticPage::normalizeIncomingPayload($payload);

        $attrs = ['components' => $normalized['components']];
        if ($normalized['url_explicit']) {
            $attrs['url'] = $normalized['url'];
        }

        $page = TenantStaticPage::updateOrCreate(
            ['user_id' => $user->id, 'page_id' => $pageId],
            $attrs
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Static page saved successfully',
            'data' => [
                'page' => $this->formatPage($page),
            ],
        ]);
    }

    public function update(StaticPageUpdateRequest $request, string $pageId): JsonResponse
    {
        if (! $this->isAllowedPageId($pageId)) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $user = $request->user();
        $validated = $request->validated();

        $page = TenantStaticPage::query()
            ->where('user_id', $user->id)
            ->where('page_id', $pageId)
            ->first();

        $components = $page?->components ?? [];
        if (array_key_exists('components', $validated)) {
            $normalized = TenantStaticPage::normalizeIncomingPayload([
                'components' => $validated['components'],
            ]);
            $components = $normalized['components'];
        }

        $attrs = ['components' => $components];
        if (array_key_exists('url', $validated)) {
            $attrs['url'] = $validated['url'];
        }

        $page = TenantStaticPage::updateOrCreate(
            ['user_id' => $user->id, 'page_id' => $pageId],
            $attrs
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Static page updated successfully',
            'data' => [
                'page' => $this->formatPage($page),
            ],
        ]);
    }

    public function destroy(Request $request, string $pageId): JsonResponse
    {
        if (! $this->isAllowedPageId($pageId)) {
            return response()->json(['status' => 'error', 'message' => 'Not found'], 404);
        }

        $deleted = TenantStaticPage::query()
            ->where('user_id', $request->user()->id)
            ->where('page_id', $pageId)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => $deleted ? 'Static page deleted' : 'No static page to delete',
            'data' => ['deleted' => (bool) $deleted],
        ]);
    }

    private function isAllowedPageId(string $pageId): bool
    {
        return in_array($pageId, TenantStaticPage::DASHBOARD_PAGE_IDS, true);
    }

    private function formatPage(TenantStaticPage $page): array
    {
        return [
            'page_id' => $page->page_id,
            'components' => $page->components ?? [],
            'url' => $page->url,
        ];
    }
}
