<?php

namespace App\Services\TenantWebsite;

use App\Models\User\BasicSetting;

final class PublicBrandingLogo
{
    public function __construct(private PublicLogoUrl $publicLogoUrl)
    {
    }

    public function from(BasicSetting|null $basicSetting, array $globalComponentsData): ?string
    {
        $rawLogo = $basicSetting?->logo ?: $this->extract($globalComponentsData);

        return $this->publicLogoUrl->transform($rawLogo);
    }

    private function extract(array $data): ?string
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                if (isset($value['companyInfo']['logo']) && is_string($value['companyInfo']['logo'])) {
                    return $value['companyInfo']['logo'];
                }

                if (isset($value['logo']['image']) && is_string($value['logo']['image'])) {
                    return $value['logo']['image'];
                }

                if ($key === 'logo' && is_string($value)) {
                    return $value;
                }

                $nested = $this->extract($value);
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
}
