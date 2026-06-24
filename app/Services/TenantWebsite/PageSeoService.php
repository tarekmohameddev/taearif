<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantPage;
use App\Models\TenantStaticPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Support\TenantWebsite\PageSeoPath;
use Illuminate\Support\Arr;

class PageSeoService
{
    /** @var list<string> */
    public const OG_TYPES = [
        'website',
        'article',
        'book',
        'profile',
        'music.song',
        'music.album',
        'music.playlist',
        'music.radio_station',
        'video.movie',
        'video.episode',
        'video.tv_show',
        'video.other',
    ];

    /** @var list<string> */
    public const META_FIELD_KEYS = [
        'path',
        'TitleAr',
        'TitleEn',
        'DescriptionAr',
        'DescriptionEn',
        'KeywordsAr',
        'KeywordsEn',
        'Author',
        'AuthorEn',
        'Robots',
        'RobotsEn',
        'og:title',
        'og:description',
        'og:keywords',
        'og:author',
        'og:robots',
        'og:url',
        'og:image',
        'og:type',
        'og:locale',
        'og:locale:alternate',
        'og:site_name',
        'og:image:width',
        'og:image:height',
        'og:image:type',
        'og:image:alt',
    ];

    public function listPages(User $tenant): array
    {
        $pages = [];
        foreach ($this->collectPageKeys($tenant) as $pageKey) {
            $pages[] = $this->buildPagePayload($tenant, $pageKey);
        }

        return ['pages' => $pages];
    }

    public function getPage(User $tenant, string $pageKey): ?array
    {
        if (! PageSeoPath::isValidPageKey($pageKey)) {
            return null;
        }

        $knownKeys = $this->collectPageKeys($tenant);
        $path = PageSeoPath::pageKeyToPath($pageKey);
        $stored = $this->storedByPath($tenant);
        $hasStored = array_key_exists($path, $stored);

        if (! in_array($pageKey, $knownKeys, true) && ! $hasStored) {
            return null;
        }

        return $this->buildPagePayload($tenant, $pageKey);
    }

    public function upsertPage(User $tenant, string $pageKey, array $meta): array
    {
        if (! PageSeoPath::isValidPageKey($pageKey)) {
            throw new \InvalidArgumentException('Invalid page key.');
        }

        $path = PageSeoPath::pageKeyToPath($pageKey);
        $meta['path'] = $path;

        $layout = TenantWebsiteLayout::firstOrNew(['user_id' => $tenant->id]);
        $data = is_array($layout->data) ? $layout->data : [];
        $pages = Arr::get($data, 'metaTags.pages', []);
        if (! is_array($pages)) {
            $pages = [];
        }

        $default = $this->defaultForPath($path);
        $stored = $this->storedByPath($tenant);
        $existing = array_merge($default, $stored[$path] ?? []);
        $incoming = $this->filterMetaFields(array_merge(['path' => $path], $meta));
        $merged = array_merge($existing, $incoming);

        $pages = $this->removePagesByPath($pages, $path);
        $pages[] = $merged;

        $data['metaTags'] = ['pages' => array_values($pages)];
        $layout->data = $data;
        $layout->save();

        $layout->refresh();

        return $this->buildPagePayload($tenant, $pageKey);
    }

    public function deletePage(User $tenant, string $pageKey): bool
    {
        if (! PageSeoPath::isValidPageKey($pageKey)) {
            return false;
        }

        $path = PageSeoPath::pageKeyToPath($pageKey);
        $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        if (! $layout || ! is_array($layout->data)) {
            return false;
        }

        $pages = Arr::get($layout->data, 'metaTags.pages', []);
        if (! is_array($pages) || $pages === []) {
            return false;
        }

        $remaining = $this->removePagesByPath($pages, $path);
        if (count($remaining) === count($pages)) {
            return false;
        }

        $data = $layout->data;
        $data['metaTags'] = ['pages' => array_values($remaining)];
        $layout->data = $data;
        $layout->save();

        return true;
    }

    /**
     * @return list<string>
     */
    protected function collectPageKeys(User $tenant): array
    {
        $keys = [];

        foreach ($this->defaultsByPath() as $path => $_) {
            $keys[] = PageSeoPath::pathToPageKey($path);
        }

        foreach (TenantStaticPage::DASHBOARD_PAGE_IDS as $staticId) {
            $keys[] = $staticId;
        }

        $generalKeys = array_flip($keys);

        TenantPage::where('user_id', $tenant->id)
            ->pluck('page_id')
            ->each(function (string $pageId) use (&$keys, $generalKeys) {
                if ($pageId === '' || isset($generalKeys[$pageId])) {
                    return;
                }
                if (in_array($pageId, TenantStaticPage::DASHBOARD_PAGE_IDS, true)) {
                    return;
                }
                $keys[] = $pageId;
            });

        $stored = $this->storedByPath($tenant);
        foreach (array_keys($stored) as $path) {
            $key = PageSeoPath::pathToPageKey($path);
            if (! in_array($key, $keys, true)) {
                $keys[] = $key;
            }
        }

        return array_values(array_unique($keys));
    }

