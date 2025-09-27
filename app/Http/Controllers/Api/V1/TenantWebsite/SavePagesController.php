<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\TenantWebsite\PageService;
use App\Models\User;

class SavePagesController extends Controller
{
    public function __construct(private PageService $pages) {}

    public function store(Request $request)
    {
        $data = $request->validate([
            'tenantId' => 'required|string',
            'pages' => 'required|array',
            'globalComponentsData' => 'nullable|array',
        ]);

        $tenant = User::where('username', $data['tenantId'])->firstOrFail();
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $result = $this->pages->savePagesPayload($tenant, $data['pages'], $data['globalComponentsData'] ?? null);
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


