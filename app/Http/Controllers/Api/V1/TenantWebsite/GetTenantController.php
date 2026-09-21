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
use App\Models\Api\GeneralSetting;
use App\Models\User\BasicSetting;
use App\Services\Membership\MembershipAccessStateService;
use App\Services\TenantWebsite\PublicBrandingLogo;
use App\Services\TenantWebsite\TenantIdentifierLookup;

use App\Http\Requests\Api\V1\TenantWebsite\GetTenantRequest;

class GetTenantController extends Controller
{
    protected $accessStateService;

    public function __construct(
        MembershipAccessStateService $accessStateService,
        private TenantIdentifierLookup $tenantIdentifierLookup,
        private PublicBrandingLogo $publicBrandingLogo
    )
    {
        $this->accessStateService = $accessStateService;
    }

    public function store(GetTenantRequest $request)
    {
        $data = $request->validated();
        $tenant = $this->tenantIdentifierLookup->find($data['websiteName']);
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

            $logoUrl = $this->publicBrandingLogo->from($basicSetting, $globals?->data ?? []);

            $branding = [
                'logo' => $logoUrl,
                'name' => $basicSetting?->company_name ?: $tenant->username,
                'websiteBranding' => data_get($tenantSetting?->settings, 'websiteBranding'),
            ];
            $publicAccessState = $this->accessStateService->publicForTenant($tenant);
            return response()->json([
                'username' => $tenant->username,
                'websiteName' => $tenant->username,
                'branding' => $branding,
                'componentSettings' => $pages,
                'globalComponentsData' => $globals?->data ?? [],
                'WebsiteLayout' => $layout?->data ?? [],
                'ThemesBackup' => $layout?->themes_backup ?? null,
                'StaticPages' => $staticPagesData,
                'maintenance_mode' => $this->isMaintenanceMode($tenant),
                'subscription' => $publicAccessState['subscription'],
                'website_access' => $publicAccessState['website_access'],
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
                'logo' => $this->publicBrandingLogo->from($basicSetting, []),
                'name' => $basicSetting?->company_name ?: $tenant->username,
                'websiteBranding' => null,
            ];
            $publicAccessState = $this->accessStateService->publicForTenant($tenant);
            return response()->json([
                'username' => $tenant->username,
                'websiteName' => $tenant->username,
                'branding' => $branding,
                'componentSettings' => [],
                'globalComponentsData' => [],
                'WebsiteLayout' => [],
                'ThemesBackup' => null,
                'StaticPages' => null,
                'maintenance_mode' => $this->isMaintenanceMode($tenant),
                'subscription' => $publicAccessState['subscription'],
                'website_access' => $publicAccessState['website_access'],
            ]);
        }
    }

    private function isMaintenanceMode(User $tenant): bool
    {
        return (bool) (GeneralSetting::where('user_id', $tenant->id)->first()?->maintenance_mode ?? false);
    }

}


