<?php

namespace App\Http\Controllers\Api\V1\TenantWebsite;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\TenantPage;
use App\Models\TenantStaticPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\TenantSetting;
use App\Models\Api\ApiDomainSetting;
use App\Models\User\BasicSetting;

use App\Http\Requests\Api\V1\TenantWebsite\GetTenantRequest;

class GetTenantController extends Controller
{
    public function store(GetTenantRequest $request)
    {
        $data = $request->validated();
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

        try {
            // Auto-bootstrap tenant website data if missing
            $hasPages = \App\Models\TenantPage::where('user_id', $tenant->id)->exists();
            $hasGlobals = \App\Models\TenantGlobalComponent::where('user_id', $tenant->id)->exists();
            $hasLayout = \App\Models\TenantWebsiteLayout::where('user_id', $tenant->id)->exists();

            if (!$hasPages || !$hasGlobals || !$hasLayout) {
                app(\App\Services\TenantWebsiteSeeder::class)->reseedWebsite($tenant);
            }
            $pages = TenantPage::where('user_id', $tenant->id)->get()->keyBy('page_id')->map->components;
            $staticPages = TenantStaticPage::where('user_id', $tenant->id)->get();
            $staticPagesWithContent = $staticPages->filter(
                static fn (TenantStaticPage $p) => $p->hasPublicContent()
            );
            $staticPagesData = $staticPagesWithContent->isEmpty()
                ? null
                : $staticPagesWithContent->keyBy('page_id')->map(static function (TenantStaticPage $p) {
                    $public = $p->toPublicArray();

                    return [
                        'components' => $public['components'],
                        'url' => $public['url'],
                    ];
                });
            $globals = TenantGlobalComponent::where('user_id', $tenant->id)->first();
            $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
            $basicSetting = BasicSetting::where('user_id', $tenant->id)->first();
            $tenantSetting = TenantSetting::where('user_id', $tenant->id)->first();

            $rawLogo = $basicSetting?->logo ?: $this->extractLogoFromWebsiteData($globals?->data ?? []);
            $logoUrl = $this->toPublicUrl($rawLogo);

            $branding = [
                'logo' => $logoUrl,
                'name' => $basicSetting?->company_name ?: $tenant->username,
                'websiteBranding' => data_get($tenantSetting?->settings, 'websiteBranding'),
            ];
            return response()->json([
                'username' => $tenant->username,
                'websiteName' => $tenant->username,
                'branding' => $branding,
                'componentSettings' => $pages,
                'globalComponentsData' => $globals?->data ?? [],
                'WebsiteLayout' => $layout?->data ?? [],
                'ThemesBackup' => $layout?->themes_backup ?? null,
                'StaticPages' => $staticPagesData,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('GetTenant failed', [
                'tenant_id' => $tenant->id,
                'username' => $tenant->username,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $basicSetting = BasicSetting::where('user_id', $tenant->id)->first();
            $branding = [
                'logo' => $this->toPublicUrl($basicSetting?->logo),
                'name' => $basicSetting?->company_name ?: $tenant->username,
                'websiteBranding' => null,
            ];
            return response()->json([
                'username' => $tenant->username,
                'websiteName' => $tenant->username,
                'branding' => $branding,
                'componentSettings' => [],
                'globalComponentsData' => [],
                'WebsiteLayout' => [],
                'ThemesBackup' => null,
                'StaticPages' => null,
            ]);
        }
    }

    private function toPublicUrl(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        // Already absolute
        if (preg_match('#^https?://#i', $value)) {
            return $value;
        }

        // Absolute path on this host
        if (str_starts_with($value, '/')) {
            return url($value);
        }

        // If it's just a filename (common in web onboarding), serve from the public user assets folder.
        if (!str_contains($value, '/')) {
            return asset('assets/front/img/user/' . $value);
        }

        // Otherwise treat as a relative public path.
        return asset($value);
    }

    /**
     * Best-effort fallback: find a logo string inside the seeded website data structure.
     * We look for known shapes used in templates: companyInfo.logo, logo.image, or a direct logo string.
     */
    private function extractLogoFromWebsiteData(array $data): ?string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                // companyInfo.logo (string)
                if (isset($value['companyInfo']) && is_array($value['companyInfo']) && isset($value['companyInfo']['logo']) && is_string($value['companyInfo']['logo'])) {
                    return $value['companyInfo']['logo'];
                }

                // logo.image (string)
                if (isset($value['logo']) && is_array($value['logo']) && isset($value['logo']['image']) && is_string($value['logo']['image'])) {
                    return $value['logo']['image'];
                }

                // direct logo (string)
                if ($key === 'logo' && is_string($value)) {
                    return $value;
                }

                $nested = $this->extractLogoFromWebsiteData($value);
                if ($nested) {
                    return $nested;
                }
            }

            if ($key === 'logo' && is_string($value)) {
                return $value;
            }
        }

        return null;
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


