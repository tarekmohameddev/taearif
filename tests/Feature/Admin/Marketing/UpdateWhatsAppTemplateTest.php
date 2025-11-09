<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Marketing;

use App\Domain\Marketing\Models\WhatsAppTemplate;
use Tests\Feature\Admin\AdminApiTestCase;

class UpdateWhatsAppTemplateTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_update_a_whatsapp_template(): void
    {
        $template = WhatsAppTemplate::factory()->create([
            'name' => 'Initial Template',
            'content' => 'Old content',
            'status' => true,
        ]);

        $this->signInAdmin();

        $updatedContent = 'Hello {{name}}, your subscription has been updated.';

        $response = $this->putJson(
            route('admin.api.marketing.whatsapp.templates.update', $template->id),
            [
                'name' => 'Updated Template',
                'description' => 'Updated description',
                'content' => $updatedContent,
                'language' => 'en',
                'status' => false,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.name', 'Updated Template')
            ->assertJsonPath('data.description', 'Updated description')
            ->assertJsonPath('data.content', $updatedContent)
            ->assertJsonPath('data.language', 'en')
            ->assertJsonPath('data.status', false)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonPath('data.character_count', mb_strlen($updatedContent));

        $this->assertDatabaseHas('whatsapp_templates', [
            'id' => $template->id,
            'name' => 'Updated Template',
            'description' => 'Updated description',
            'content' => $updatedContent,
            'language' => 'en',
            'status' => 0,
        ]);
    }

    /** @test */
    public function validation_errors_are_returned_for_invalid_template_payload(): void
    {
        $template = WhatsAppTemplate::factory()->create([
            'language' => 'en',
        ]);

        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.marketing.whatsapp.templates.update', $template->id),
            [
                'language' => 'es',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);
    }

    /** @test */
    public function unauthenticated_requests_are_rejected(): void
    {
        $template = WhatsAppTemplate::factory()->create();

        $response = $this->putJson(
            route('admin.api.marketing.whatsapp.templates.update', $template->id),
            [
                'name' => 'Should Fail',
            ]
        );

        $response->assertUnauthorized();
    }

    /** @test */
    public function not_found_is_returned_when_template_does_not_exist(): void
    {
        $this->signInAdmin();

        $response = $this->putJson(
            route('admin.api.marketing.whatsapp.templates.update', 999999),
            [
                'name' => 'Missing Template',
            ]
        );

        $response->assertNotFound()
            ->assertJsonPath('code', 'NOT_FOUND');
    }
}

