<?php

namespace App\Services\TenantWebsite;

use App\Models\TenantPage;
use App\Models\TenantStaticPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\TenantSetting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class PageService
{
    public function listPages(User $tenant): array
    {
        return TenantPage::where('user_id', $tenant->id)
            ->get()
            ->map(fn($p) => [
                'pageId' => $p->page_id,
                'components' => $p->components,
            ])->toArray();
    }

    public function getPage(User $tenant, string $pageId): array
    {
        $page = TenantPage::where('user_id', $tenant->id)->where('page_id', $pageId)->first();
        $globals = TenantGlobalComponent::where('user_id', $tenant->id)->first();
        return [
            'pageId' => $pageId,
            'pageData' => $page?->components ?? [],
            'globalComponentsData' => $globals?->data ?? [],
        ];
    }

    public function upsertPage(User $tenant, string $pageId, array $components): TenantPage
    {
        // Components are arrays ordered by position
        return DB::transaction(function () use ($tenant, $pageId, $components) {
            return TenantPage::updateOrCreate(
                ['user_id' => $tenant->id, 'page_id' => $pageId],
                ['components' => $components]
            );
        });
    }

    public function deletePage(User $tenant, string $pageId): void
    {
        TenantPage::where('user_id', $tenant->id)->where('page_id', $pageId)->delete();
    }

    public function savePagesPayload(
        User $tenant,
        array $pages,
        ?array $globals,
        ?array $websiteLayout = null,
        ?array $themesBackup = null,
        ?array $staticPages = null,
        ?array $websiteBranding = null
    ): array
    {
        return DB::transaction(function () use ($tenant, $pages, $globals, $websiteLayout, $themesBackup, $staticPages, $websiteBranding) {
            $pagesSaved = 0;
            $pagesDeleted = 0;
            $componentsSaved = 0;

            $existing = TenantPage::where('user_id', $tenant->id)->pluck('id', 'page_id');
            $sentPageIds = array_keys($pages);

            foreach ($pages as $pageId => $components) {
                $components = collect($components)
                    ->sortBy('position')
                    ->values()
                    ->all();
                $page = TenantPage::updateOrCreate(
                    ['user_id' => $tenant->id, 'page_id' => $pageId],
                    ['components' => $components]
                );
                $pagesSaved++;
                $componentsSaved += count($components);
            }

            // Remove empty pages (no components) and not sent pages that are empty
            $toDelete = TenantPage::where('user_id', $tenant->id)
                ->where(function ($q) use ($sentPageIds) {
                    $q->whereNotIn('page_id', $sentPageIds)->orWhereJsonLength('components', 0);
                })->get();
            foreach ($toDelete as $p) {
                $p->delete();
                $pagesDeleted++;
            }

            // Handle StaticPages if provided
            if ($staticPages !== null) {
                $sentStaticPageIds = array_keys($staticPages);

                foreach ($staticPages as $pageId => $payload) {
                    $normalized = TenantStaticPage::normalizeIncomingPayload($payload);
                    $attrs = ['components' => $normalized['components']];
                    if ($normalized['url_explicit']) {
                        $attrs['url'] = $normalized['url'];
                    }
                    TenantStaticPage::updateOrCreate(
                        ['user_id' => $tenant->id, 'page_id' => $pageId],
                        $attrs
                    );
                }

                // Remove empty static pages (no components) and not sent static pages that are empty
                $toDeleteStatic = TenantStaticPage::where('user_id', $tenant->id)
                    ->where(function ($q) use ($sentStaticPageIds) {
                        $q->whereNotIn('page_id', $sentStaticPageIds)->orWhereJsonLength('components', 0);
                    })->get();
                foreach ($toDeleteStatic as $p) {
                    $p->delete();
                }
            }

            if ($globals !== null) {
                TenantGlobalComponent::updateOrCreate(
                    ['user_id' => $tenant->id],
                    ['data' => $globals]
                );
            }

            if ($websiteLayout !== null || $themesBackup !== null) {
                $layout = TenantWebsiteLayout::firstOrNew(['user_id' => $tenant->id]);
                
                if ($websiteLayout !== null) {
                    $layout->data = $websiteLayout;
                }
                
                if ($themesBackup !== null) {
                    $layout->themes_backup = $themesBackup;
                }
                
                // Ensure data is not null (required by schema)
                if (!isset($layout->data)) {
                    $layout->data = [];
                }
                
                $layout->save();
            }

            if ($websiteBranding !== null) {
                $settings = TenantSetting::firstOrNew(['user_id' => $tenant->id]);
                $current = is_array($settings->settings) ? $settings->settings : [];
                $current['websiteBranding'] = $websiteBranding;
                $settings->settings = $current;
                $settings->save();
            }

            return [
                'pagesSaved' => $pagesSaved,
                'pagesDeleted' => $pagesDeleted,
                'componentsSaved' => $componentsSaved,
            ];
        });
    }
}


