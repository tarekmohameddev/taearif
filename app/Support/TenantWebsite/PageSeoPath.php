<?php

namespace App\Support\TenantWebsite;

class PageSeoPath
{
    public const HOMEPAGE_KEY = 'homepage';

    public static function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '' || $path === '/' || $path === 'homepage' || $path === 'home') {
            return '/';
        }

        return str_starts_with($path, '/') ? $path : '/'.$path;
    }

    public static function pageKeyToPath(string $pageKey): string
    {
        $pageKey = trim($pageKey);
        if ($pageKey === '' || $pageKey === self::HOMEPAGE_KEY) {
            return '/';
        }

        return self::normalizePath($pageKey);
    }

    public static function pathToPageKey(string $path): string
    {
        $path = self::normalizePath($path);

        return $path === '/' ? self::HOMEPAGE_KEY : ltrim($path, '/');
    }

    public static function isValidPageKey(string $pageKey): bool
    {
        return (bool) preg_match('/^[A-Za-z0-9_-]+$/', $pageKey);
    }
}
