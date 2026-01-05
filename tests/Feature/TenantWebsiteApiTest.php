<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\TenantPage;
use App\Models\TenantStaticPage;
use App\Models\TenantGlobalComponent;
use App\Models\TenantWebsiteLayout;
use App\Models\User\BasicSetting;
use App\Models\TenantSetting;
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
        BasicSetting::create([
            'user_id' => $tenant->id,
            'company_name' => 'Acme Co',
            'logo' => '/logo.png',
        ]);
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => [['id' => 'c1', 'position' => 0]]]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => ['header' => []]]);
        TenantWebsiteLayout::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => ['metaTags' => ['pages' => []]]]);

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('username', 'acme')
            ->assertJsonPath('branding.logo', url('/logo.png'))
            ->assertJsonPath('branding.name', 'Acme Co')
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

    public function test_save_pages_with_themes_backup(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');
        $themesBackup = [
            'theme1' => ['colors' => ['primary' => '#000000']],
            'theme2' => ['colors' => ['primary' => '#ffffff']],
        ];
        
        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => ['homepage' => []],
            'ThemesBackup' => $themesBackup,
        ])->assertOk();

        $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        $this->assertNotNull($layout);
        $this->assertEquals($themesBackup, $layout->themes_backup);
    }

    public function test_get_tenant_returns_themes_backup(): void
    {
        $tenant = $this->createTenant();
        $themesBackup = ['theme1' => ['data' => 'test']];
        
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => []]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => []]);
        TenantWebsiteLayout::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'data' => [],
            'themes_backup' => $themesBackup,
        ]);

        $response = $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('ThemesBackup', $themesBackup);
    }

    public function test_save_pages_persists_branding_website_branding_and_get_tenant_returns_it(): void
    {
        $tenant = $this->createTenant();
        BasicSetting::create([
            'user_id' => $tenant->id,
            'company_name' => 'Acme Co',
            'logo' => '/logo.png',
        ]);

        $this->actingAs($tenant, 'sanctum');

        $websiteBranding = [
            'primaryColor' => '#ff0000',
            'fontFamily' => 'Inter',
            'custom' => ['any' => 'json'],
        ];

        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => ['homepage' => []],
            'branding' => [
                'websiteBranding' => $websiteBranding,
            ],
        ])->assertOk();

        $this->assertDatabaseHas('tenant_settings', ['user_id' => $tenant->id]);
        $settings = TenantSetting::where('user_id', $tenant->id)->first();
        $this->assertEquals($websiteBranding, $settings->settings['websiteBranding'] ?? null);

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('branding.websiteBranding', $websiteBranding);
    }

    public function test_get_tenant_returns_null_when_themes_backup_not_set(): void
    {
        $tenant = $this->createTenant();
        
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => []]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => []]);
        TenantWebsiteLayout::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'data' => [],
        ]);

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('ThemesBackup', null);
    }

    public function test_get_tenant_returns_null_when_static_pages_not_set(): void
    {
        $tenant = $this->createTenant();
        
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => []]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => []]);
        TenantWebsiteLayout::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'data' => [],
        ]);

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('StaticPages', null);
    }

    public function test_save_pages_with_static_pages(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');
        $staticPages = [
            'terms' => [
                ['id' => 'sp1', 'type' => 'text', 'name' => 'Text', 'componentName' => 'text1', 'data' => [], 'position' => 0],
            ],
            'privacy' => [
                ['id' => 'sp2', 'type' => 'text', 'name' => 'Text', 'componentName' => 'text1', 'data' => [], 'position' => 0],
            ],
        ];
        
        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => ['homepage' => []],
            'StaticPages' => $staticPages,
        ])->assertOk();

        $this->assertDatabaseHas('tenant_static_pages', ['user_id' => $tenant->id, 'page_id' => 'terms']);
        $this->assertDatabaseHas('tenant_static_pages', ['user_id' => $tenant->id, 'page_id' => 'privacy']);
    }

    public function test_get_tenant_returns_static_pages(): void
    {
        $tenant = $this->createTenant();
        
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => []]);
        TenantStaticPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'terms', 'components' => [['id' => 'sp1', 'position' => 0]]]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => []]);
        TenantWebsiteLayout::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'data' => [],
        ]);

        $response = $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('StaticPages.terms.0.id', 'sp1');
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


