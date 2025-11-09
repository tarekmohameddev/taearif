<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Marketing;

use App\Models\BasicSetting;
use App\Models\Language;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateWhatsAppSettingsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_whatsapp_settings(): void
    {
        [$language, $settings] = $this->ensureDefaultLanguageWithSettings();

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.marketing.whatsapp.settings.update'),
            [
                'service' => 'evolution_api',
                'notifications_enabled' => true,
                'meta' => [
                    'access_token' => 'meta-token-6789',
                    'phone_number_id' => '1234567890',
                    'business_account_id' => 'BUS-54321',
                ],
                'evolution' => [
                    'api_url' => 'https://evolution.example.com',
                    'api_key' => 'evolution-key-123',
                    'instance_name' => 'instance-001',
                    'phone_number' => '+966500000000',
                ],
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.service', 'evolution_api')
            ->assertJsonPath('data.notifications_enabled', true)
            ->assertJsonPath('data.meta.phone_number_id', '1234567890')
            ->assertJsonPath('data.meta.business_account_id', 'BUS-54321')
            ->assertJsonPath('data.meta.access_token', '***6789')
            ->assertJsonPath('data.evolution.api_url', 'https://evolution.example.com')
            ->assertJsonPath('data.evolution.instance_name', 'instance-001')
            ->assertJsonPath('data.evolution.phone_number', '+966500000000');

        $freshSettings = $settings->fresh();

        $this->assertSame($language->id, $freshSettings->language_id);
        $this->assertSame('evolution_api', $freshSettings->whatsapp_service);
        $this->assertEquals(1, (int) $freshSettings->whatsapp_notifications_enabled);
        $this->assertSame('meta-token-6789', $freshSettings->meta_access_token);
        $this->assertSame('1234567890', $freshSettings->meta_phone_number_id);
        $this->assertSame('BUS-54321', $freshSettings->meta_business_account_id);
        $this->assertSame('https://evolution.example.com', $freshSettings->evolution_api_url);
        $this->assertSame('evolution-key-123', $freshSettings->evolution_api_key);
        $this->assertSame('instance-001', $freshSettings->evolution_instance_name);
        $this->assertSame('+966500000000', $freshSettings->evolution_phone_number);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $this->putJson(
            route('admin.api.marketing.whatsapp.settings.update'),
            [
                'service' => 'meta',
            ]
        )->assertUnauthorized();
    }

    private function ensureDefaultLanguageWithSettings(): array
    {
        $language = Language::query()->where('is_default', 1)->first();

        if (!$language) {
            $language = Language::create([
                'name' => 'English',
                'code' => 'en',
                'is_default' => 1,
                'rtl' => 0,
            ]);
        } elseif ($language->is_default != 1) {
            $language->update(['is_default' => 1]);
        }

        $settings = BasicSetting::query()->where('language_id', $language->id)->first();

        if (!$settings) {
            $settings = BasicSetting::create([
                'language_id' => $language->id,
            ]);
        }

        return [$language, $settings];
    }
}

