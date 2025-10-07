<?php

namespace App\Services;

use App\Models\User;
use App\Models\TenantPage;
use App\Models\TenantGlobalComponent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TenantWebsiteSeeder
{
    /**
     * Seed default website pages and components for a new tenant
     *
     * @param User $tenant
     * @return bool
     */
    public function seedDefaultWebsite(User $tenant): bool
    {
        try {
            return DB::transaction(function () use ($tenant) {
                // Load default template from config
                $template = config('tenant_website_defaults');
                
                if (!$template || !isset($template['pages']) || !isset($template['globalComponentsData'])) {
                    Log::warning('Tenant website default template not found or invalid', [
                        'tenant_id' => $tenant->id,
                    ]);
                    return false;
                }

                // Seed pages
                $this->seedPages($tenant, $template['pages']);

                // Seed global components
                $this->seedGlobalComponents($tenant, $template['globalComponentsData']);

                Log::info('Successfully seeded default website for tenant', [
                    'tenant_id' => $tenant->id,
                    'username' => $tenant->username,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to seed default website for tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return false;
        }
    }

    /**
     * Seed pages for the tenant
     *
     * @param User $tenant
     * @param array $pages
     * @return void
     */
    protected function seedPages(User $tenant, array $pages): void
    {
        foreach ($pages as $pageId => $components) {
            // Sort components by position
            $sortedComponents = collect($components)
                ->sortBy('position')
                ->values()
                ->all();

            TenantPage::create([
                'user_id' => $tenant->id,
                'page_id' => $pageId,
                'components' => $sortedComponents,
            ]);
        }
    }

    /**
     * Seed global components for the tenant
     *
     * @param User $tenant
     * @param array $globalData
     * @return void
     */
    protected function seedGlobalComponents(User $tenant, array $globalData): void
    {
        TenantGlobalComponent::create([
            'user_id' => $tenant->id,
            'data' => $globalData,
        ]);
    }

    /**
     * Check if tenant already has website data
     *
     * @param User $tenant
     * @return bool
     */
    public function hasWebsiteData(User $tenant): bool
    {
        return TenantPage::where('user_id', $tenant->id)->exists() ||
               TenantGlobalComponent::where('user_id', $tenant->id)->exists();
    }

    /**
     * Seed default website only if tenant doesn't have any data yet
     *
     * @param User $tenant
     * @return bool
     */
    public function seedIfEmpty(User $tenant): bool
    {
        if ($this->hasWebsiteData($tenant)) {
            Log::info('Tenant already has website data, skipping seed', [
                'tenant_id' => $tenant->id,
                'username' => $tenant->username,
            ]);
            return false;
        }

        return $this->seedDefaultWebsite($tenant);
    }
}

