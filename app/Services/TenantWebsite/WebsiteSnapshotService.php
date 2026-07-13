<?php

namespace App\Services\TenantWebsite;

use App\Models\Api\ApiDomainSetting;
use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantStaticPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;

class WebsiteSnapshotService
{
    /**
     * Build the 7-field snapshot (websiteName, domain, pages, globalComponentsData,
     * WebsiteLayout, ThemesBackup, StaticPages) reflecting the tenant's currently
     * persisted state. Used for both "before" and "after" activity-log snapshots.
     *
     * @return array<string, mixed>
     */
    public function snapshot(User $tenant): array
    {
        $pages = TenantPage::where('user_id', $tenant->id)
            ->get()
            ->keyBy('page_id')
            ->map->components;

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

        return [
            'websiteName' => $tenant->username,
            'domain' => $this->resolveDomain($tenant),
            'pages' => $pages,
            'globalComponentsData' => $globals?->data ?? [],
            'WebsiteLayout' => $layout?->data ?? [],
            'ThemesBackup' => $layout?->themes_backup ?? null,
            'StaticPages' => $staticPagesData,
        ];
    }

    /**
     * @return array{subdomain: ?string, customDomain: ?string}
     */
    private function resolveDomain(User $tenant): array
    {
        $customDomain = ApiDomainSetting::where('user_id', $tenant->id)
            ->preferredActive()
            ->first()?->custom_name;

        return [
            'subdomain' => $tenant->username,
            'customDomain' => $customDomain,
        ];
    }
}