    protected function buildPagePayload(User $tenant, string $pageKey): array
    {
        $path = PageSeoPath::pageKeyToPath($pageKey);
        $defaultsByPath = $this->defaultsByPath();
        $storedByPath = $this->storedByPath($tenant);
        $default = $this->defaultForPath($path);
        $override = $storedByPath[$path] ?? null;
        $isGeneral = array_key_exists($path, $defaultsByPath);

        return [
            'page_key' => $pageKey,
            'path' => $path,
            'is_general' => $isGeneral,
            'has_override' => $override !== null,
            'meta' => $override !== null
                ? array_merge($default, $override)
                : $default,
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function defaultsByPath(): array
    {
        $pages = config('tenant_website_default_meta.pages', []);
        $indexed = [];
        foreach ($pages as $page) {
            if (! is_array($page) || empty($page['path'])) {
                continue;
            }
            $indexed[PageSeoPath::normalizePath((string) $page['path'])] = $page;
        }

        return $indexed;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function storedByPath(User $tenant): array
    {
        $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        if (! $layout || ! is_array($layout->data)) {
            return [];
        }

        $pages = Arr::get($layout->data, 'metaTags.pages', []);
        if (! is_array($pages)) {
            return [];
        }

        $indexed = [];
        foreach ($pages as $page) {
            if (! is_array($page) || empty($page['path'])) {
                continue;
            }
            $indexed[PageSeoPath::normalizePath((string) $page['path'])] = $page;
        }

        return $indexed;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultForPath(string $path): array
    {
        $path = PageSeoPath::normalizePath($path);
        $defaults = $this->defaultsByPath();

        if (isset($defaults[$path])) {
            return $defaults[$path];
        }

        return $this->blankMetaTemplate($path);
    }

    /**
     * @return array<string, mixed>
     */
    protected function blankMetaTemplate(string $path): array
    {
        return [
            'path' => PageSeoPath::normalizePath($path),
            'TitleAr' => '',
            'TitleEn' => '',
            'DescriptionAr' => '',
            'DescriptionEn' => '',
            'KeywordsAr' => '',
            'KeywordsEn' => '',
            'Author' => '',
            'AuthorEn' => '',
            'Robots' => 'index, follow',
            'RobotsEn' => 'index, follow',
            'og:title' => '',
            'og:description' => '',
            'og:keywords' => '',
            'og:author' => '',
            'og:robots' => 'index, follow',
            'og:url' => '',
            'og:image' => '',
            'og:type' => 'website',
            'og:locale' => 'ar',
            'og:locale:alternate' => 'en',
            'og:site_name' => '',
            'og:image:width' => null,
            'og:image:height' => null,
            'og:image:type' => null,
            'og:image:alt' => '',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     */
    protected function findPageIndexByPath(array $pages, string $path): ?int
    {
        $path = PageSeoPath::normalizePath($path);
        foreach ($pages as $index => $page) {
            if (! is_array($page) || empty($page['path'])) {
                continue;
            }
            if (PageSeoPath::normalizePath((string) $page['path']) === $path) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $pages
     * @return array<int, array<string, mixed>>
     */
    protected function removePagesByPath(array $pages, string $path): array
    {
        $path = PageSeoPath::normalizePath($path);

        return array_values(array_filter($pages, function ($page) use ($path) {
            if (! is_array($page) || empty($page['path'])) {
                return true;
            }

            return PageSeoPath::normalizePath((string) $page['path']) !== $path;
        }));
    }

    /**
     * @return array<string, mixed>
     */
    public static function extractMetaFromPayload(array $payload): array
    {
        $changes = [];

        foreach (self::META_FIELD_KEYS as $key) {
            if (array_key_exists($key, $payload)) {
                $changes[$key] = $payload[$key];
            }
        }

        return $changes;
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterMetaFields(array $meta): array
    {
        $filtered = [];
        foreach (self::META_FIELD_KEYS as $key) {
            if (array_key_exists($key, $meta)) {
                $filtered[$key] = $meta[$key];
            }
        }

        return $filtered;
    }
}
