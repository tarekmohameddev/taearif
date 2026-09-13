<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Domain\Domain\Models\CustomDomain;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainReconciliationActionsTest extends AdminApiTestCase
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
    public function legacy_orphan_adopt_creates_linked_row_and_provisions(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'legacy-adopt-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'legacy-adopt-' . uniqid('', false) . '.example.com';

        $legacy = CustomDomain::create([
            'user_id' => $user->id,
            'requested_domain' => $apex,
            'current_domain' => $apex,
            'status' => true,
        ]);

        $this->fakeRepairEndpoints($apex);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.legacy-orphan.adopt'), [
                'legacy_id' => $legacy->id,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $user->id,
            'custom_domain_id' => $legacy->id,
            'custom_name' => $apex,
        ]);

        $setting = ApiDomainSetting::query()->where('custom_domain_id', $legacy->id)->first();
        $this->assertNotNull($setting);
        $this->assertIsArray($setting->dns_records['last_check'] ?? null);
    }

    /** @test */
    public function legacy_orphan_delete_respects_linked_boundary(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'legacy-delete-boundary-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'legacy-delete-boundary-' . uniqid('', false) . '.example.com';

        $legacy = CustomDomain::create([
            'user_id' => $user->id,
            'requested_domain' => $apex,
            'current_domain' => $apex,
            'status' => true,
        ]);

        ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_domain_id' => $legacy->id,
            'custom_name' => $apex,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.legacy-orphan.delete'), [
                'legacy_id' => $legacy->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('user_custom_domains', ['id' => $legacy->id]);
    }

    /** @test */
    public function legacy_orphan_delete_removes_row_when_unlinked(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'legacy-delete-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'legacy-delete-' . uniqid('', false) . '.example.com';

        $legacy = CustomDomain::create([
            'user_id' => $user->id,
            'requested_domain' => $apex,
            'current_domain' => $apex,
            'status' => true,
        ]);

        $this->fakeVercelAdminDomains([], allowDelete: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.legacy-orphan.delete'), [
                'legacy_id' => $legacy->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('user_custom_domains', ['id' => $legacy->id]);
    }

    /** @test */
    public function stray_www_remove_deletes_www_without_apex(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $apex = 'stray-apex-' . uniqid('', false) . '.example.com';
        $www = 'www.' . $apex;

        $this->fakeVercelAdminDomains([
            ['name' => $www, 'verified' => true, 'redirect' => $apex, 'redirectStatusCode' => 301],
        ], allowDelete: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.stray-www.remove'), [
                'www' => $www,
                'confirm_domain' => $www,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), rawurlencode($www)));
    }

    /** @test */
    public function fix_www_redirect_is_idempotent_when_already_correct(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'fix-redirect-idempotent-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'fix-redirect-idempotent-' . uniqid('', false) . '.example.com';
        $domain = ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $apex,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->fakeVercelAdminDomains([
            ['name' => $apex, 'verified' => true],
            ['name' => 'www.' . $apex, 'verified' => true, 'redirect' => $apex, 'redirectStatusCode' => 301],
        ]);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.fix-redirect'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertNotSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/domains'));
    }

    /** @test */
    public function fix_www_redirect_repairs_mismatched_redirect(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'fix-redirect-repair-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'fix-redirect-repair-' . uniqid('', false) . '.example.com';
        $domain = ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $apex,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->fakeVercelAdminDomains([
            ['name' => $apex, 'verified' => true],
            ['name' => 'www.' . $apex, 'verified' => true, 'redirect' => 'wrong.example.com', 'redirectStatusCode' => 301],
        ], allowMutations: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.fix-redirect'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/domains'));
    }

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('user_custom_domains')) {
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
            'services.vercel.platform_domains' => ['taearif.com', 'www.taearif.com'],
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

    /**
     * @param  list<array<string, mixed>>  $domains
     */
    private function fakeVercelAdminDomains(array $domains, bool $allowDelete = false, bool $allowMutations = false): void
    {
        $allowMutations = $allowMutations || $allowDelete;

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domains, $allowDelete, $allowMutations) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => $domains,
                    'pagination' => ['count' => count($domains), 'next' => null],
                ], 200);
            }

            if ($allowMutations && $method === 'GET' && preg_match('#/v9/projects/prj_test/domains/([^/?]+)#', $url, $matches)) {
                $name = strtolower(rawurldecode($matches[1]));
                foreach ($domains as $domain) {
                    if (strtolower((string) ($domain['name'] ?? '')) === $name) {
                        return Http::response($domain, 200);
                    }
                }

                return Http::response(['error' => 'not found'], 404);
            }

            if ($allowDelete && $method === 'DELETE' && str_contains($url, '/domains/')) {
                return Http::response(null, 200);
            }

            if ($allowMutations && $method === 'POST' && str_contains($url, '/domains') && ! str_contains($url, '/verify')) {
                return Http::response(['name' => 'www.example.com', 'verified' => true], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
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
