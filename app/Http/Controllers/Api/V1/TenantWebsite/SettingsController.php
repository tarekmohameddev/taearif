<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantWebsite\SaveSettingsRequest;
use App\Services\TenantWebsite\SettingsService;
use App\Models\User;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function update(SaveSettingsRequest $request, string $tenantId)
    {
        $tenant = User::where('username', $tenantId)->firstOrFail();
        if ($request->user()?->id !== $tenant->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        $saved = $this->settings->update($tenant, $request->input('settings'));
        return response()->json(['success' => true, 'settings' => $saved->settings]);
    }
}


