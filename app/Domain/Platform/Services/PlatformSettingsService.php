<?php

namespace App\Domain\Platform\Services;

use App\Domain\Shared\Services\BaseService;
use App\Models\BasicSetting;
use App\Models\BasicExtended;
use App\Models\Seo;
use App\Exceptions\BusinessLogicException;

/**
 * Platform Settings Service
 *
 * Handles platform-wide system settings management
 */
class PlatformSettingsService extends BaseService
{
    /**
     * Get settings by section
     *
     * @param string $section
     * @return array
     * @throws BusinessLogicException
     */
    public function getSettings(string $section): array
    {
        return match($section) {
            'general' => $this->getGeneralSettings(),
            'email' => $this->getEmailSettings(),
            'whatsapp' => $this->getWhatsAppSettings(),
            'seo' => $this->getSeoSettings(),
            'maintenance' => $this->getMaintenanceSettings(),
            'appearance' => $this->getAppearanceSettings(),
            default => throw new BusinessLogicException("Invalid section: {$section}", 'INVALID_SECTION', 404),
        };
    }

    /**
     * Update settings by section
     *
     * @param string $section
     * @param array $data
     * @return array
     * @throws BusinessLogicException
     */
    public function updateSettings(string $section, array $data): array
    {
        return $this->executeInTransaction(function () use ($section, $data) {
            return match($section) {
                'general' => $this->updateGeneralSettings($data),
                'email' => $this->updateEmailSettings($data),
                'whatsapp' => $this->updateWhatsAppSettings($data),
                'seo' => $this->updateSeoSettings($data),
                'maintenance' => $this->updateMaintenanceSettings($data),
                'appearance' => $this->updateAppearanceSettings($data),
                default => throw new BusinessLogicException("Invalid section: {$section}", 'INVALID_SECTION', 404),
            };
        });
    }

    /**
     * Get general settings
     */
    protected function getGeneralSettings(): array
    {
        $bs = BasicSetting::first();
        $be = BasicExtended::first();

        return [
            'website_title' => $bs?->website_title,
            'timezone' => $be?->timezone ?? $bs?->timezone,
            'currency' => [
                'symbol' => $be?->base_currency_symbol,
                'symbol_position' => $be?->base_currency_symbol_position,
                'text' => $be?->base_currency_text,
                'text_position' => $be?->base_currency_text_position,
                'rate' => $be?->base_currency_rate ? (float) $be->base_currency_rate : 1.0,
            ],
            'email_verification_status' => (bool) $bs?->email_verification_status,
            'base_color' => $bs?->base_color,
        ];
    }

    /**
     * Update general settings
     */
    protected function updateGeneralSettings(array $data): array
    {
        $bss = BasicSetting::all();
        foreach ($bss as $bs) {
            if (isset($data['website_title'])) $bs->website_title = $data['website_title'];
            if (isset($data['email_verification_status'])) $bs->email_verification_status = $data['email_verification_status'];
            if (isset($data['base_color'])) $bs->base_color = str_replace('#', '', $data['base_color']);
            if (isset($data['timezone'])) $bs->timezone = $data['timezone'];
            $bs->save();
        }

        $bes = BasicExtended::all();
        foreach ($bes as $be) {
            if (isset($data['timezone'])) $be->timezone = $data['timezone'];
            if (isset($data['currency'])) {
                if (isset($data['currency']['symbol'])) $be->base_currency_symbol = $data['currency']['symbol'];
                if (isset($data['currency']['symbol_position'])) $be->base_currency_symbol_position = $data['currency']['symbol_position'];
                if (isset($data['currency']['text'])) $be->base_currency_text = $data['currency']['text'];
                if (isset($data['currency']['text_position'])) $be->base_currency_text_position = $data['currency']['text_position'];
                if (isset($data['currency']['rate'])) $be->base_currency_rate = $data['currency']['rate'];
            }
            $be->save();
        }

        // Update environment timezone if changed
        if (isset($data['timezone'])) {
            $this->updateEnvironmentVariable('TIMEZONE', $data['timezone']);
        }

        return $this->getGeneralSettings();
    }

