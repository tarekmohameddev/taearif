<?php

namespace App\Services\TenantBrand;

final class TenantBrandSourceLocator
{
    /** @return array{path: string, mime: string, size: int, width: int, height: int}|null */
    public function locate(string $url): ?array
    {
        if (parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return null;
        }

        $host = strtolower(rtrim((string) parse_url($url, PHP_URL_HOST), '.'));
        if (! in_array($host, config('tenant_brand.local_asset_hosts', []), true)) {
            return null;
        }

        $urlPath = parse_url($url, PHP_URL_PATH);
        if (! is_string($urlPath)) {
            return null;
        }

        $root = realpath(public_path());
        $path = realpath(public_path(ltrim(rawurldecode($urlPath), '/')));
        if ($root === false || $path === false || ! is_file($path)) {
            return null;
        }

        $prefix = rtrim(strtolower($root), '\\/') . DIRECTORY_SEPARATOR;
        if (! str_starts_with(strtolower($path), $prefix)) {
            return null;
        }

        $size = filesize($path);
        if (! is_int($size) || $size <= 0 || $size > (int) config('tenant_brand.max_source_bytes', 5242880)) {
            return null;
        }

        $info = @getimagesize($path);
        if (! is_array($info) || ! isset($info[0], $info[1], $info['mime'])) {
            return null;
        }

        $width = (int) $info[0];
        $height = (int) $info[1];
        $mime = strtolower((string) $info['mime']);
        if (! in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true)
            || $width < 1 || $height < 1
            || $width > (int) config('tenant_brand.max_source_dimension', 10000)
            || $height > (int) config('tenant_brand.max_source_dimension', 10000)
            || ($width * $height) > (int) config('tenant_brand.max_source_pixels', 40000000)) {
            return null;
        }

        return compact('path', 'mime', 'size', 'width', 'height');
    }
}
