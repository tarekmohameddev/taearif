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

class TenantWebsiteSeeder
{
    /**
     * Maximum number of retry attempts for API calls
     */
    protected const MAX_RETRIES = 3;

    /**
     * API request timeout in seconds
     */
    protected const TIMEOUT = 10;

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

        $attempt = 1;
        $lastError = null;

        // Try up to MAX_RETRIES times
        while ($attempt <= self::MAX_RETRIES) {
            try {
                Log::info("Fetching default data from API (attempt {$attempt}/" . self::MAX_RETRIES . ")", [
                    'url' => $apiUrl,
                ]);

                $response = Http::timeout(self::TIMEOUT)
                    ->retry(1, 100) // Internal retry with 100ms delay
                    ->get($apiUrl);

                if ($response->successful()) {
                    $data = $response->json();

                    // Validate the response structure
                    if (isset($data['componentSettings']) && isset($data['globalComponentsData'])) {
                        Log::info("Successfully fetched default data from API on attempt {$attempt}");
                        return $data;
                    } else {
                        $lastError = 'Invalid API response structure: missing required keys';
                        Log::warning($lastError, ['response' => $data]);
                    }
                } else {
                    $lastError = "API request failed with status {$response->status()}";
                    Log::warning($lastError, [
                        'attempt' => $attempt,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                }
            } catch (\Exception $e) {
                $lastError = $e->getMessage();
                Log::warning("API request failed on attempt {$attempt}", [
                    'error' => $e->getMessage(),
                    'url' => $apiUrl,
                ]);
            }

            $attempt++;

            // Wait before next retry (exponential backoff)
            if ($attempt <= self::MAX_RETRIES) {
                $waitTime = pow(2, $attempt - 1); // 2, 4, 8 seconds
                sleep($waitTime);
            }
        }

        // All retries failed, fall back to local config
        Log::error('All API retry attempts failed, falling back to local config', [
            'last_error' => $lastError,
            'attempts' => self::MAX_RETRIES,
        ]);

        return config('tenant_website_defaults');
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

                // Seed global components
                $this->seedGlobalComponents($tenant, $template['globalComponentsData']);

                // Seed website layout if provided
                if (isset($template['WebsiteLayout']) && is_array($template['WebsiteLayout'])) {
                    $this->seedWebsiteLayout($tenant, $template['WebsiteLayout']);
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
        foreach ($staticPages as $pageId => $components) {
            // Sort components by position
            $sortedComponents = collect($components)
                ->sortBy('position')
                ->values()
                ->all();

            TenantStaticPage::updateOrCreate(
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

                // Get onboarding data from BasicSetting
                $basicSetting = \App\Models\User\BasicSetting::where('user_id', $tenant->id)->first();
                $brandingColors = null;

                if ($basicSetting) {
                    // Inject onboarding data into template
                    $template = $this->injectOnboardingData($template, $basicSetting, $tenant);
                    $brandingColors = $this->extractBrandingColorsFromBasicSetting($basicSetting);
                }

                $companyInfoFromFooter = $this->extractCompanyInfoForWebsiteLayoutFromFooter($tenant);

                // Update/recreate pages
                $this->seedPages($tenant, $template['componentSettings']);

                // Update/recreate static pages if provided
                if (isset($template['StaticPages']) && is_array($template['StaticPages'])) {
                    $this->seedStaticPages($tenant, $template['StaticPages']);
                }

                // Update/recreate global components
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

                    $this->seedWebsiteLayout($tenant, $websiteLayout);
                } elseif (!empty($brandingColors) || $companyInfoFromFooter !== null) {
                    // Keep existing layout data; merge onboarding colors and footer company info.
                    $this->mergeIntoExistingWebsiteLayout($tenant, $brandingColors ?? [], $companyInfoFromFooter);
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
     * Read address / working hours from api_footer_settings (same source as onboarding FooterSetting).
     *
     * @return array{address: mixed, workingHours: mixed}|null
     */
    protected function extractCompanyInfoForWebsiteLayoutFromFooter(User $tenant): ?array
    {
        $footer = FooterSetting::where('user_id', $tenant->id)->first();
        if (!$footer || !is_array($footer->general)) {
            return null;
        }

        $general = $footer->general;

        return [
            'address' => $general['address'] ?? null,
            'workingHours' => $general['workingHours'] ?? null,
        ];
    }

    /**
     * Merge WebsiteLayout.companyInfo from footer general (mirrors onboarding persistence).
     *
     * @param  array<string, mixed>  $layout
     * @param  array{address: mixed, workingHours: mixed}|null  $companyInfo
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

        $layout['companyInfo']['address'] = $companyInfo['address'];
        $layout['companyInfo']['workingHours'] = $companyInfo['workingHours'];

        return $layout;
    }

    /**
     * Merge branding colors and footer company info into existing tenant layout when template has no WebsiteLayout.
     *
     * @param  array<string, string>  $brandingColors
     * @param  array{address: mixed, workingHours: mixed}|null  $companyInfoFromFooter
     */
    protected function mergeIntoExistingWebsiteLayout(User $tenant, array $brandingColors, ?array $companyInfoFromFooter): void
    {
        $layout = TenantWebsiteLayout::firstOrNew(['user_id' => $tenant->id]);
        $existingData = is_array($layout->data) ? $layout->data : [];

        if (!empty($brandingColors)) {
            $existingData = $this->applyBrandingColorsToWebsiteLayout($existingData, $brandingColors);
        }

        $existingData = $this->applyCompanyInfoToWebsiteLayout($existingData, $companyInfoFromFooter);

        $layout->data = $existingData;
        $layout->save();
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

            // Replace email
            if (isset($data['email']) && $replacementData['email']) {
                if (in_array($data['email'], ['info@example.com'])) {
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