    /**
     * Get email settings
     */
    protected function getEmailSettings(): array
    {
        $be = BasicExtended::first();

        return [
            'smtp_enabled' => (bool) $be?->is_smtp,
            'smtp' => [
                'host' => $be?->smtp_host,
                'port' => $be?->smtp_port,
                'username' => $be?->smtp_username,
                'password' => $be?->smtp_password ? '********' : null, // Masked
                'encryption' => $be?->encryption,
            ],
            'from' => [
                'email' => $be?->from_mail,
                'name' => $be?->from_name,
            ],
            'to' => [
                'email' => $be?->to_mail,
            ],
            'email_notifications_enabled' => (bool) $be?->email_notifications_enabled,
        ];
    }

    /**
     * Update email settings
     */
    protected function updateEmailSettings(array $data): array
    {
        $bes = BasicExtended::all();
        
        foreach ($bes as $be) {
            if (isset($data['smtp_enabled'])) $be->is_smtp = $data['smtp_enabled'];
            if (isset($data['email_notifications_enabled'])) $be->email_notifications_enabled = $data['email_notifications_enabled'];
            
            if (isset($data['smtp'])) {
                if (isset($data['smtp']['host'])) $be->smtp_host = $data['smtp']['host'];
                if (isset($data['smtp']['port'])) $be->smtp_port = $data['smtp']['port'];
                if (isset($data['smtp']['username'])) $be->smtp_username = $data['smtp']['username'];
                if (isset($data['smtp']['password']) && $data['smtp']['password'] !== '********') {
                    $be->smtp_password = $data['smtp']['password'];
                }
                if (isset($data['smtp']['encryption'])) $be->smtp_encryption = $data['smtp']['encryption'];
            }
            
            if (isset($data['from'])) {
                if (isset($data['from']['email'])) $be->from_mail = $data['from']['email'];
                if (isset($data['from']['name'])) $be->from_name = $data['from']['name'];
            }
            
            if (isset($data['to']['email'])) $be->to_mail = $data['to']['email'];
            
            $be->save();
        }

        return $this->getEmailSettings();
    }

    /**
     * Get WhatsApp settings
     */
    protected function getWhatsAppSettings(): array
    {
        $bs = BasicSetting::first();

        return [
            'enabled' => (bool) $bs?->whatsapp_notifications_enabled,
            'service' => $bs?->whatsapp_service ?? 'meta_cloud',
            'meta_cloud' => [
                'access_token' => $bs?->meta_access_token ? '********' : null, // Masked
                'phone_number_id' => $bs?->meta_phone_number_id,
                'business_account_id' => $bs?->meta_business_account_id,
                'template_name' => $bs?->meta_template_name,
                'template_language' => $bs?->meta_template_language,
            ],
            'evolution_api' => [
                'url' => $bs?->evolution_api_url,
                'key' => $bs?->evolution_api_key ? '********' : null, // Masked
                'instance_name' => $bs?->evolution_instance_name,
                'phone_number' => $bs?->evolution_phone_number,
            ],
            'whatsapp_number' => $bs?->whatsapp_number,
            'whatsapp_status' => (bool) $bs?->whatsapp_status,
        ];
    }

    /**
     * Update WhatsApp settings
     */
    protected function updateWhatsAppSettings(array $data): array
    {
        $bss = BasicSetting::all();
        
        foreach ($bss as $bs) {
            if (isset($data['enabled'])) $bs->whatsapp_notifications_enabled = $data['enabled'];
            if (isset($data['service'])) $bs->whatsapp_service = $data['service'];
            if (isset($data['whatsapp_number'])) $bs->whatsapp_number = $data['whatsapp_number'];
            if (isset($data['whatsapp_status'])) $bs->whatsapp_status = $data['whatsapp_status'];
            
            if (isset($data['meta_cloud'])) {
                if (isset($data['meta_cloud']['access_token']) && $data['meta_cloud']['access_token'] !== '********') {
                    $bs->meta_access_token = $data['meta_cloud']['access_token'];
                }
                if (isset($data['meta_cloud']['phone_number_id'])) $bs->meta_phone_number_id = $data['meta_cloud']['phone_number_id'];
                if (isset($data['meta_cloud']['business_account_id'])) $bs->meta_business_account_id = $data['meta_cloud']['business_account_id'];
                if (isset($data['meta_cloud']['template_name'])) $bs->meta_template_name = $data['meta_cloud']['template_name'];
                if (isset($data['meta_cloud']['template_language'])) $bs->meta_template_language = $data['meta_cloud']['template_language'];
            }
            
            if (isset($data['evolution_api'])) {
                if (isset($data['evolution_api']['url'])) $bs->evolution_api_url = $data['evolution_api']['url'];
                if (isset($data['evolution_api']['key']) && $data['evolution_api']['key'] !== '********') {
                    $bs->evolution_api_key = $data['evolution_api']['key'];
                }
                if (isset($data['evolution_api']['instance_name'])) $bs->evolution_instance_name = $data['evolution_api']['instance_name'];
                if (isset($data['evolution_api']['phone_number'])) $bs->evolution_phone_number = $data['evolution_api']['phone_number'];
            }
            
            $bs->save();
        }

        return $this->getWhatsAppSettings();
    }

