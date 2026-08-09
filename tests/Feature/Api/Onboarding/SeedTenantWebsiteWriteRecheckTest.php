<?php

namespace Tests\Feature\Api\Onboarding;

use App\Models\TenantPage;
use App\Models\TenantWebsiteLayout;
use App\Models\User;
use App\Services\TenantWebsiteSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Write-time re-check: if website rows appear (or onboarding completed) before seed writes,
 * seedDefaultWebsite / seedIfEmpty must skip and not overwrite.
 *
 * Residual Seed↔Reseed / Seed↔GetTenant interleaving can still occur if another writer
 * commits after the re-check and before writes; this test covers the deterministic
 * "data exists before write → skip" case only.
 */
class SeedTenantWebsiteWriteRecheckTest extends TestCase
{
    use DatabaseTransactions;

    public function test_seed_if_empty_skips_writes_when_layout_already_exists(): void
    {
        if (!Schema::hasTable('users')
            || !Schema::hasTable('tenant_pages')
            || !Schema::hasTable('tenant_website_layouts')) {
            $this->markTestSkipped('Required tables are missing for write-time re-check test.');
        }

        $apiUrl = 'https://tenant-template.test/defaults-recheck';
        config(['app.tenant_website_api_url' => $apiUrl]);
        TenantWebsiteSeeder::clearDefaultDataCache($apiUrl);

        Http::fake([
            $apiUrl => Http::response([
                'componentSettings' => [
                    'home' => [
                        ['id' => 'overwrite-me', 'position' => 0, 'type' => 'hero'],
                    ],
                ],
                'globalComponentsData' => [
                    'footer' => ['content' => ['contactInfo' => ['email' => 'template@example.test']]],
                ],
                'WebsiteLayout' => [
                    'branding' => ['colors' => ['primary' => '#000000']],
                    'marker' => 'from-template-must-not-appear',
                ],
            ], 200),
        ]);

        $user = User::factory()->tenant()->create([
            'email' => 'seed-recheck-' . uniqid('', true) . '@example.com',
            'onboarding_completed' => false,
        ]);

        // Layout only: hasWebsiteData() is false (no pages/globals), so seedIfEmpty
        // proceeds to fetch, then shouldAbortSeedWrites skips because layout exists.
        TenantWebsiteLayout::query()->create([
            'user_id' => $user->id,
            'data' => [
                'marker' => 'pre-existing-layout',
                'existing' => ['keep' => true],
            ],
        ]);

        $seeder = app(TenantWebsiteSeeder::class);
        $result = $seeder->seedIfEmpty($user);

        $this->assertFalse($result);

        $layout = TenantWebsiteLayout::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($layout);
        $this->assertSame('pre-existing-layout', data_get($layout->data, 'marker'));
        $this->assertSame(true, data_get($layout->data, 'existing.keep'));
        $this->assertNull(data_get($layout->data, 'branding.colors.primary'));

        $this->assertFalse(
            TenantPage::query()->where('user_id', $user->id)->exists(),
            'Seed writes must not create pages when layout already existed'
        );
    }

    public function test_seed_default_website_skips_when_onboarding_already_completed(): void
    {
        if (!Schema::hasTable('users') || !Schema::hasTable('tenant_pages')) {
            $this->markTestSkipped('Required tables are missing for onboarding_completed re-check test.');
        }

        $apiUrl = 'https://tenant-template.test/defaults-onboarding-done';
        config(['app.tenant_website_api_url' => $apiUrl]);
        TenantWebsiteSeeder::clearDefaultDataCache($apiUrl);

        Http::fake([
            $apiUrl => Http::response([
                'componentSettings' => [
                    'home' => [['id' => 'should-not-write', 'position' => 0]],
                ],
                'globalComponentsData' => ['header' => []],
                'WebsiteLayout' => ['marker' => 'template'],
            ], 200),
        ]);

        $user = User::factory()->tenant()->create([
            'email' => 'seed-onboarded-' . uniqid('', true) . '@example.com',
            'onboarding_completed' => true,
        ]);

        $seeder = app(TenantWebsiteSeeder::class);
        $result = $seeder->seedDefaultWebsite($user);

        $this->assertFalse($result);
        $this->assertFalse(TenantPage::query()->where('user_id', $user->id)->exists());
    }
}
