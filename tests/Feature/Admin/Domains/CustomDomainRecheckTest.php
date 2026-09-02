<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainRecheckTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureAdminViewData();
        app(VercelDomainCache::class)->invalidate();
    }

    protected function tearDown(): void
    {
        app(VercelDomainCache::class)->invalidate();

        parent::tearDown();
    }

    /** @test */
    public function recheck_clears_domain_health_counts_cache(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();
        $cache = app(VercelDomainCache::class);
        Cache::put($cache->healthCountersKey(), ['linked' => 9, 'issues' => 3], now()->addMinutes(5));
        $this->assertTrue(Cache::has($cache->healthCountersKey()));

        $this->fakeRecheckEndpoints($domain->custom_name);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.recheck'), [
                'domain_id' => $domain->id,
            ])
            ->assertRedirect();

        $this->assertFalse(Cache::has($cache->healthCountersKey()));
    }

    /** @test */
    public function recheck_runs_sync_and_persists_last_check(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();
        $this->fakeRecheckEndpoints($domain->custom_name);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.recheck'), [
                'domain_id' => $domain->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? null;

        $this->assertIsArray($lastCheck);
        $this->assertTrue($lastCheck['vercel_verified'] ?? $lastCheck['apex_verified'] ?? false);
        $this->assertTrue($lastCheck['nameservers_ok']);
        $this->assertNotEmpty($lastCheck['last_check_at']);
    }

    /** @test */
    public function recheck_is_throttled_after_ten_requests_per_minute(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();
        $this->fakeRecheckEndpoints($domain->custom_name);

        for ($i = 0; $i < 10; $i++) {
            $this->from(route('admin.custom-domain.index'))
                ->post(route('admin.custom-domain.recheck'), ['domain_id' => $domain->id])
                ->assertRedirect();
        }

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.recheck'), ['domain_id' => $domain->id])
            ->assertStatus(429);
    }

    /** @test */
    public function recheck_surfaces_translated_provider_error_without_flipping_active_status(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.health_failure_threshold' => 5]);
        $this->signInWebAdmin();

        $domain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create([
                'email' => 'recheck-active-' . uniqid('', true) . '@example.com',
            ])->id,
            'custom_name' => 'recheck-active-' . uniqid('', false) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domain) {
            $url = $request->url();

            if (str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $domain->custom_name, 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (str_contains($url, '/verify')) {
                return Http::response(['error' => ['message' => 'rate limited']], 429);
            }

            return Http::response(['error' => ['message' => 'upstream']], 503);
        });

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.recheck'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertSame('provider_error', $domain->health()['code']);
    }

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings')) {
            $this->fail('Required domain tables are missing.');
        }
    }

    private function configureVercel(): void
    {
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.base_url' => 'https://api.vercel.com',
            'services.vercel.max_project_domains' => 50,
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
        ]);
    }

    private function mockNameservers(bool $ok): void
    {
        $this->mock(DnsNameserverChecker::class, function ($mock) use ($ok) {
            $mock->shouldReceive('hasExpectedNameservers')->andReturn($ok);
            $mock->shouldReceive('getObservedNameservers')->andReturn(
                $ok ? ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'] : ['ns1.example.com']
            );
        });
    }

    private function fakeRecheckEndpoints(string $domainName): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domainName) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $domainName, 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['name' => $domainName, 'verified' => true], 200);
            }

            if (str_contains($url, '/v6/domains/' . rawurlencode($domainName) . '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if (str_contains($url, '/domains/' . rawurlencode($domainName))) {
                return Http::response(['name' => $domainName, 'verified' => true], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });
    }

    private function signInWebAdmin(): Admin
    {
        $admin = Admin::factory()->create([
            'status' => true,
            'role_id' => null,
        ]);

        $this->app['auth']->guard('admin')->setUser($admin);

        View::share([
            'adminUser' => $admin,
        ]);

        return $admin;
    }

    private function seedDomainSetting(?string $customName = null): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'recheck-domain-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $customName ?? ('recheck-' . uniqid('', false) . '.example.com'),
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);
    }

    private function ensureAdminViewData(): void
    {
        DB::table('languages')->updateOrInsert(
            ['code' => 'en'],
            [
                'name' => 'English',
                'is_default' => 1,
                'rtl' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $languageId = (int) DB::table('languages')->where('code', 'en')->value('id');

        $settingsPayload = [
            'language_id' => $languageId,
            'website_title' => 'Taearif',
            'timezone' => 'UTC',
            'logo' => 'logo.png',
            'favicon' => 'favicon.png',
        ];

        if (Schema::hasColumn('basic_settings', 'copyright_text')) {
            $settingsPayload['copyright_text'] = 'Taearif';
        }

        DB::table('basic_settings')->updateOrInsert(
            ['language_id' => $languageId],
            $settingsPayload
        );

        DB::table('basic_extendeds')->updateOrInsert(
            ['language_id' => $languageId],
            []
        );

        $currentLang = \App\Models\Language::query()
            ->with(['basic_setting', 'basic_extended'])
            ->where('is_default', 1)
            ->firstOrFail();

        View::share([
            'bs' => $currentLang->basic_setting,
            'be' => $currentLang->basic_extended,
            'currentLang' => $currentLang,
            'menus' => json_encode([]),
            'rtl' => 0,
            'socials' => collect(),
            'langs' => \App\Models\Language::all(),
            'adminLanguages' => \App\Models\Language::orderBy('is_default', 'desc')->get(),
            'admin_rtl' => false,
            'defaultLang' => $currentLang,
            'adminPermissions' => [],
        ]);
    }
}
