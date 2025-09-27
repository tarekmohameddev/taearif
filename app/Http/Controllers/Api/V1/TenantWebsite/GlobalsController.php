<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantWebsite\SaveGlobalsRequest;
use App\Services\TenantWebsite\GlobalService;
use App\Models\User;

class GlobalsController extends Controller
{
    public function __construct(private GlobalService $globals) {}

    public function update(SaveGlobalsRequest $request, string $tenantId)
    {
        $tenant = User::where('username', $tenantId)->firstOrFail();
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $saved = $this->globals->update($tenant, $request->input('data'));
        return response()->json(['success' => true, 'data' => $saved->data]);
    }
}


