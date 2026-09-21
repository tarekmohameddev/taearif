<?php

namespace App\Services\TenantBrand;

final class TenantBrandUrlPolicy
{
    /** @var list<string> */
    private array $allowedHosts;

    /** @param list<string>|null $allowedHosts */
    public function __construct(?array $allowedHosts = null)
    {
        $configured = $allowedHosts ?? config('tenant_brand.asset_hosts', []);
        $this->allowedHosts = array_values(array_unique(array_filter(array_map(
            static fn ($host) => strtolower(rtrim(trim((string) $host), '.')),
            $configured
        ))));
    }

    public function normalize(string $value): ?string
    {
        $value = trim($value);

        if (preg_match('#^https://#i', $value)) {
            return $value;
        }

        if (preg_match('#^data:#i', $value)) {
            return null;
        }

        if (str_starts_with($value, '//')) {
            return $this->isAllowedHost($value)
                ? 'https:' . $value
                : null;
        }

        if (preg_match('#^http://#i', $value)) {
            return $this->isAllowedHost($value)
                ? preg_replace('#^http://#i', 'https://', $value)
                : null;
        }

        return null;
    }

    private function isAllowedHost(string $value): bool
    {
        $host = parse_url(str_starts_with($value, '//') ? 'https:' . $value : $value, PHP_URL_HOST);

        return is_string($host)
            && in_array(strtolower(rtrim($host, '.')), $this->allowedHosts, true);
    }
}
