<?php

namespace App\Services\TenantBrand;

final class TenantBrandResolver
{
    public function __construct(
        private TenantBrandPlaceholderPolicy $placeholderPolicy,
        private TenantBrandUrlPolicy $urlPolicy
    ) {
    }

    public function resolve(array $globalComponentsData, ?string $brandingLogo, array $websiteLayout): ?string
    {
        foreach ($this->candidates($globalComponentsData, $brandingLogo, $websiteLayout) as $candidate) {
            if ($this->placeholderPolicy->isPlaceholder($candidate)) {
                continue;
            }

            return $this->urlPolicy->normalize($candidate);
        }

        return null;
    }

    /** @return list<string> */
    private function candidates(array $globals, ?string $brandingLogo, array $layout): array
    {
        $candidates = [];
        $headerLogo = data_get($globals, 'header.logo');

        if (is_array($headerLogo)) {
            $image = $this->nonEmpty($headerLogo['image'] ?? null);
            $headerCandidate = $image ?? $this->nonEmpty($headerLogo['src'] ?? null);
            if ($headerCandidate !== null) {
                $candidates[] = $headerCandidate;
            }
        }

        $branding = $this->nonEmpty($brandingLogo);
        if ($branding !== null) {
            $candidates[] = $branding;
        }

        $legacy = $this->nonEmpty(data_get($layout, 'CustomBranding.header.logo'));
        if ($legacy !== null) {
            $candidates[] = $legacy;
        }

        return $candidates;
    }

    private function nonEmpty(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
