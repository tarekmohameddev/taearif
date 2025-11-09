<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Marketing;

use App\Domain\Marketing\Models\WhatsAppTemplate;
use App\Models\BasicSetting;
use App\Models\Language;
use Illuminate\Support\Str;
use Tests\Feature\Admin\AdminApiTestCase;

class ManageMarketingTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_view_marketing_overview(): void
    {
        $this->signInAdmin();

        WhatsAppTemplate::factory()->count(2)->create([
            'status' => true,
        ]);

        WhatsAppTemplate::factory()->create([
            'status' => false,
        ]);

        $response = $this->getJson(route('admin.api.marketing.index'));

        $response->assertOk()
            ->assertJsonPath('data.whatsapp.total_templates', 3)
            ->assertJsonPath('data.whatsapp.active_templates', 2)
            ->assertJsonPath('data.whatsapp.inactive_templates', 1);
    }

    /** @test */
    public function admin_can_view_marketing_statistics(): void
    {
        $this->signInAdmin();

        WhatsAppTemplate::factory()->create([
            'language' => 'en',
            'status' => true,
        ]);

        WhatsAppTemplate::factory()->create([
            'language' => 'ar',
            'status' => false,
        ]);

        $response = $this->getJson(route('admin.api.marketing.statistics'));

        $response->assertOk()
            ->assertJsonPath('data.whatsapp_templates.total', 2)
            ->assertJsonPath('data.whatsapp_templates.active', 1)
            ->assertJsonPath('data.whatsapp_templates.inactive', 1)
            ->assertJsonPath('data.whatsapp_templates.by_language.en', 1)
            ->assertJsonPath('data.whatsapp_templates.by_language.ar', 1);
    }

    /** @test */
    public function admin_can_list_whatsapp_templates(): void
    {
        $this->signInAdmin();

        WhatsAppTemplate::factory()->count(3)->create();

        $response = $this->getJson(route('admin.api.marketing.whatsapp.templates.index'));

        $response->assertOk()
            ->assertJsonPath('data.data.0.id', WhatsAppTemplate::first()->id);
    }

    /** @test */
    public function listing_whatsapp_templates_requires_authentication(): void
    {
        $this->getJson(route('admin.api.marketing.whatsapp.templates.index'))
            ->assertUnauthorized();
    }

    /** @test */
    public function admin_can_create_a_whatsapp_template(): void
    {
        $this->signInAdmin();

        $payload = [
            'name' => 'template_' . Str::random(6),
            'content' => 'Hello {{name}}',
            'type' => 'notification',
            'language' => 'en',
        ];

        $response = $this->postJson(
            route('admin.api.marketing.whatsapp.templates.store'),
            $payload
        );

        $response->assertCreated()
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.language', 'en');

        $this->assertDatabaseHas('whatsapp_templates', [
            'name' => $payload['name'],
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_when_creating_template_with_invalid_payload(): void
    {
        $this->signInAdmin();

        $this->postJson(
            route('admin.api.marketing.whatsapp.templates.store'),
            [
                'name' => '',
                'content' => '',
                'type' => null,
                'language' => 'fr',
            ]
        )->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'content', 'type', 'language']);
    }

    /** @test */
    public function admin_can_view_a_whatsapp_template(): void
    {
        $this->signInAdmin();

        $template = WhatsAppTemplate::factory()->create([
            'name' => 'view_template',
        ]);

        $response = $this->getJson(
            route('admin.api.marketing.whatsapp.templates.show', $template->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.name', 'view_template');
    }

    /** @test */
    public function viewing_template_requires_authentication(): void
    {
        $template = WhatsAppTemplate::factory()->create();

        $this->getJson(
            route('admin.api.marketing.whatsapp.templates.show', $template->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_viewing_missing_template(): void
    {
        $this->signInAdmin();

        $this->getJson(
            route('admin.api.marketing.whatsapp.templates.show', 999999)
        )->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }

    /** @test */
    public function admin_can_toggle_template_status(): void
    {
        $this->signInAdmin();

        $template = WhatsAppTemplate::factory()->create([
            'status' => false,
        ]);

        $response = $this->postJson(
            route('admin.api.marketing.whatsapp.templates.toggle-status', $template->id)
        );

        $response->assertOk()
            ->assertJsonPath('data.status', true);

        $this->assertTrue($template->fresh()->status);
    }

    /** @test */
    public function toggling_template_status_requires_authentication(): void
    {
        $template = WhatsAppTemplate::factory()->create();

        $this->postJson(
            route('admin.api.marketing.whatsapp.templates.toggle-status', $template->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_delete_whatsapp_template(): void
    {
        $this->signInAdmin();

        $template = WhatsAppTemplate::factory()->create();

        $response = $this->deleteJson(
            route('admin.api.marketing.whatsapp.templates.destroy', $template->id)
        );

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseMissing('whatsapp_templates', [
            'id' => $template->id,
        ]);
    }

    /** @test */
    public function deleting_template_requires_authentication(): void
    {
        $template = WhatsAppTemplate::factory()->create();

        $this->deleteJson(
            route('admin.api.marketing.whatsapp.templates.destroy', $template->id)
        )->assertUnauthorized();
    }

    /** @test */
    public function admin_can_view_whatsapp_settings(): void
    {
        $this->ensureDefaultLanguageAndSettings();
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.marketing.whatsapp.settings'));

        $response->assertOk()
            ->assertJsonPath('data.service', 'meta')
            ->assertJsonPath('data.notifications_enabled', false);
    }

    /** @test */
    public function admin_can_view_automated_messages(): void
    {
        $this->ensureDefaultLanguageAndSettings();
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.marketing.automated-messages'));

        $response->assertOk()
            ->assertJsonPath('data.welcome.type', 'welcome')
            ->assertJsonPath('data.password_reset.type', 'password_reset');
    }

    /** @test */
    public function admin_can_view_automated_message_by_type(): void
    {
        $this->ensureDefaultLanguageAndSettings();
        $this->signInAdmin();

        $response = $this->getJson(
            route('admin.api.marketing.automated-messages.show', 'welcome')
        );

        $response->assertOk()
            ->assertJsonPath('data.type', 'welcome');
    }

    private function ensureDefaultLanguageAndSettings(): void
    {
        $language = Language::query()->where('is_default', 1)->first();
        if (!$language) {
            $language = Language::create([
                'name' => 'English',
                'code' => 'en',
                'is_default' => 1,
                'rtl' => 0,
            ]);
        }

        BasicSetting::query()->firstOrCreate([
            'language_id' => $language->id,
        ], [
            'whatsapp_notifications_enabled' => false,
        ]);
    }
}

