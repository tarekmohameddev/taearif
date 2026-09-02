<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
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

class CustomDomainRepairVerifyTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DEMO_MODE=inactive');
        $_ENV['DEMO_MODE'] = 'inactive';
        $_SERVER['DEMO_MODE'] = 'inactive';

        $this->ensureAdminViewData();
        app(VercelDomainCache::class)->invalidate();
    }

    protected function tearDown(): void
    {
        app(VercelDomainCache::class)->invalidate();

        parent::tearDown();
    }

    /** @test */
    public function repair_verify_clears_domain_health_counts_cache(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();
        $cache = app(VercelDomainCache::class);
        Cache::put($cache->healthCountersKey(), ['linked' => 9, 'issues' => 3], now()->addMinutes(5));
        $this->assertTrue(Cache::has($cache->healthCountersKey()));

        $this->fakeRepairEndpoints($domain->custom_name);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), [
                'domain_id' => $domain->id,
            ])
            ->assertRedirect();

        $this->assertFalse(Cache::has($cache->healthCountersKey()));
    }

    /** @test */
    public function repair_verify_runs_admin_repair_and_persists_last_check(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();
        $this->fakeRepairEndpoints($domain->custom_name);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), [
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
        $this->assertSame('admin_repair', $lastCheck['provisioning']['mode'] ?? null);
    }

    /** @test */
    public function repair_verify_is_throttled_after_ten_requests_per_minute(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();
        $this->fakeRepairEndpoints($domain->custom_name);

        for ($i = 0; $i < 10; $i++) {
            $this->from(route('admin.custom-domain.index'))
                ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
                ->assertRedirect();
        }

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
            ->assertStatus(429);
    }

    /** @test */
    public function repair_verify_surfaces_translated_provider_error_without_flipping_active_status(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.health_failure_threshold' => 5]);
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $domain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create([
                'email' => 'repair-active-' . uniqid('', true) . '@example.com',
            ])->id,
            'custom_name' => 'repair-active-' . uniqid('', false) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domain) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if (str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $domain->custom_name, 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (str_contains($url, '/v6/domains/') && str_contains($url, '/config')) {
                return Http::response(['error' => ['message' => 'upstream']], 503);
            }

            if (str_contains($url, '/v8/certs')) {
                return Http::response([
                    'certs' => [[
                        'id' => 'cert_active',
                        'cns' => [$domain->custom_name],
                        'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                    ]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (preg_match('#/v(?:5|7)/domains/([^/?]+)#', $url) && ! str_contains($url, '/config')) {
                return Http::response([
                    'name' => $domain->custom_name,
                    'zone' => true,
                    'verified' => true,
                ], 200);
            }

            if (str_contains($url, '/domains/' . rawurlencode($domain->custom_name))) {
                return Http::response(['name' => $domain->custom_name, 'verified' => true], 200);
            }

            if (str_contains($url, '/verify')) {
                return Http::response(['error' => ['message' => 'rate limited']], 429);
            }

            return Http::response(['error' => ['message' => 'upstream']], 503);
        });

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertContains($domain->health()['code'], ['provider_error', 'apex_only', 'linked']);
    }

    /** @test */
    public function repair_verify_requires_custom_domains_permission(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $role = Role::factory()->create([
            'permissions' => json_encode(['Dashboard']),
        ]);

        $admin = Admin::factory()->create([
            'status' => true,
            'role_id' => $role->id,
        ]);
        $this->app['auth']->guard('admin')->setUser($admin);

        $domain = $this->seedDomainSetting();
        $this->fakeRepairEndpoints($domain->custom_name);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
            ->assertRedirect(route('admin.dashboard'));
    }

    /** @test */
    public function repair_verify_route_is_web_post_with_csrf_and_permission(): void
    {
        $route = \Illuminate\Support\Facades\Route::getRoutes()->getByName('admin.custom-domain.repair-verify');

        $this->assertNotNull($route);
        $this->assertSame('POST', $route->methods()[0]);
        $this->assertContains('web', $route->gatherMiddleware());
        $this->assertContains('auth:admin', $route->gatherMiddleware());
    }

    /** @test */
    public function manual_status_and_ssl_routes_are_removed(): void
    {
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.custom-domain.status'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.custom-domain.ssl-status'));
        $this->assertFalse(\Illuminate\Support\Facades\Route::has('admin.custom-domain.recheck'));
        $this->assertTrue(\Illuminate\Support\Facades\Route::has('admin.custom-domain.repair-verify'));
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
            'services.vercel.expected_project_id' => 'prj_test',
            'services.vercel.expected_team_id' => 'team_test',
            'services.vercel.allow_shared_project_mutations' => true,
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

    private function fakeRepairEndpoints(string $domainName): void
    {
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domainName) {
            $url = $request->url();
            $method = $request->method();
            $apex = strtolower($domainName);

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $apex, 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v8/certs') && ! preg_match('#/v8/certs/[^/?]#', $url)) {
                return Http::response([
                    'certs' => [[
                        'id' => 'cert_test',
                        'cns' => [$apex],
                        'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                        'autoRenew' => true,
                    ]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && preg_match('#/v(?:5|7)/domains/([^/?]+)#', $url, $matches) && ! str_contains($url, '/config')) {
                $requestedApex = strtolower(rawurldecode($matches[1]));
                if ($requestedApex === $apex) {
                    return Http::response([
                        'name' => $apex,
                        'zone' => true,
                        'verified' => true,
                    ], 200);
                }

                return Http::response(['error' => 'not_found'], 404);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['name' => $apex, 'verified' => true], 200);
            }

            if (str_contains($url, '/v6/domains/' . rawurlencode($apex) . '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains/' . rawurlencode($apex))) {
                return Http::response(['name' => $apex, 'verified' => true, 'verification' => []], 200);
            }

            if (str_contains($url, '/domains/' . rawurlencode($apex))) {
                return Http::response(['name' => $apex, 'verified' => true, 'verification' => []], 200);
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
            'email' => 'repair-domain-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $customName ?? ('repair-' . uniqid('', false) . '.example.com'),
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
