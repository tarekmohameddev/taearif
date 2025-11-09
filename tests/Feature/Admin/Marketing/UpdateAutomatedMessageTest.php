<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Marketing;

use App\Models\BasicSetting;
use App\Models\Language;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateAutomatedMessageTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_welcome_automated_message(): void
    {
        [, $settings] = $this->ensureDefaultLanguageWithSettings();

        $settings->forceFill([
            'welcome_message_enabled' => 0,
            'welcome_message_text' => 'Old welcome text',
            'welcome_message_delay' => 5,
            'welcome_message_template' => 'old_template',
            'welcome_message_api' => 'meta',
        ])->save();

        $this->signInAdmin();

        $payload = [
            'enabled' => true,
            'text' => 'Welcome to Taearif!',
            'delay' => 15,
            'template' => 'welcome_template_v2',
            'api' => 'evolution',
        ];

        $response = $this->putJson(
            route('admin.api.marketing.automated-messages.update', 'welcome'),
            $payload
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'welcome')
            ->assertJsonPath('data.enabled', true)
            ->assertJsonPath('data.text', 'Welcome to Taearif!')
            ->assertJsonPath('data.delay', 15)
            ->assertJsonPath('data.template', 'welcome_template_v2')
            ->assertJsonPath('data.api', 'evolution');

        $settings->refresh();

        $this->assertEquals(1, (int) $settings->welcome_message_enabled);
        $this->assertSame('Welcome to Taearif!', $settings->welcome_message_text);
        $this->assertSame(15, (int) $settings->welcome_message_delay);
        $this->assertSame('welcome_template_v2', $settings->welcome_message_template);
        $this->assertSame('evolution', $settings->welcome_message_api);
    }

    /** @test */
    public function invalid_message_type_returns_error(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.marketing.automated-messages.update', 'unsupported'),
            []
        );

        $response->assertStatus(400)
            ->assertJsonPath('code', 'INVALID_AUTOMATED_MESSAGE_TYPE');
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $this->putJson(
            route('admin.api.marketing.automated-messages.update', 'welcome'),
            []
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

