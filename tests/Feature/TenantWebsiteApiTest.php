<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\TenantPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TenantWebsiteApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createTenant(string $username = 'acme'): User
    {
        $user = User::factory()->create();
        $user->username = $username;
        $user->save();
        return $user;
    }

    public function test_get_tenant_returns_204_when_not_found(): void
    {
        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'nope'])
            ->assertStatus(204);
    }

    public function test_get_tenant_returns_pages_and_globals(): void
    {
        $tenant = $this->createTenant();
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => [['id' => 'c1', 'position' => 0]]]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => ['header' => []]]);
        TenantWebsiteLayout::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => ['metaTags' => ['pages' => []]]]);

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('username', 'acme')
            ->assertJsonPath('componentSettings.homepage.0.id', 'c1')
            ->assertJsonPath('globalComponentsData.header', [])
            ->assertJsonPath('WebsiteLayout.metaTags.pages', []);
    }

    public function test_save_pages_requires_auth(): void
    {
        $tenant = $this->createTenant();
        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => ['homepage' => []],
        ])->assertStatus(401);
    }

    public function test_save_pages_succeeds_for_owner(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');
        $resp = $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => [
                'homepage' => [
                    ['id' => 'c1', 'type' => 'hero', 'name' => 'Hero', 'componentName' => 'hero1', 'data' => [], 'position' => 0],
                ],
            ],
            'globalComponentsData' => ['header' => []],
            'WebsiteLayout' => ['metaTags' => ['pages' => [['TitleAr' => 'الرئيسية', 'path' => '/']]]],
        ])->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('tenant_pages', ['user_id' => $tenant->id, 'page_id' => 'homepage']);
        $this->assertDatabaseHas('tenant_global_components', ['user_id' => $tenant->id]);
        $this->assertDatabaseHas('tenant_website_layouts', ['user_id' => $tenant->id]);
    }

    public function test_public_get_pages_and_single_page(): void
    {
        $tenant = $this->createTenant();
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'home', 'components' => []]);
        $this->getJson('/api/v1/tenant-website/acme/pages')->assertOk();
        $this->getJson('/api/v1/tenant-website/acme/pages/home')->assertOk()
            ->assertJsonPath('pageId', 'home');
    }

    public function test_media_upload(): void
    {
        Storage::fake('public');
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');
        $file = UploadedFile::fake()->image('logo.png');
        $this->postJson('/api/v1/tenant-website/acme/media', ['file' => $file])
            ->assertOk()
            ->assertJsonStructure(['id','url','mime','size']);
    }

    public function test_publish_increments_version(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');
        $this->postJson('/api/v1/tenant-website/acme/publish')
            ->assertOk()
            ->assertJsonStructure(['success','version','publishedAt']);
    }

    public function test_contact_form_public(): void
    {
        $tenant = $this->createTenant();
        $this->postJson('/api/v1/tenant-website/acme/forms/contact', [
            'name' => 'John', 'message' => 'Hello'
        ])->assertOk()->assertJsonStructure(['success','id']);
    }

    public function test_components_catalog_public(): void
    {
        $this->getJson('/api/v1/tenant-website/components/catalog')->assertOk();
    }
}


