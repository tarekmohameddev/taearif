<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PageSeoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function createTenant(string $username = 'acme'): User
    {
        $user = User::factory()->create();
        $user->username = $username;
        $user->save();

        return $user;
    }

    protected function seedTenantWebsite(User $tenant): void
    {
        TenantPage::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'homepage',
            'components' => [],
        ]);
        TenantPage::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'page_id' => 'my-custom-page',
            'components' => [],
        ]);
        TenantGlobalComponent::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'data' => ['header' => []],
        ]);
        TenantWebsiteLayout::create([
            'id' => (string) Str::uuid(),
            'user_id' => $tenant->id,
            'data' => ['currentTheme' => 1, 'metaTags' => ['pages' => []]],
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $this->getJson('/api/content/page-seo')->assertStatus(401);
    }

    public function test_index_returns_general_defaults_and_custom_pages(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $response = $this->getJson('/api/content/page-seo')
            ->assertOk()
            ->assertJsonPath('success', true);

        $pageKeys = collect($response->json('data.pages'))->pluck('page_key')->all();

        $this->assertContains('homepage', $pageKeys);
        $this->assertContains('for-rent', $pageKeys);
        $this->assertContains('my-custom-page', $pageKeys);
        $this->assertContains('privacy', $pageKeys);

        $homepage = collect($response->json('data.pages'))->firstWhere('page_key', 'homepage');
        $this->assertSame('/', $homepage['path']);
        $this->assertTrue($homepage['is_general']);
        $this->assertFalse($homepage['has_override']);
        $this->assertSame('الصفحة الرئيسية', $homepage['meta']['TitleAr']);
    }

    public function test_put_homepage_upserts_meta_in_layout(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->putJson('/api/content/page-seo/homepage', [
            'TitleAr' => 'عنوان مخصص',
            'TitleEn' => 'Custom Title',
            'DescriptionAr' => 'وصف عربي',
            'og:image' => 'https://cdn.example.com/og.jpg',
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page_key', 'homepage')
            ->assertJsonPath('data.path', '/')
            ->assertJsonPath('data.has_override', true)
            ->assertJsonPath('data.meta.TitleAr', 'عنوان مخصص')
            ->assertJsonPath('data.meta.og:image', 'https://cdn.example.com/og.jpg');

        $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        $pages = $layout->data['metaTags']['pages'];
        $homepage = collect($pages)->firstWhere('path', '/');

        $this->assertNotNull($homepage);
        $this->assertSame('عنوان مخصص', $homepage['TitleAr']);
        $this->assertSame('https://cdn.example.com/og.jpg', $homepage['og:image']);
    }

    public function test_show_returns_merged_meta(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->putJson('/api/content/page-seo/for-rent', [
            'TitleAr' => 'إيجار محدث',
        ])->assertOk();

        $this->getJson('/api/content/page-seo/for-rent')
            ->assertOk()
            ->assertJsonPath('data.meta.TitleAr', 'إيجار محدث')
            ->assertJsonPath('data.meta.TitleEn', 'For Rent');
    }

    public function test_patch_partial_update_preserves_other_fields(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->putJson('/api/content/page-seo/for-sale', [
            'TitleAr' => 'بيع أولي',
            'DescriptionAr' => 'وصف أولي',
            'og:image' => 'https://cdn.example.com/sale.jpg',
        ])->assertOk();

        $this->patchJson('/api/content/page-seo/for-sale', [
            'TitleAr' => 'بيع محدث',
        ])
            ->assertOk()
            ->assertJsonPath('data.meta.TitleAr', 'بيع محدث')
            ->assertJsonPath('data.meta.DescriptionAr', 'وصف أولي')
            ->assertJsonPath('data.meta.og:image', 'https://cdn.example.com/sale.jpg');
    }

    public function test_delete_removes_override_and_falls_back_to_defaults(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->putJson('/api/content/page-seo/projects', [
            'TitleAr' => 'مشاريع مخصصة',
        ])->assertOk();

        $this->deleteJson('/api/content/page-seo/projects')
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->getJson('/api/content/page-seo/projects')
            ->assertOk()
            ->assertJsonPath('data.has_override', false)
            ->assertJsonPath('data.meta.TitleAr', 'المشاريع');
    }

    public function test_get_tenant_returns_updated_meta_after_dashboard_write(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->putJson('/api/content/page-seo/homepage', [
            'TitleAr' => 'من لوحة التحكم',
            'path' => '/',
        ])->assertOk();

        $this->postJson('/api/v1/tenant-website/getTenant', ['websiteName' => 'acme'])
            ->assertOk()
            ->assertJsonPath('WebsiteLayout.metaTags.pages.0.path', '/')
            ->assertJsonPath('WebsiteLayout.metaTags.pages.0.TitleAr', 'من لوحة التحكم');
    }

    public function test_save_pages_still_persists_meta_tags(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->postJson('/api/v1/tenant-website/save-pages', [
            'tenantId' => 'acme',
            'pages' => ['homepage' => []],
            'globalComponentsData' => ['header' => []],
            'WebsiteLayout' => [
                'metaTags' => [
                    'pages' => [
                        ['TitleAr' => 'من المحرر', 'path' => '/'],
                    ],
                ],
            ],
        ])->assertOk();

        $layout = TenantWebsiteLayout::where('user_id', $tenant->id)->first();
        $homepage = collect($layout->data['metaTags']['pages'])->firstWhere('path', '/');
        $this->assertSame('من المحرر', $homepage['TitleAr']);
    }

    public function test_post_store_upserts_by_path(): void
    {
        $tenant = $this->createTenant();
        $this->seedTenantWebsite($tenant);
        $this->actingAs($tenant, 'sanctum');

        $this->postJson('/api/content/page-seo', [
            'path' => '/my-custom-page',
            'TitleAr' => 'صفحة مخصصة',
            'TitleEn' => 'Custom Page',
        ])
            ->assertOk()
            ->assertJsonPath('data.page_key', 'my-custom-page')
            ->assertJsonPath('data.meta.TitleAr', 'صفحة مخصصة');
    }

    public function test_show_returns_404_for_unknown_page_key(): void
    {
        $tenant = $this->createTenant();
        $this->actingAs($tenant, 'sanctum');

        $this->getJson('/api/content/page-seo/does-not-exist')
            ->assertStatus(404);
    }
}
