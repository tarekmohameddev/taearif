<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainHealthFilterTest extends AdminApiTestCase
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
    public function health_issues_filter_excludes_unchecked_and_linked(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());
        $issue = $this->seedDomainWithHealth($this->nsIssueLastCheck());
        $unchecked = $this->seedDomainWithHealth([]);

        $this->fakeInventory([$linked->custom_name, $issue->custom_name, $unchecked->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'issues']));

        $response->assertOk();
        $response->assertSee($issue->custom_name, false);
        $response->assertDontSee($unchecked->custom_name, false);
        $response->assertDontSee($linked->custom_name, false);
        $response->assertSee(__('domain_health.filter_confirmed_issues'), false);
    }

    /** @test */
    public function health_unchecked_filter_shows_only_never_checked_domains(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $unchecked = $this->seedDomainWithHealth([]);
        $issue = $this->seedDomainWithHealth($this->nsIssueLastCheck());

        $this->fakeInventory([$issue->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'unchecked']));

        $response->assertOk();
        $response->assertSee($unchecked->custom_name, false);
        $response->assertDontSee($issue->custom_name, false);
        $response->assertSee(__('domain_health.filter_unchecked'), false);
    }

    /** @test */
    public function health_linked_filter_shows_only_working_domains(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());
        $issue = $this->seedDomainWithHealth($this->nsIssueLastCheck());

        $this->fakeInventory([$linked->custom_name, 'www.' . $linked->custom_name, $issue->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'linked']));

        $response->assertOk();
        $response->assertSee($linked->custom_name, false);
        $response->assertDontSee($issue->custom_name, false);
        $response->assertSee(__('domain_health.filter_linked'), false);
        $response->assertSee(__('domain_www.apex_and_www'), false);
    }

    /** @test */
    public function enable_www_promotes_apex_only_to_linked_and_updates_counters(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $apexOnly = $this->seedDomainWithHealth($this->apexOnlyLastCheck());
        $apex = $apexOnly->custom_name;

        $this->fakeInventoryMutations([
            ['name' => $apex, 'verified' => true],
        ], afterPostDomains: [
            ['name' => $apex, 'verified' => true],
            ['name' => 'www.' . $apex, 'verified' => true, 'redirect' => $apex, 'redirectStatusCode' => 301],
        ]);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.enable'), [
                'domain_id' => $apexOnly->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $apexOnly->refresh();
        $this->assertTrue($apexOnly->dns_records['last_check']['www_present'] ?? false);
        $this->assertTrue($apexOnly->dns_records['last_check']['www_redirect_correct'] ?? false);
        $this->assertSame('linked', $apexOnly->health()['code']);

        $index = $this->get(route('admin.custom-domain.index'));
        $index->assertOk();
        $index->assertSee(__('domain_health.show_apex_and_www_count', ['count' => 1]), false);
        $index->assertSee(__('domain_health.show_apex_only_count', ['count' => 0]), false);
        $index->assertSee(__('domain_www.apex_and_www'), false);
    }

    /** @test */
    public function disable_www_demotes_linked_to_apex_only_and_updates_counters(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());
        $apex = $linked->custom_name;

        $this->fakeInventoryMutations([
            ['name' => $apex, 'verified' => true],
            ['name' => 'www.' . $apex, 'verified' => true, 'redirect' => $apex, 'redirectStatusCode' => 301],
        ], afterDeleteDomains: [
            ['name' => $apex, 'verified' => true],
        ], allowDelete: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.www.disable'), [
                'domain_id' => $linked->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $linked->refresh();
        $this->assertFalse($linked->dns_records['last_check']['www_present'] ?? true);
        $this->assertFalse($linked->dns_records['last_check']['www_redirect_correct'] ?? true);
        $this->assertSame('apex_only', $linked->health()['code']);

        $index = $this->get(route('admin.custom-domain.index'));
        $index->assertOk();
        $index->assertSee(__('domain_health.show_apex_and_www_count', ['count' => 0]), false);
        $index->assertSee(__('domain_health.show_apex_only_count', ['count' => 1]), false);
        $index->assertSee(__('domain_www.apex_only'), false);
    }

    /** @test */
    public function health_not_on_vercel_filter_shows_only_orphan_candidates(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $orphan = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => false,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $onVercel = $this->seedDomainWithHealth([
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => false,
        ]);

        $this->fakeInventory([$onVercel->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'not_on_vercel']));

        $response->assertOk();
        $response->assertSee($orphan->custom_name, false);
        $response->assertDontSee($onVercel->custom_name, false);
    }

    /** @test */
    public function health_ownership_required_filter_matches_txt_challenge_rows(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $ownership = $this->seedDomainWithHealth([
            'health_code' => 'ownership_required',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
            'ownership_challenge' => [
                'type' => 'txt',
                'domain' => '_vercel.example.com',
                'value' => 'vc-domain-verify=abc',
            ],
        ]);
        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());

        $this->fakeInventory([$ownership->custom_name, $linked->custom_name, 'www.' . $linked->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'ownership_required']));

        $response->assertOk();
        $response->assertSee($ownership->custom_name, false);
        $response->assertDontSee($linked->custom_name, false);
    }

    /** @test */
    public function health_dns_misconfigured_filter_matches_misconfigured_rows(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $misconfigured = $this->seedDomainWithHealth([
            'health_code' => 'dns_misconfigured',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => true,
        ]);
        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());

        $this->fakeInventory([$misconfigured->custom_name, $linked->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'dns_misconfigured']));

        $response->assertOk();
        $response->assertSee($misconfigured->custom_name, false);
        $response->assertDontSee($linked->custom_name, false);
    }

    /** @test */
    public function health_apex_only_filter_matches_rows_without_valid_www_redirect(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $apexOnly = $this->seedDomainWithHealth([
            'health_code' => 'apex_only',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => false,
            'www_redirect_correct' => false,
        ]);
        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());

        $this->fakeInventory([$apexOnly->custom_name, $linked->custom_name, 'www.' . $linked->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'apex_only']));

        $response->assertOk();
        $response->assertSee($apexOnly->custom_name, false);
        $response->assertDontSee($linked->custom_name, false);
    }

    /** @test */
    public function global_health_counters_link_to_filtered_views(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $linked = $this->seedDomainWithHealth($this->linkedLastCheck());
        $this->seedDomainWithHealth($this->nsIssueLastCheck());

        $this->fakeInventory([$linked->custom_name, 'www.' . $linked->custom_name]);

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee('health=linked', false);
        $response->assertSee('health=apex_only', false);
        $response->assertSee('health=issues', false);
        $response->assertSee('health=unchecked', false);
        $response->assertSee(__('domain_health.show_apex_and_www_count', ['count' => 1]), false);
        $response->assertSee(__('domain_health.show_apex_only_count', ['count' => 0]), false);
    }

    /** @test */
    public function invalid_health_filter_returns_not_found(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'not_a_real_filter']));

        $response->assertOk();
        $response->assertViewIs('errors.404');
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
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function linkedLastCheck(): array
    {
        return [
            'health_code' => 'linked',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => true,
            'www_redirect_correct' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function apexOnlyLastCheck(): array
    {
        return [
            'health_code' => 'apex_only',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'account_domain_present' => true,
            'zone_enabled' => true,
            'nameservers_ok' => true,
            'dns_misconfigured' => false,
            'ssl_ready' => true,
            'certificate_readiness' => 'issued',
            'www_present' => false,
            'www_redirect_correct' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nsIssueLastCheck(): array
    {
        return [
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => true,
            'nameservers_ok' => false,
            'message' => 'Nameservers are not pointing to Vercel yet.',
        ];
    }

    /**
     * @param  list<string>  $names
     */
    private function fakeInventory(array $names): void
    {
        $domains = [];

        foreach ($names as $name) {
            $entry = ['name' => $name, 'verified' => true];

            if (str_starts_with($name, 'www.')) {
                $apex = substr($name, 4);
                $entry['redirect'] = $apex;
                $entry['redirectStatusCode'] = 301;
            }

            $domains[] = $entry;
        }

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $domains,
                'pagination' => ['count' => count($domains), 'next' => null, 'prev' => null],
            ], 200),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $domains
     * @param  list<array<string, mixed>>|null  $afterPostDomains
     * @param  list<array<string, mixed>>|null  $afterDeleteDomains
     */
    private function fakeInventoryMutations(
        array $domains,
        ?array $afterPostDomains = null,
        ?array $afterDeleteDomains = null,
        bool $allowDelete = false
    ): void {
        $state = ['domains' => $domains];

        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$state, $afterPostDomains, $afterDeleteDomains, $allowDelete) {
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
                    'domains' => $state['domains'],
                    'pagination' => ['count' => count($state['domains']), 'next' => null, 'prev' => null],
                ], 200);
            }

            if ($allowDelete && $method === 'GET' && preg_match('#/v9/projects/prj_test/domains/([^/?]+)#', $url, $matches)) {
                $name = strtolower(rawurldecode($matches[1]));
                foreach ($state['domains'] as $domain) {
                    if (strtolower((string) ($domain['name'] ?? '')) === $name) {
                        return Http::response($domain, 200);
                    }
                }

                return Http::response(['error' => 'not found'], 404);
            }

            if ($allowDelete && $method === 'PATCH' && str_contains($url, '/domains/')) {
                return Http::response(null, 200);
            }

            if ($allowDelete && $method === 'DELETE' && str_contains($url, '/domains/')) {
                if ($afterDeleteDomains !== null) {
                    $state['domains'] = $afterDeleteDomains;
                }

                return Http::response(null, 200);
            }

            if ($method === 'POST' && str_contains($url, '/domains')) {
                if ($afterPostDomains !== null) {
                    $state['domains'] = $afterPostDomains;
                }

                return Http::response(['name' => 'www.example.com', 'verified' => true], 200);
            }

            return Http::response(['error' => 'unexpected', 'url' => $url, 'method' => $method], 500);
        });
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    private function seedDomainWithHealth(array $lastCheck): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'health-filter-' . uniqid('', true) . '@example.com',
        ]);

        $dnsRecords = $lastCheck === [] ? [] : ['last_check' => $lastCheck];

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'health-filter-' . uniqid('', false) . '.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => $dnsRecords,
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
