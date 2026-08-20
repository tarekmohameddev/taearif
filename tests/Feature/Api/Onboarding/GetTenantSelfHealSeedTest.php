<?php

namespace Tests\Feature\Api\Onboarding;

use App\Models\TenantGlobalComponent;
use App\Models\TenantPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Services\TenantWebsiteSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class GetTenantSelfHealSeedTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_tenant_sync_reseeds_when_website_rows_missing(): void
    {
        if (!Schema::hasTable('users')
            || !Schema::hasTable('tenant_pages')
            || !Schema::hasTable('tenant_global_components')
            || !Schema::hasTable('tenant_website_layouts')) {
            $this->markTestSkipped('Required tables are missing for GetTenant self-heal test.');
        }

        $apiUrl = 'https://tenant-template.test/defaults-get-tenant';
        config(['app.tenant_website_api_url' => $apiUrl]);
        TenantWebsiteSeeder::clearDefaultDataCache($apiUrl);

        Http::fake([
            $apiUrl => Http::response([
                'componentSettings' => [
                    'homepage' => [
                        ['id' => 'c1', 'position' => 0, 'type' => 'hero'],
                    ],
                ],
                'globalComponentsData' => [
                    'header' => ['logo' => null],
                    'footer' => ['links' => []],
                ],
                'WebsiteLayout' => [
                    'metaTags' => ['pages' => []],
                ],
            ], 200),
        ]);

        $username = 'gtseed' . substr(md5(uniqid('', true)), 0, 10);
        $tenant = User::factory()->tenant()->create([
            'username' => $username,
            'email' => "get-tenant-seed-{$username}@example.com",
        ]);

        $this->assertFalse(TenantPage::query()->where('user_id', $tenant->id)->exists());
        $this->assertFalse(TenantGlobalComponent::query()->where('user_id', $tenant->id)->exists());
        $this->assertFalse(TenantWebsiteLayout::query()->where('user_id', $tenant->id)->exists());

        $response = $this->postJson('/api/v1/tenant-website/getTenant', [
            'websiteName' => $username,
        ]);

        $response->assertOk()
            ->assertJsonPath('username', $username)
            ->assertJsonPath('componentSettings.homepage.0.id', 'c1')
            ->assertJsonPath('globalComponentsData.header.logo', null);

        $this->assertIsArray(data_get($response->json(), 'WebsiteLayout'));
        $this->assertNotEmpty(data_get($response->json(), 'WebsiteLayout.metaTags.pages'));

        $this->assertTrue(TenantPage::query()->where('user_id', $tenant->id)->exists());
        $this->assertTrue(TenantGlobalComponent::query()->where('user_id', $tenant->id)->exists());
        $this->assertTrue(TenantWebsiteLayout::query()->where('user_id', $tenant->id)->exists());
    }
}
