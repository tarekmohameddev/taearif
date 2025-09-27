<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Services\TenantWebsite\PublishService;
use App\Models\User;

class PublishController extends Controller
{
    public function __construct(private PublishService $publish) {}

    public function store(\Illuminate\Http\Request $request, string $tenantId)
    {
        $tenant = User::where('username', $tenantId)->firstOrFail();
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $out = $this->publish->publish($tenant);
        return response()->json(['success' => true] + $out);
    }
}


