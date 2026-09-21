<?php

namespace App\Services\TenantBrand;

use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Models\User\BasicSetting;
use App\Services\TenantWebsite\PublicBrandingLogo;

final class TenantBrandPayloadBuilder
{
    public function __construct(
        private PublicBrandingLogo $publicBrandingLogo,
        private TenantBrandResolver $resolver,
        private TenantBrandProjector $projector
    ) {
    }

    /** @return array{logoUrl: ?string, inline: null, v: string} */
    public function build(User $tenant): array
    {
        return $this->buildWith($tenant);
    }

    /**
     * Model overrides let observers reuse the just-saved values instead of
     * reading the changed row again. A present null override means deleted.
     *
     * @param array{globals?: TenantGlobalComponent|null, layout?: TenantWebsiteLayout|null, basic?: BasicSetting|null} $overrides
     * @return array{logoUrl: ?string, inline: null, v: string}
     */
    public function buildWith(User $tenant, array $overrides = []): array
    {
        $globals = array_key_exists('globals', $overrides)
            ? $overrides['globals']
            : TenantGlobalComponent::where('user_id', $tenant->id)->first();
        $layout = array_key_exists('layout', $overrides)
            ? $overrides['layout']
            : TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        $basicSetting = array_key_exists('basic', $overrides)
            ? $overrides['basic']
            : BasicSetting::where('user_id', $tenant->id)->first();
        $globalData = is_array($globals?->data) ? $globals->data : [];
        $layoutData = is_array($layout?->data) ? $layout->data : [];
        $brandingLogo = $this->publicBrandingLogo->from($basicSetting, $globalData);
        $sourceUrl = $this->resolver->resolve($globalData, $brandingLogo, $layoutData);

        return $this->projector->project($sourceUrl);
    }
}
