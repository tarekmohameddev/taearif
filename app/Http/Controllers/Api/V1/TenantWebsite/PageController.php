<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantWebsite\SaveSinglePageRequest;
use App\Http\Requests\TenantWebsite\CreatePageRequest;
use Illuminate\Http\Request;
use App\Services\TenantWebsite\PageService;
use App\Models\User;

class PageController extends Controller
{
    public function __construct(private PageService $pages) {}

    protected function resolveTenant(string $tenantId): User
    {
        return User::where('username', $tenantId)->firstOrFail();
    }

    public function index(Request $request, string $tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);
        return response()->json($this->pages->listPages($tenant));
    }

    public function show(Request $request, string $tenantId, string $pageId)
    {
        $tenant = $this->resolveTenant($tenantId);
        return response()->json($this->pages->getPage($tenant, $pageId));
    }

    public function store(CreatePageRequest $request, string $tenantId)
    {
        $tenant = $this->resolveTenant($tenantId);
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $this->pages->upsertPage($tenant, $request->input('pageId'), $request->input('components'));
        return response()->json(['success' => true]);
    }

    public function update(SaveSinglePageRequest $request, string $tenantId, string $pageId)
    {
        $tenant = $this->resolveTenant($tenantId);
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $this->pages->upsertPage($tenant, $pageId, $request->input('components'));
        return response()->json(['success' => true]);
    }

    public function patch(SaveSinglePageRequest $request, string $tenantId, string $pageId)
    {
        return $this->update($request, $tenantId, $pageId);
    }

    public function destroy(Request $request, string $tenantId, string $pageId)
    {
        $tenant = $this->resolveTenant($tenantId);
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $this->pages->deletePage($tenant, $pageId);
        return response()->json(['success' => true]);
    }
}


