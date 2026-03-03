<?php

declare(strict_types=1);

namespace Tests\Feature\Api\V1\Email;

use App\Models\EmailCampaignTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use DatabaseTransactions;

    private function requireEmailTemplateTables(): void
    {
        if (!Schema::hasTable('email_campaign_templates')) {
            $this->markTestSkipped('email_campaign_templates table required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create(['account_type' => 'tenant', 'tenant_id' => null]);
    }

    private function baseUrl(): string
    {
        return '/api/v1/email';
    }

    /** @test */
    public function test_it_can_list_templates(): void
    {
        $this->requireEmailTemplateTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();

        EmailCampaignTemplate::create([
            'user_id' => $tenantA->id,
            'name' => 'Template A',
            'subject' => 'Subject A',
            'body_html' => '<p>Body A</p>',
            'is_active' => true,
        ]);
        EmailCampaignTemplate::create([
            'user_id' => $tenantB->id,
            'name' => 'Template B',
            'subject' => 'Subject B',
            'body_html' => '<p>Body B</p>',
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenantA);
        $res = $this->getJson($this->baseUrl() . '/templates')->assertOk();
        $items = $res->json('data.data');
        $this->assertNotNull($items);
        $this->assertCount(1, $items);
        $this->assertSame('Template A', $items[0]['name']);
    }

    /** @test */
    public function test_it_can_list_templates_with_is_active_filter(): void
    {
        $this->requireEmailTemplateTables();

        $tenant = $this->createTenant();
        EmailCampaignTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'Active Template',
            'subject' => 'S1',
            'body_html' => '<p>B1</p>',
            'is_active' => true,
        ]);
        EmailCampaignTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'Inactive Template',
            'subject' => 'S2',
            'body_html' => '<p>B2</p>',
            'is_active' => false,
        ]);

        Sanctum::actingAs($tenant);
        $res = $this->getJson($this->baseUrl() . '/templates?is_active=1')->assertOk();
        $items = $res->json('data.data');
        $this->assertNotNull($items);
        $this->assertCount(1, $items);
        $this->assertSame('Active Template', $items[0]['name']);
    }

    /** @test */
    public function test_it_can_show_a_template(): void
    {
        $this->requireEmailTemplateTables();

        $tenant = $this->createTenant();
        $template = EmailCampaignTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'Show Me',
            'subject' => 'Subject',
            'body_html' => '<p>Body</p>',
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenant);
        $this->getJson($this->baseUrl() . '/templates/' . $template->id)
            ->assertOk()
            ->assertJsonPath('data.id', $template->id)
            ->assertJsonPath('data.name', 'Show Me');
    }

    /** @test */
    public function test_it_returns_404_when_showing_template_belonging_to_another_tenant(): void
    {
        $this->requireEmailTemplateTables();

        $tenantA = $this->createTenant();
        $tenantB = $this->createTenant();
        $template = EmailCampaignTemplate::create([
            'user_id' => $tenantA->id,
            'name' => 'Other Tenant',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenantB);
        $this->getJson($this->baseUrl() . '/templates/' . $template->id)
            ->assertStatus(404)
            ->assertJsonPath('code', 'TEMPLATE_NOT_FOUND');
    }

    /** @test */
    public function test_it_can_store_a_template(): void
    {
        $this->requireEmailTemplateTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $res = $this->postJson($this->baseUrl() . '/templates', [
            'name' => 'New Template',
            'subject' => 'Welcome',
            'body_html' => '<p>Hello</p>',
            'body_text' => 'Hello',
            'is_active' => true,
        ])->assertStatus(201);

        $this->assertDatabaseHas('email_campaign_templates', [
            'user_id' => $tenant->id,
            'name' => 'New Template',
            'subject' => 'Welcome',
        ]);
        $this->assertNotNull($res->json('data.id'));
    }

    /** @test */
    public function test_it_can_update_a_template(): void
    {
        $this->requireEmailTemplateTables();

        $tenant = $this->createTenant();
        $template = EmailCampaignTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'Original',
            'subject' => 'S1',
            'body_html' => '<p>B1</p>',
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenant);
        $this->patchJson($this->baseUrl() . '/templates/' . $template->id, [
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ])->assertOk();

        $this->assertDatabaseHas('email_campaign_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
        ]);
    }

    /** @test */
    public function test_it_validates_store_request(): void
    {
        $this->requireEmailTemplateTables();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->postJson($this->baseUrl() . '/templates', [
            'name' => '',
            'subject' => '',
            'body_html' => '',
        ])->assertStatus(422);
    }

    /** @test */
    public function test_it_can_delete_a_template(): void
    {
        $this->requireEmailTemplateTables();

        $tenant = $this->createTenant();
        $template = EmailCampaignTemplate::create([
            'user_id' => $tenant->id,
            'name' => 'To Delete',
            'subject' => 'S',
            'body_html' => '<p>B</p>',
            'is_active' => true,
        ]);

        Sanctum::actingAs($tenant);
        $this->deleteJson($this->baseUrl() . '/templates/' . $template->id)->assertOk();

        $this->assertDatabaseMissing('email_campaign_templates', ['id' => $template->id]);
    }
}