    /**
     * Get SEO settings
     */
    protected function getSeoSettings(): array
    {
        $seo = Seo::first();

        return [
            'meta_keywords' => $seo?->meta_keywords,
            'meta_description' => $seo?->meta_description,
            'google_analytics' => $seo?->google_analytics,
            'facebook_pixel' => $seo?->facebook_pixel,
        ];
    }

    /**
     * Update SEO settings
     */
    protected function updateSeoSettings(array $data): array
    {
        $seos = Seo::all();
        
        foreach ($seos as $seo) {
            if (isset($data['meta_keywords'])) $seo->meta_keywords = $data['meta_keywords'];
            if (isset($data['meta_description'])) $seo->meta_description = $data['meta_description'];
            if (isset($data['google_analytics'])) $seo->google_analytics = $data['google_analytics'];
            if (isset($data['facebook_pixel'])) $seo->facebook_pixel = $data['facebook_pixel'];
            $seo->save();
        }

        return $this->getSeoSettings();
    }

    /**
     * Get maintenance settings
     */
    protected function getMaintenanceSettings(): array
    {
        $bs = BasicSetting::first();

        return [
            'enabled' => (bool) $bs?->maintenance_status,
            'message' => $bs?->maintainance_text,
            'image' => $bs?->maintenance_img ? asset('assets/admin/img/' . $bs->maintenance_img) : null,
            'secret_path' => $bs?->secret_path,
        ];
    }

    /**
     * Update maintenance settings
     */
    protected function updateMaintenanceSettings(array $data): array
    {
        $bss = BasicSetting::all();
        
        foreach ($bss as $bs) {
            if (isset($data['enabled'])) $bs->maintenance_status = $data['enabled'];
            if (isset($data['message'])) $bs->maintainance_text = $data['message'];
            if (isset($data['secret_path'])) $bs->secret_path = $data['secret_path'];
            $bs->save();
        }

        return $this->getMaintenanceSettings();
    }

    /**
     * Get appearance settings
     */
    protected function getAppearanceSettings(): array
    {
        $bs = BasicSetting::first();

        return [
            'base_color' => $bs?->base_color,
            'logo' => $bs?->logo ? asset('assets/admin/img/' . $bs->logo) : null,
            'footer_logo' => $bs?->footer_logo ? asset('assets/admin/img/' . $bs->footer_logo) : null,
            'favicon' => $bs?->favicon ? asset('assets/admin/img/' . $bs->favicon) : null,
            'preloader' => $bs?->preloader ? asset('assets/admin/img/' . $bs->preloader) : null,
        ];
    }

    /**
     * Update appearance settings
     */
    protected function updateAppearanceSettings(array $data): array
    {
        $bss = BasicSetting::all();
        
        foreach ($bss as $bs) {
            if (isset($data['base_color'])) $bs->base_color = str_replace('#', '', $data['base_color']);
            $bs->save();
        }

        return $this->getAppearanceSettings();
    }

    /**
     * Get all settings sections
     *
     * @return array
     */
    public function getAllSettings(): array
    {
        return [
            'general' => $this->getGeneralSettings(),
            'email' => $this->getEmailSettings(),
            'whatsapp' => $this->getWhatsAppSettings(),
            'seo' => $this->getSeoSettings(),
            'maintenance' => $this->getMaintenanceSettings(),
            'appearance' => $this->getAppearanceSettings(),
        ];
    }

    /**
     * Update environment variable
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    protected function updateEnvironmentVariable(string $key, string $value): void
    {
        try {
            $path = base_path('.env');
            if (file_exists($path)) {
                file_put_contents($path, preg_replace(
                    "/^{$key}=.*/m",
                    "{$key}={$value}",
                    file_get_contents($path)
                ));
                \Artisan::call('config:clear');
            }
        } catch (\Exception $e) {
            // Log but don't fail
            \Log::warning("Failed to update environment variable {$key}: " . $e->getMessage());
        }
    }
}

