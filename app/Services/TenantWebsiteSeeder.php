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

                if (!$template || !isset($template['componentSettings']) || !isset($template['globalComponentsData'])) {
                    Log::warning('Tenant website default template not found or invalid', [
                        'tenant_id' => $tenant->id,
                    ]);
                    return false;
                }

                // Seed pages
                $this->seedPages($tenant, $template['componentSettings']);

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
                $template = config('tenant_website_defaults');

                if (!$template || !isset($template['componentSettings']) || !isset($template['globalComponentsData'])) {
                    Log::warning('Tenant website default template not found for reseed', [
                        'tenant_id' => $tenant->id,
                    ]);
                    return false;
                }

                // Get onboarding data from BasicSetting
                $basicSetting = \App\Models\User\BasicSetting::where('user_id', $tenant->id)->first();

                if ($basicSetting) {
                    // Inject onboarding data into template
                    $template = $this->injectOnboardingData($template, $basicSetting, $tenant);
                }

                // Update/recreate pages
                $this->seedPages($tenant, $template['componentSettings']);

                // Update/recreate global components
                $this->seedGlobalComponents($tenant, $template['globalComponentsData']);

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
