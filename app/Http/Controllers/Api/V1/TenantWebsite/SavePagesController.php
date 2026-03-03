<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TenantWebsite\PageService;
use App\Models\User;

use App\Http\Requests\Api\V1\TenantWebsite\SavePagesRequest;

class SavePagesController extends Controller
{
    public function __construct(private PageService $pages) {}

    public function store(SavePagesRequest $request)
    {
        $data = $request->validated();

        $tenant = User::where('username', $data['tenantId'])->firstOrFail();
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $pages = $data['pages'] ?? $request->input('componentSettings');
        if (!is_array($pages)) {
            return response()->json(['message' => 'pages or componentSettings is required'], 422);
        }

        $result = $this->pages->savePagesPayload(
            $tenant,
            $pages,
            $data['globalComponentsData'] ?? null,
            $data['WebsiteLayout'] ?? null,
            $data['ThemesBackup'] ?? null,
            $data['StaticPages'] ?? null,
            $data['branding']['websiteBranding'] ?? null
        );
        return response()->json([
            'success' => true,
            'message' => "Pages saved successfully. {$result['pagesDeleted']} empty page(s) deleted from database.",
            'tenantId' => $tenant->username,
            'pagesSaved' => $result['pagesSaved'],
            'pagesDeleted' => $result['pagesDeleted'],
            'componentsSaved' => $result['componentsSaved'],
        ]);
    }
}


