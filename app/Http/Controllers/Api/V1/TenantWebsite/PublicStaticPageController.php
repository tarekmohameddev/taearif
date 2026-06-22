<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\V1\TenantWebsite\Concerns\ResolvesTenant;
use App\Models\TenantStaticPage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicStaticPageController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request, string $tenantId): JsonResponse
    {
        $tenant = $this->resolveTenant($request, $tenantId);

        $pages = TenantStaticPage::query()
            ->where('user_id', $tenant->id)
            ->whereIn('page_id', TenantStaticPage::DASHBOARD_PAGE_IDS)
            ->get()
            ->filter(fn (TenantStaticPage $page) => $page->hasPublicContent())
            ->map(fn (TenantStaticPage $page) => $page->toPublicArray())
            ->values()
            ->all();

        return response()->json(['pages' => $pages]);
    }

    public function show(Request $request, string $tenantId, string $pageId): JsonResponse
    {
        if (! in_array($pageId, TenantStaticPage::DASHBOARD_PAGE_IDS, true)) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $tenant = $this->resolveTenant($request, $tenantId);

        $page = TenantStaticPage::query()
            ->where('user_id', $tenant->id)
            ->where('page_id', $pageId)
            ->first();

        if (! $page || ! $page->hasPublicContent()) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($page->toPublicArray());
    }
}
