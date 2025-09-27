<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantWebsite\UploadMediaRequest;
use App\Services\TenantWebsite\MediaService;
use App\Models\User;

class MediaController extends Controller
{
    public function __construct(private MediaService $media) {}

    public function store(UploadMediaRequest $request, string $tenantId)
    {
        $tenant = User::where('username', $tenantId)->firstOrFail();
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $file = $request->file('file');
        $saved = $this->media->upload($tenant, $file);
        return response()->json([
            'id' => $saved->id,
            'url' => $saved->url,
            'mime' => $saved->mime,
            'size' => $saved->size,
        ]);
    }
}


