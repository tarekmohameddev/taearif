<?php

namespace App\Services;

use App\Models\User;
use App\Models\Api\FooterSetting;
use App\Models\TenantPage;
use App\Models\TenantStaticPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TenantWebsiteSeeder
{
    /**
     * Maximum number of retry attempts for API calls
     */
    protected const MAX_RETRIES = 2;

    /**
     * API request timeout in seconds
     */
    protected const TIMEOUT = 5;

    /**
     * Cache key prefix for the fetched default template, keyed by API URL.
     */
    protected const CACHE_KEY_PREFIX = 'tenant_website_default_data:';

    /**
     * Placeholder emails in default templates — replaced with tenant/footer email during reseed.
     *
     * @var list<string>
     */
    protected const EMAIL_PLACEHOLDERS = [
        'info@example.com',
        'example@gmail.com',
    ];

    /**
     * Fetch default data from external API with retry logic
     * Falls back to local config if API fails after retries
     *
     * @return array|null
     */
    protected function fetchDefaultData(): ?array
    {
        $apiUrl = config('app.tenant_website_api_url');

        // If API URL is not configured, use local config
        if (empty($apiUrl)) {
            Log::info('API URL not configured, using local config');
            return config('tenant_website_defaults');
        }

        $cacheKey = self::CACHE_KEY_PREFIX . md5($apiUrl);
        $ttl = (int) config('app.tenant_website_api_cache_ttl', 3600);

        $cached = Cache::get($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $data = $this->fetchFromApi($apiUrl);

        if ($data !== null) {
            // Only cache genuinely successful API responses so a failed
            // fetch can be retried sooner instead of being stuck for the full TTL.
            Cache::put($cacheKey, $data, $ttl);

            return $data;
        }

        // All retries failed, fall back to local config (not cached).
        return config('tenant_website_defaults');
    }

    /**
     * Bust the cached default template data for a given (or the configured) API URL.
     *
     * @param string|null $apiUrl
     * @return void
     */
    public static function clearDefaultDataCache(?string $apiUrl = null): void
    {
        $apiUrl = $apiUrl ?? config('app.tenant_website_api_url');

        if (empty($apiUrl)) {
            return;
        }

        Cache::forget(self::CACHE_KEY_PREFIX . md5($apiUrl));
    }

    /**
     * Fetch the default template from the external API with retry logic.
     * Uses the HTTP client's built-in retry/backoff instead of blocking sleep()
     * calls so a worker (queue or web) isn't tied up longer than necessary.
     *
     * @param string $apiUrl
     * @return array|null
     */
    protected function fetchFromApi(string $apiUrl): ?array
    {
        try {
            Log::info('Fetching default data from API', ['url' => $apiUrl]);

            $response = Http::timeout(self::TIMEOUT)
                ->retry(self::MAX_RETRIES, 500)
                ->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['componentSettings']) && isset($data['globalComponentsData'])) {
                    Log::info('Successfully fetched default data from API');
                    return $data;
                }

                Log::warning('Invalid API response structure: missing required keys', ['response' => $data]);
                return null;
            }

            Log::warning("API request failed with status {$response->status()}", [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } catch (\Exception $e) {
            Log::error('API request failed after retries, falling back to local config', [
                'error' => $e->getMessage(),
                'url' => $apiUrl,
            ]);
        }

        return null;
    }

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
                // Fetch default template from API (with fallback to local config)
                $template = $this->fetchDefaultData();

                if (!$template || !isset($template['componentSettings']) || !isset($template['globalComponentsData'])) {
                    Log::warning('Tenant website default template not found or invalid', [
                        'tenant_id' => $tenant->id,
                    ]);
                    return false;
                }

                // Seed pages
                $this->seedPages($tenant, $template['componentSettings']);

                // Seed static pages if provided
                if (isset($template['StaticPages']) && is_array($template['StaticPages'])) {
                    $this->seedStaticPages($tenant, $template['StaticPages']);
                }

                $tenant->refresh();

                $companyInfoFromFooterForEmail = $this->extractCompanyInfoForWebsiteLayoutFromFooter($tenant);
                $resolvedEmail = $this->resolveContactEmailFromFooterAndUser($companyInfoFromFooterForEmail, $tenant);

                // Seed global components (merge resolved email into footer.contactInfo for builder)
                $template['globalComponentsData'] = $this->applyResolvedEmailToGlobalComponentsData(
                    $template['globalComponentsData'],
                    $resolvedEmail
                );
                $this->seedGlobalComponents($tenant, $template['globalComponentsData']);

                // Seed website layout if provided
                if (isset($template['WebsiteLayout']) && is_array($template['WebsiteLayout'])) {
                    $websiteLayout = $this->applyResolvedEmailToWebsiteLayout(
                        $template['WebsiteLayout'],
                        $resolvedEmail
                    );
                    $this->seedWebsiteLayout($tenant, $websiteLayout);
                }

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

            TenantPage::updateOrCreate(
                [
                'user_id' => $tenant->id,
                'page_id' => $pageId,
                ],
                [
                'components' => $sortedComponents,
                ]
            );
        }
    }

    /**
     * Seed static pages for the tenant
     *
     * @param User $tenant
     * @param array $staticPages
     * @return void
     */
    protected function seedStaticPages(User $tenant, array $staticPages): void
    {
        foreach ($staticPages as $pageId => $payload) {
            $normalized = TenantStaticPage::normalizeIncomingPayload($payload);
            $attrs = ['components' => $normalized['components']];
            if ($normalized['url_explicit']) {
                $attrs['url'] = $normalized['url'];
            }

            TenantStaticPage::updateOrCreate(
                [
                'user_id' => $tenant->id,
                'page_id' => $pageId,
                ],
                $attrs
            );
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
        TenantGlobalComponent::updateOrCreate(
            [
            'user_id' => $tenant->id,
            ],
            [
            'data' => $globalData,
            ]
        );
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

    /**
     * Re-seed/refresh website pages and components for a tenant
     * This will update existing data with fresh defaults
     * Also injects onboarding data (logo, company name) if available
     *
     * @param User $tenant
     * @return bool
     */
    public function reseedWebsite(User $tenant): bool
    {
        try {
            return DB::transaction(function () use ($tenant) {
                // Fetch default template from API (with fallback to local config)
                $template = $this->fetchDefaultData();

                if (!$template || !isset($template['componentSettings']) || !isset($template['globalComponentsData'])) {
                    Log::warning('Tenant website default template not found for reseed', [
                        'tenant_id' => $tenant->id,
                    ]);
                    return false;
                }

                $tenant->refresh();

                // Get onboarding data from BasicSetting
                $basicSetting = \App\Models\User\BasicSetting::where('user_id', $tenant->id)->first();
                $brandingColors = null;

                if ($basicSetting) {
                    // Inject onboarding data into template
                    $template = $this->injectOnboardingData($template, $basicSetting, $tenant);
                    $brandingColors = $this->extractBrandingColorsFromBasicSetting($basicSetting);
                }

                $companyInfoFromFooter = $this->extractCompanyInfoForWebsiteLayoutFromFooter($tenant);
                $resolvedEmail = $this->resolveContactEmailFromFooterAndUser($companyInfoFromFooter, $tenant);

                // Update/recreate pages
                $this->seedPages($tenant, $template['componentSettings']);

                // Update/recreate static pages if provided
                if (isset($template['StaticPages']) && is_array($template['StaticPages'])) {
                    $this->seedStaticPages($tenant, $template['StaticPages']);
                }

                // Update/recreate global components (ensure footer.content.contactInfo.email)
                $template['globalComponentsData'] = $this->applyResolvedEmailToGlobalComponentsData(
                    $template['globalComponentsData'],
                    $resolvedEmail
                );
                $this->seedGlobalComponents($tenant, $template['globalComponentsData']);

                // Update/recreate website layout if provided
                if (isset($template['WebsiteLayout']) && is_array($template['WebsiteLayout'])) {
                    $websiteLayout = $template['WebsiteLayout'];

                    if (!empty($brandingColors)) {
                        $websiteLayout = $this->applyBrandingColorsToWebsiteLayout(
                            $websiteLayout,
                            $brandingColors
                        );
                    }

                    $websiteLayout = $this->applyCompanyInfoToWebsiteLayout($websiteLayout, $companyInfoFromFooter);
                    $websiteLayout = $this->applyResolvedEmailToWebsiteLayout($websiteLayout, $resolvedEmail);

                    $this->seedWebsiteLayout($tenant, $websiteLayout);
                } elseif (
                    !empty($brandingColors)
                    || $companyInfoFromFooter !== null
                    || ($resolvedEmail !== null && $resolvedEmail !== '')
                ) {
                    // Keep existing layout data; merge onboarding colors, footer company info, and resolved email.
                    $this->mergeIntoExistingWebsiteLayout(
                        $tenant,
                        $brandingColors ?? [],
                        $companyInfoFromFooter,
                        $resolvedEmail
                    );
                }

                Log::info('Successfully re-seeded website for tenant', [
                    'tenant_id' => $tenant->id,
                    'username' => $tenant->username,
                    'with_onboarding_data' => $basicSetting !== null,
                ]);

                return true;
            });
        } catch (\Exception $e) {
            Log::error('Failed to re-seed website for tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Build branding colors from basic settings.
     *
     * @param \App\Models\User\BasicSetting $basicSetting
     * @return array<string, string>
     */
    protected function extractBrandingColorsFromBasicSetting($basicSetting): array
    {
        $colors = [];

        if (!empty($basicSetting->base_color)) {
            $colors['primary'] = $basicSetting->base_color;
        }

        if (!empty($basicSetting->secondary_color)) {
            $colors['secondary'] = $basicSetting->secondary_color;
        }

        if (!empty($basicSetting->accent_color)) {
            $colors['accent'] = $basicSetting->accent_color;
        }

        return $colors;
    }

    /**
     * Ensure WebsiteLayout contains branding colors.
     *
     * @param array $layout
     * @param array<string, string> $brandingColors
     * @return array
     */
    protected function applyBrandingColorsToWebsiteLayout(array $layout, array $brandingColors): array
    {
        if (!isset($layout['branding']) || !is_array($layout['branding'])) {
            $layout['branding'] = [];
        }

        if (!isset($layout['branding']['colors']) || !is_array($layout['branding']['colors'])) {
            $layout['branding']['colors'] = [];
        }

        $layout['branding']['colors'] = array_merge($layout['branding']['colors'], $brandingColors);

        return $layout;
    }

    /**
     * Read company contact fields from api_footer_settings (same JSON as onboarding FooterSetting).
     *
     * @return array<string, mixed>|null
     */
    protected function extractCompanyInfoForWebsiteLayoutFromFooter(User $tenant): ?array
    {
        $footer = FooterSetting::where('user_id', $tenant->id)->first();
        if (!$footer || !is_array($footer->general)) {
            return null;
        }

        $general = $footer->general;

        return [
            'email' => $general['email'] ?? null,
            'phone' => $general['phone'] ?? null,
            'address' => $general['address'] ?? null,
            'valLicense' => $general['valLicense'] ?? null,
            'workingHours' => $general['workingHours'] ?? null,
        ];
    }

    /**
     * Merge WebsiteLayout.companyInfo from footer general (mirrors onboarding persistence; double-write target).
     *
     * @param  array<string, mixed>  $layout
     * @param  array<string, mixed>|null  $companyInfo
     * @return array<string, mixed>
     */
    protected function applyCompanyInfoToWebsiteLayout(array $layout, ?array $companyInfo): array
    {
        if ($companyInfo === null) {
            return $layout;
        }

        if (!isset($layout['companyInfo']) || !is_array($layout['companyInfo'])) {
            $layout['companyInfo'] = [];
        }

        foreach (['email', 'phone', 'address', 'valLicense', 'workingHours'] as $key) {
            if (array_key_exists($key, $companyInfo)) {
                $layout['companyInfo'][$key] = $companyInfo[$key];
            }
        }

        return $layout;
    }

    /**
     * Merge branding colors and footer company info into existing tenant layout when template has no WebsiteLayout.
     *
     * @param  array<string, string>  $brandingColors
     * @param  array<string, mixed>|null  $companyInfoFromFooter
     */
    protected function mergeIntoExistingWebsiteLayout(
        User $tenant,
        array $brandingColors,
        ?array $companyInfoFromFooter,
        ?string $resolvedEmail = null
    ): void {
        $layout = TenantWebsiteLayout::firstOrNew(['user_id' => $tenant->id]);
        $existingData = is_array($layout->data) ? $layout->data : [];

        if (!empty($brandingColors)) {
            $existingData = $this->applyBrandingColorsToWebsiteLayout($existingData, $brandingColors);
        }

        $existingData = $this->applyCompanyInfoToWebsiteLayout($existingData, $companyInfoFromFooter);
        $existingData = $this->applyResolvedEmailToWebsiteLayout($existingData, $resolvedEmail);

        if (empty($existingData['metaTags']['pages'])) {
            $existingData['metaTags'] = [
                'pages' => config('tenant_website_default_meta.pages', []),
            ];
        }

        $layout->data = $existingData;
        $layout->save();
    }

    /**
     * Resolve contact email: footer general first, then tenant user record.
     *
     * @param  array<string, mixed>|null  $companyInfoFromFooter
     */
    protected function resolveContactEmailFromFooterAndUser(?array $companyInfoFromFooter, User $tenant): ?string
    {
        $fromFooter = $companyInfoFromFooter['email'] ?? null;
        if (is_string($fromFooter) && trim($fromFooter) !== '') {
            return trim($fromFooter);
        }

        $fromUser = $tenant->email;
        if (is_string($fromUser) && trim($fromUser) !== '') {
            return trim($fromUser);
        }

        return null;
    }

    /**
     * When resolved email is non-empty, set WebsiteLayout.data.companyInfo.email (overrides placeholders / null from footer merge).
     *
     * @param  array<string, mixed>  $layout
     * @return array<string, mixed>
     */
    protected function applyResolvedEmailToWebsiteLayout(array $layout, ?string $resolvedEmail): array
    {
        if ($resolvedEmail === null || $resolvedEmail === '') {
            return $layout;
        }

        if (!isset($layout['companyInfo']) || !is_array($layout['companyInfo'])) {
            $layout['companyInfo'] = [];
        }

        $layout['companyInfo']['email'] = $resolvedEmail;

        return $layout;
    }

    /**
     * When resolved email is non-empty, set globalComponentsData.footer.content.contactInfo.email (creates path if missing).
     *
     * @param  array<string, mixed>  $globalData
     * @return array<string, mixed>
     */
    protected function applyResolvedEmailToGlobalComponentsData(array $globalData, ?string $resolvedEmail): array
    {
        if ($resolvedEmail === null || $resolvedEmail === '') {
            return $globalData;
        }

        if (!isset($globalData['footer']) || !is_array($globalData['footer'])) {
            $globalData['footer'] = [];
        }

        if (!isset($globalData['footer']['content']) || !is_array($globalData['footer']['content'])) {
            $globalData['footer']['content'] = [];
        }

        if (!isset($globalData['footer']['content']['contactInfo']) || !is_array($globalData['footer']['content']['contactInfo'])) {
            $globalData['footer']['content']['contactInfo'] = [];
        }

        $globalData['footer']['content']['contactInfo']['email'] = $resolvedEmail;

        return $globalData;
    }

    /**
     * Seed website layout for the tenant
     *
     * @param User $tenant
     * @param array $layout
     * @return void
     */
    protected function seedWebsiteLayout(User $tenant, array $layout): void
    {
        if (empty($layout['metaTags']['pages'])) {
            $layout['metaTags'] = [
                'pages' => config('tenant_website_default_meta.pages', []),
            ];
        }

        TenantWebsiteLayout::updateOrCreate(
            [
                'user_id' => $tenant->id,
            ],
            [
                'data' => $layout,
            ]
        );
    }

    /**
     * Inject onboarding data (logo, company name) into template
     *
     * @param array $template
     * @param \App\Models\User\BasicSetting $basicSetting
     * @param User $tenant
     * @return array
     */
    protected function injectOnboardingData(array $template, $basicSetting, User $tenant): array
    {
        $logoUrl = $basicSetting->logo ? $basicSetting->logo : null;
        $companyName = $basicSetting->company_name ?: 'تعاريف العقارية';

        // Get user contact info
        $userPhone = $tenant->phone ?: null;
        $userEmail = $tenant->email ?: null;
        $userAddress = $tenant->address ?: null;

        // Prepare replacement data
        $replacementData = [
            'logoUrl' => $logoUrl,
            'companyName' => $companyName,
            'phone' => $userPhone,
            'email' => $userEmail,
            'address' => $userAddress,
        ];

        // Replace in pages
        if (isset($template['componentSettings'])) {
            $template['componentSettings'] = $this->replaceInArray($template['componentSettings'], $replacementData);
        }

        // Replace in global components
        if (isset($template['globalComponentsData'])) {
            $template['globalComponentsData'] = $this->replaceInArray($template['globalComponentsData'], $replacementData);
        }

		// Replace in website layout if present
		if (isset($template['WebsiteLayout']) && is_array($template['WebsiteLayout'])) {
			$template['WebsiteLayout'] = $this->replaceInArray($template['WebsiteLayout'], $replacementData);
		}

        return $template;
    }

    /**
     * Recursively replace logo, company name, and contact info in array
     *
     * @param mixed $data
     * @param array $replacementData
     * @return mixed
     */
    protected function replaceInArray($data, array $replacementData)
    {
        if (is_array($data)) {
            // Check if this is a 'companyInfo' array and replace its logo (string URL)
            if (isset($data['companyInfo']) && is_array($data['companyInfo'])) {
                if (isset($data['companyInfo']['logo']) && $replacementData['logoUrl']) {
                    $data['companyInfo']['logo'] = $replacementData['logoUrl'];
                }
            }

            // Check if this is a 'logo' array with 'image' and/or 'text' key
            if (isset($data['logo']) && is_array($data['logo'])) {
                // Replace logo image
                if (isset($data['logo']['image']) && $replacementData['logoUrl']) {
                    $data['logo']['image'] = $replacementData['logoUrl'];
                }

                // Replace company name in logo text
                if (isset($data['logo']['text']) && $data['logo']['text'] === 'تعاريف العقارية' && $replacementData['companyName']) {
                    $data['logo']['text'] = $replacementData['companyName'];
                }
            }

            // Replace company name in 'text' key (any level)
            if (isset($data['text']) && $data['text'] === 'تعاريف العقارية' && $replacementData['companyName']) {
                $data['text'] = $replacementData['companyName'];
            }

            // Replace company name in 'name' key (any level)
            if (isset($data['name']) && $data['name'] === 'تعاريف العقارية' && $replacementData['companyName']) {
                $data['name'] = $replacementData['companyName'];
            }

            // Replace phone number
            if (isset($data['phone']) && $replacementData['phone']) {
                // Check for default phone patterns
                if (in_array($data['phone'], ['+966 5XXXXXXXX', '5XXXXXXXX', '5XXXXXXXX'])) {
                    $data['phone'] = $replacementData['phone'];
                }
            }
            if (isset($data['phone1']) && $replacementData['phone']) {
                if (in_array($data['phone1'], ['+966 5XXXXXXXX', '5XXXXXXXX'])) {
                    $data['phone1'] = $replacementData['phone'];
                }
            }
            if (isset($data['phone2']) && $replacementData['phone']) {
                if (in_array($data['phone2'], ['0537180774'])) {
                    $data['phone2'] = $replacementData['phone'];
                }
            }

            // Replace email (template placeholders)
            if (isset($data['email']) && $replacementData['email']) {
                if (in_array($data['email'], self::EMAIL_PLACEHOLDERS, true)) {
                    $data['email'] = $replacementData['email'];
                }
            }

            // Replace address
            if (isset($data['address']) && $replacementData['address']) {
                if (in_array($data['address'], ['المملكة العربية السعودية'])) {
                    $data['address'] = $replacementData['address'];
                }
            }

            // Recursively process all array elements
            foreach ($data as $key => $value) {
                $data[$key] = $this->replaceInArray($value, $replacementData);
            }
        }

        return $data;
    }
}
