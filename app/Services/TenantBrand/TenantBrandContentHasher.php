<?php

namespace App\Services\TenantBrand;

final class TenantBrandContentHasher
{
    /** @var list<string> */
    private array $localHosts;

    private string $publicRoot;

    /** @param list<string>|null $localHosts */
    public function __construct(?array $localHosts = null, ?string $publicRoot = null)
    {
        $this->localHosts = array_values(array_filter(array_map(
            static fn ($host) => strtolower(rtrim(trim((string) $host), '.')),
            $localHosts ?? config('tenant_brand.local_asset_hosts', [])
        )));
        $this->publicRoot = rtrim($publicRoot ?? public_path(), '\\/');
    }

    public function hash(?string $url): ?string
    {
        if ($url === null) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);
        $path = parse_url($url, PHP_URL_PATH);
        if (! is_string($host) || ! is_string($path)) {
            return null;
        }

        if (! in_array(strtolower(rtrim($host, '.')), $this->localHosts, true)) {
            return null;
        }

        $candidate = realpath($this->publicRoot . DIRECTORY_SEPARATOR . ltrim(rawurldecode($path), '/'));
        $root = realpath($this->publicRoot);
        if ($candidate === false || $root === false || ! is_file($candidate)) {
            return null;
        }

        $prefix = rtrim(strtolower($root), '\\/') . DIRECTORY_SEPARATOR;
        if (! str_starts_with(strtolower($candidate), $prefix)) {
            return null;
        }

        $hash = hash_file('sha256', $candidate);

        return is_string($hash) ? $hash : null;
    }
}
