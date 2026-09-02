<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainCapacityTest extends AdminApiTestCase
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
    public function page_renders_capacity_when_vercel_api_responds(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $this->fakeDomainList(46),
                'pagination' => ['count' => 46, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        // Anchor on values and structure, not copy: the admin layout renders in
        // Arabic, so asserting labels couples this test to the translation files.
        $response->assertSee('data-lucide="gauge"', false);
        $response->assertSee('46 / 50', false);
        $response->assertSee('>46<', false);   // customer apex domains
        $response->assertSee('>4</h4>', false); // free entries
        $response->assertSee('92%', false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function page_still_renders_domains_list_when_api_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response(['error' => ['message' => 'upstream error']], 500),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertDontSee('data-lucide="gauge"', false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function page_still_renders_when_vercel_is_not_configured(): void
    {
        $this->skipIfMissingSchema();
        $this->signInWebAdmin();

        $domain = $this->seedDomainSetting();

        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertDontSee('data-lucide="gauge"', false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function pagination_is_followed_and_totals_are_summed(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $this->seedDomainSetting();

        $page1Until = 1788086333258;

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($page1Until) {
            $url = $request->url();

            if (! str_contains($url, '/v9/projects/') || ! str_contains($url, '/domains')) {
                return Http::response(['error' => 'unexpected'], 500);
            }

            if (str_contains($url, 'until=')) {
                return Http::response([
                    'domains' => $this->fakeDomainList(20),
                    'pagination' => ['count' => 20, 'next' => null, 'prev' => $page1Until],
                ], 200);
            }

            return Http::response([
                'domains' => $this->fakeDomainList(80),
                'pagination' => ['count' => 80, 'next' => $page1Until, 'prev' => null],
            ], 200);
        });

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('100 / 50', false);
    }

    /** @test */
    public function remaining_entries_use_exact_inventory_counts(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $this->fakeDomainList(48),
                'pagination' => ['count' => 48, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('48 / 50', false);
        $response->assertSee('>48<', false);
        $response->assertSee('>2</h4>', false);
        $response->assertSee('96%', false);
    }

    /** @test */
    public function capacity_widget_shows_mixed_apex_only_and_apex_with_www_metrics(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();
        $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'taearif.com', 'verified' => true],
                    ['name' => 'customer-apex.example.com', 'verified' => true],
                    ['name' => 'www.customer-apex.example.com', 'verified' => true, 'redirect' => 'customer-apex.example.com', 'redirectStatusCode' => 301],
                    ['name' => 'apex-only.example.com', 'verified' => false],
                ],
                'pagination' => ['count' => 4, 'next' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('data-lucide="gauge"', false);
        $response->assertSee('>2<', false);
        $response->assertSee('>1<', false);
    }

    /** @test */
    public function platform_allowlist_domains_are_excluded_from_customer_apex_count(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();
        $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [
                    ['name' => 'taearif.com', 'verified' => true],
                    ['name' => 'www.taearif.com', 'verified' => true, 'redirect' => 'taearif.com', 'redirectStatusCode' => 301],
                    ['name' => 'bigrises.com', 'verified' => true],
                    ['name' => 'tenant-one.example.com', 'verified' => true],
                ],
                'pagination' => ['count' => 4, 'next' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('>1<', false);
        $response->assertSee('>2<', false);
    }

    /** @test */
    public function page_shows_unreliable_inventory_warning_when_lower_bound(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();
        $domain = $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [['name' => 'partial.example.com', 'verified' => true]],
                'pagination' => ['count' => 1, 'next' => 9999999999999],
            ], 200),
        ]);

        app(VercelDomainCache::class)->fresh();

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee(__('vercel_capacity.inventory_unreliable'), false);
        $response->assertSee($domain->custom_name, false);
    }

    /** @test */
    public function capacity_math_does_not_divide_customer_counts_by_two(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();
        $this->seedDomainSetting();

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $this->fakeDomainList(46),
                'pagination' => ['count' => 46, 'next' => null],
            ], 200),
        ]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('>46<', false);
        $response->assertDontSee('>23<', false);
    }

    /** @test */
    public function enable_www_rejects_when_no_free_slot(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'www-capacity-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'www-capacity-' . uniqid('', false) . '.example.com';
        $domain = ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $apex,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $filled = [];
        for ($i = 0; $i < 50; $i++) {
            $filled[] = ['name' => 'filled-' . $i . '.example.com', 'verified' => true];
        }
        $filled[] = ['name' => $apex, 'verified' => true];

        $this->fakeVercelAdminDomains($filled);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.enable'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Http::assertNotSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/domains'));
    }

    /** @test */
    public function enable_www_is_idempotent_when_valid_redirect_already_exists(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'www-idempotent-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'www-idempotent-' . uniqid('', false) . '.example.com';
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
            ->post(route('admin.custom-domain.www.enable'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertNotSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/domains'));
    }

    /** @test */
    public function enable_www_surfaces_redirect_mismatch_error(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'www-mismatch-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'www-mismatch-' . uniqid('', false) . '.example.com';
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
        ]);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.enable'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    /** @test */
    public function disable_www_removes_only_www_hostname(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'www-disable-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'www-disable-' . uniqid('', false) . '.example.com';
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
        ], allowDelete: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.disable'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), 'www.' . rawurlencode($apex)));

        Http::assertNotSent(fn ($request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/domains/'));
    }

    /** @test */
    public function disable_www_clears_apex_redirect_before_removing_www(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $user = User::factory()->tenant()->create([
            'email' => 'www-legacy-' . uniqid('', true) . '@example.com',
        ]);
        $apex = 'www-legacy-' . uniqid('', false) . '.example.com';
        $domain = ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $apex,
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->fakeVercelAdminDomains([
            ['name' => $apex, 'verified' => true, 'redirect' => 'www.' . $apex, 'redirectStatusCode' => 301],
            ['name' => 'www.' . $apex, 'verified' => true],
        ], allowDelete: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.disable'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn ($request) => $request->method() === 'PATCH'
            && str_contains($request->url(), '/domains/' . rawurlencode($apex))
            && str_contains($request->body(), '"redirect":null')
            && str_contains($request->body(), '"redirectStatusCode":null'));

        Http::assertSent(fn ($request) => $request->method() === 'DELETE'
            && str_contains($request->url(), 'www.' . rawurlencode($apex)));
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     */
    private function fakeVercelAdminDomains(array $domains, bool $allowDelete = false, bool $allowPatch = false): void
    {
        $allowMutations = $allowDelete || $allowPatch;

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

            if ($allowMutations && $method === 'PATCH' && str_contains($url, '/domains/')) {
                return Http::response(null, 200);
            }

            if ($allowDelete && $method === 'DELETE' && str_contains($url, '/domains/')) {
                return Http::response(null, 200);
            }

            if ($method === 'POST' && str_contains($url, '/domains')) {
                return Http::response(['name' => 'www.example.com', 'verified' => true], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
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
            'services.vercel.platform_domains' => [
                'taearif.com',
                'www.taearif.com',
                'bigrises.com',
                'www.bigrises.com',
                'test.taearif.com',
                'mandhoor.com',
            ],
        ]);
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

    private function seedDomainSetting(): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'capacity-domain-' . uniqid('', true) . '@example.com',
        ]);

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'capacity-' . uniqid('', false) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);
    }

    /**
     * @return list<array{name: string}>
     */
    private function fakeDomainList(int $count): array
    {
        $domains = [];

        for ($i = 0; $i < $count; $i++) {
            $domains[] = ['name' => 'domain-' . $i . '.example.com'];
        }

        return $domains;
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
