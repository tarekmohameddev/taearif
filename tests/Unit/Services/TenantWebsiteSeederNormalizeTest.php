<?php

namespace Tests\Unit\Services;

use App\Services\TenantWebsiteSeeder;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TenantWebsiteSeederNormalizeTest extends TestCase
{
    public function test_fetch_default_data_normalizes_pages_and_static_pages_aliases(): void
    {
        $apiUrl = 'https://example.test/api/node/defaultData';
        config(['app.tenant_website_api_url' => $apiUrl]);

        TenantWebsiteSeeder::clearDefaultDataCache($apiUrl);

        $pages = [
            'homepage' => [
                ['id' => 'c1', 'type' => 'hero'],
            ],
        ];
        $staticPages = [
            'terms' => [
                'components' => [
                    ['id' => 'sp1'],
                ],
            ],
        ];
        $globalComponentsData = [
            'header' => ['logo' => null],
            'footer' => ['links' => []],
        ];
        $websiteLayout = [
            'metaTags' => ['pages' => []],
        ];

        Http::fake([
            $apiUrl => Http::response([
                'tenantId' => 'ignored',
                'theme' => 'ignored',
                'themeNumber' => 1,
                'pages' => $pages,
                'staticPages' => $staticPages,
                'globalComponentsData' => $globalComponentsData,
                'WebsiteLayout' => $websiteLayout,
            ], 200),
        ]);

        $seeder = new TenantWebsiteSeeder();
        $method = (new \ReflectionClass($seeder))->getMethod('fetchDefaultData');
        $method->setAccessible(true);

        $result = $method->invoke($seeder);

        $this->assertIsArray($result);
        $this->assertSame($pages, $result['componentSettings']);
        $this->assertSame($staticPages, $result['StaticPages']);
        $this->assertSame($globalComponentsData, $result['globalComponentsData']);
        $this->assertSame($websiteLayout, $result['WebsiteLayout']);
        $this->assertArrayHasKey('pages', $result);
        $this->assertArrayHasKey('staticPages', $result);
    }

    public function test_fetch_default_data_prefers_existing_component_settings_and_static_pages(): void
    {
        $apiUrl = 'https://example.test/api/node/defaultData';
        config(['app.tenant_website_api_url' => $apiUrl]);

        TenantWebsiteSeeder::clearDefaultDataCache($apiUrl);

        $componentSettings = ['homepage' => [['id' => 'canonical']]];
        $pages = ['homepage' => [['id' => 'alias']]];
        $staticPagesCanonical = ['terms' => ['components' => [['id' => 'canonical-sp']]]];
        $staticPagesAlias = ['terms' => ['components' => [['id' => 'alias-sp']]]];

        Http::fake([
            $apiUrl => Http::response([
                'componentSettings' => $componentSettings,
                'pages' => $pages,
                'StaticPages' => $staticPagesCanonical,
                'staticPages' => $staticPagesAlias,
                'globalComponentsData' => ['header' => []],
            ], 200),
        ]);

        $seeder = new TenantWebsiteSeeder();
        $method = (new \ReflectionClass($seeder))->getMethod('fetchDefaultData');
        $method->setAccessible(true);

        $result = $method->invoke($seeder);

        $this->assertSame($componentSettings, $result['componentSettings']);
        $this->assertSame($staticPagesCanonical, $result['StaticPages']);
    }
}
