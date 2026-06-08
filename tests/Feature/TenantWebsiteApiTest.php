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

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('StaticPages.terms.components.0.id', 'sp1')
            ->assertJsonPath('StaticPages.terms.url', null);
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

    public function test_content_static_pages_requires_auth(): void
    {
        $this->getJson('/api/content/static-pages')->assertStatus(401);
        $this->postJson('/api/content/static-pages', [
            'page_id' => 'privacy',
            'components' => [],
        ])->assertStatus(401);
    }

    public function test_content_static_pages_index_returns_three_slots(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');

        $this->getJson('/api/content/static-pages')
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(3, 'data.pages')
            ->assertJsonPath('data.pages.0.page_id', 'privacy')
            ->assertJsonPath('data.pages.1.page_id', 'terms')
            ->assertJsonPath('data.pages.2.page_id', 'profile');
    }

    public function test_content_static_pages_crud_and_get_tenant_url(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');

        $components = [
            ['id' => 'c1', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];

        $this->postJson('/api/content/static-pages', [
            'page_id' => 'privacy',
            'components' => $components,
            'url' => 'https://example.com/video.mp4',
        ])->assertOk()
            ->assertJsonPath('data.page.page_id', 'privacy')
            ->assertJsonPath('data.page.url', 'https://example.com/video.mp4');

        $this->assertDatabaseHas('tenant_static_pages', [
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'url' => 'https://example.com/video.mp4',
        ]);

        $this->putJson('/api/content/static-pages/privacy', [
            'url' => null,
        ])->assertOk()
            ->assertJsonPath('data.page.url', null);

        $this->getJson('/api/content/static-pages/privacy')
            ->assertOk()
            ->assertJsonPath('data.page.components.0.id', 'c1');

        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => []]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => []]);
        TenantWebsiteLayout::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'data' => [],
        ]);

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('StaticPages.privacy.components.0.id', 'c1')
            ->assertJsonPath('StaticPages.privacy.url', null);

        $this->deleteJson('/api/content/static-pages/privacy')->assertOk();
        $this->assertDatabaseMissing('tenant_static_pages', [
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
        ]);
    }

    public function test_content_static_pages_store_validation(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');

        $this->postJson('/api/content/static-pages', [
            'page_id' => 'invalid',
            'components' => [],
        ])->assertStatus(422);

        $this->getJson('/api/content/static-pages/not-a-page')->assertStatus(404);
    }

    public function test_content_static_pages_other_user_does_not_see_peer_data(): void
    {
        $tenantA = $this->createTenant('acme');
        $tenantB = $this->createTenant('other');

        $this->actingAs($tenantA, 'sanctum');
        $this->postJson('/api/content/static-pages', [
            'page_id' => 'terms',
            'components' => [
                ['id' => 'secret', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
            ],
        ])->assertOk();

        $this->actingAs($tenantB, 'sanctum');
        $this->getJson('/api/content/static-pages/terms')
            ->assertOk()
            ->assertJsonPath('data.page.components', [])
            ->assertJsonPath('data.page.url', null);
    }

    public function test_save_pages_static_pages_nested_format_with_url(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');

        $staticPages = [
            'terms' => [
                'components' => [
                    ['id' => 'sp1', 'type' => 'text', 'name' => 'Text', 'componentName' => 'text1', 'data' => [], 'position' => 0],
                ],
                'url' => 'https://cdn.example.com/terms-banner.jpg',
            ],
        ];

        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => ['homepage' => []],
            'StaticPages' => $staticPages,
        ])->assertOk();

        $this->assertDatabaseHas('tenant_static_pages', [
            'user_id' => $tenant->id,
            'page_id' => 'terms',
            'url' => 'https://cdn.example.com/terms-banner.jpg',
        ]);
    }

    public function test_public_static_pages_list_and_show_without_auth(): void
    {
        $tenant = $this->createTenant();
        $components = [
            ['id' => 'c1', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];
        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'components' => $components,
        ]);
        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'profile',
            'components' => $components,
        ]);

        $this->getJson('/api/v1/tenant-website/acme/static-pages')
            ->assertOk()
            ->assertJsonCount(2, 'pages')
            ->assertJsonPath('pages.0.page_id', 'privacy')
            ->assertJsonPath('pages.1.page_id', 'profile');

        $this->getJson('/api/v1/tenant-website/acme/static-pages/privacy')
            ->assertOk()
            ->assertJsonPath('page_id', 'privacy')
            ->assertJsonPath('components.0.id', 'c1');
    }

    public function test_public_static_pages_unknown_page_returns_404(): void
    {
        $tenant = $this->createTenant();

        $this->getJson('/api/v1/tenant-website/acme/static-pages/not-a-page')
            ->assertStatus(404);

        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'components' => [],
        ]);

        $this->getJson('/api/v1/tenant-website/acme/static-pages/privacy')
            ->assertStatus(404);
    }

    public function test_public_static_pages_empty_page_omitted_from_index(): void
    {
        $tenant = $this->createTenant();
        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'components' => [],
        ]);
        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'profile',
            'components' => [
                ['id' => 'c1', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
            ],
        ]);

        $this->getJson('/api/v1/tenant-website/acme/static-pages')
            ->assertOk()
            ->assertJsonCount(1, 'pages')
            ->assertJsonPath('pages.0.page_id', 'profile');
    }

    public function test_public_static_pages_draft_fallback_when_not_published(): void
    {
        $tenant = $this->createTenant();
        $draftComponents = [
            ['id' => 'draft', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];
        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'components' => $draftComponents,
            'published_data' => null,
        ]);

        $this->getJson('/api/v1/tenant-website/acme/static-pages/privacy')
            ->assertOk()
            ->assertJsonPath('components.0.id', 'draft');
    }

    public function test_public_static_pages_prefers_published_data_after_publish(): void
    {
        $tenant = $this->createTenant();
        $publishedComponents = [
            ['id' => 'published', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];
        $draftComponents = [
            ['id' => 'draft', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];

        $page = TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'components' => $publishedComponents,
        ]);

        $this->actingAs($tenant, 'sanctum');
        $this->postJson('/api/v1/tenant-website/acme/publish')->assertOk();

        $page->refresh();
        $page->components = $draftComponents;
        $page->save();

        $this->getJson('/api/v1/tenant-website/acme/static-pages/privacy')
            ->assertOk()
            ->assertJsonPath('components.0.id', 'published');
    }

    public function test_get_tenant_static_pages_use_published_fallback_logic(): void
    {
        $tenant = $this->createTenant();
        TenantPage::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'page_id' => 'homepage', 'components' => []]);
        TenantGlobalComponent::create(['id' => (string) \Illuminate\Support\Str::uuid(), 'user_id' => $tenant->id, 'data' => []]);
        TenantWebsiteLayout::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'data' => [],
        ]);

        $publishedComponents = [
            ['id' => 'published', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];
        $draftComponents = [
            ['id' => 'draft', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
        ];

        $page = TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'privacy',
            'components' => $publishedComponents,
        ]);

        $this->actingAs($tenant, 'sanctum');
        $this->postJson('/api/v1/tenant-website/acme/publish')->assertOk();

        $page->refresh();
        $page->components = $draftComponents;
        $page->save();

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('StaticPages.privacy.components.0.id', 'published');
    }

    public function test_public_static_pages_cross_tenant_isolation(): void
    {
        $tenantA = $this->createTenant('acme');
        $tenantB = $this->createTenant('other');

        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenantA->id,
            'page_id' => 'privacy',
            'components' => [
                ['id' => 'tenant-a', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
            ],
        ]);

        $this->getJson('/api/v1/tenant-website/other/static-pages/privacy')
            ->assertStatus(404);

        TenantStaticPage::create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $tenantB->id,
            'page_id' => 'privacy',
            'components' => [
                ['id' => 'tenant-b', 'type' => 'text', 'name' => 'Text', 'componentName' => 't1', 'data' => [], 'position' => 0],
            ],
        ]);

        $this->getJson('/api/v1/tenant-website/other/static-pages/privacy')
            ->assertOk()
            ->assertJsonPath('components.0.id', 'tenant-b');

        $this->getJson('/api/v1/tenant-website/acme/static-pages/privacy')
            ->assertOk()
            ->assertJsonPath('components.0.id', 'tenant-a');
    }
}


