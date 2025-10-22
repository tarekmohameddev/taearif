<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TenantPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\Api\ApiDomainSetting;

class GetTenantController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'websiteName' => 'required|string',
        ]);
        $input = strtolower(trim($data['websiteName']));

        // Try resolving by username first
        $tenant = User::where('username', $input)->first();

        // If not found, try resolving by custom domain
        if (!$tenant) {
            $domain = $this->normalizeDomain($input);
            $domainRecord = ApiDomainSetting::where('custom_name', $domain)
                ->where('status', 'active')
                ->first();

            if ($domainRecord) {
                $tenant = $domainRecord->user;
            }
        }
        if (!$tenant) {
            return response()->json([], 204);
        }
        $pages = TenantPage::where('user_id', $tenant->id)->get()->keyBy('page_id')->map->components;
        $globals = TenantGlobalComponent::where('user_id', $tenant->id)->first();
        $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        return response()->json([
            'username' => $tenant->username,
            'websiteName' => $tenant->username,
            'componentSettings' => $pages,
            'globalComponentsData' => $globals?->data ?? [],
            'WebsiteLayout' => $layout?->data ?? [],
        ]);
    }

    private function normalizeDomain(string $value): string
    {
        // Strip protocol
        $value = preg_replace('#^https?://#', '', $value);
        // Strip leading www.
        $value = preg_replace('#^www\.#', '', $value);
        // Remove trailing slashes and whitespace
        return rtrim(trim(strtolower($value)), '/');
    }
}


