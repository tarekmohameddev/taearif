<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Domains;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\DomainDnsRecordService;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Tests\Feature\Admin\AdminApiTestCase;

class CustomDomainControlCompletenessTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('DEMO_MODE=inactive');
        $_ENV['DEMO_MODE'] = 'inactive';
        $_SERVER['DEMO_MODE'] = 'inactive';

        $this->ensureAdminViewData();
        $this->mockDnsRecords();
        app(VercelDomainCache::class)->invalidate();
    }

    protected function tearDown(): void
    {
        app(VercelDomainCache::class)->invalidate();

        parent::tearDown();
    }

    /** @test */
    public function reattach_of_not_on_vercel_db_domain_reaches_active_or_pending(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $apex = 'reattach-' . uniqid('', false) . '.example.com';
        $domain = $this->seedDomain($apex, [
            'health_code' => 'not_on_vercel',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => false,
            'apex_verified' => false,
            'nameservers_ok' => true,
        ]);

        $this->fakeAttachProvisioning($apex);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? null;
        $this->assertIsArray($lastCheck);
        $this->assertTrue($lastCheck['apex_attached'] ?? $lastCheck['vercel_attached'] ?? false);
        $this->assertContains($domain->status, ['active', 'pending']);
        $this->assertContains($lastCheck['provisioning']['mode'] ?? null, ['admin_repair', 'initial']);
    }

    /** @test */
    public function reattach_refuses_at_capacity_without_typed_confirm_domain(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $apex = 'capacity-block-' . uniqid('', false) . '.example.com';
        $domain = $this->seedDomain($apex);

        $this->fakeAtCapacityInventory(50);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHasErrors('confirm_domain');
    }

    /** @test */
    public function reattach_with_capacity_confirm_still_surfaces_capacity_failure_from_provider(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $apex = 'capacity-confirm-' . uniqid('', false) . '.example.com';
        $domain = $this->seedDomain($apex);

        $this->fakeAtCapacityInventory(50);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), [
                'domain_id' => $domain->id,
                'confirm_domain' => $apex,
            ])
            ->assertRedirect();

        $domain->refresh();
        $provisioning = $domain->dns_records['last_check']['provisioning'] ?? [];
        $this->assertFalse($provisioning['mutations_attempted'] ?? true);
        $this->assertSame('capacity_reached', $provisioning['capacity_reason'] ?? null);
    }

    /** @test */
    public function status_mismatch_heals_via_repair_verify(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $apex = 'status-mismatch-' . uniqid('', false) . '.example.com';
        $domain = ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create([
                'email' => 'status-mismatch-' . uniqid('', true) . '@example.com',
            ])->id,
            'custom_name' => $apex,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'health_code' => 'not_on_vercel',
                    'auto_attach_custom_domain' => true,
                    'nameserver_check_enabled' => true,
                    'apex_attached' => false,
                    'vercel_attached' => false,
                    'apex_verified' => false,
                    'nameservers_ok' => true,
                ],
            ],
        ]);

        $this->fakeRepairEndpoints($apex);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.repair-verify'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? [];
        $this->assertTrue($lastCheck['apex_attached'] ?? $lastCheck['vercel_attached'] ?? false);
        $this->assertNotSame('not_on_vercel', $domain->health(true)['code']);
    }

    /** @test */
    public function ownership_txt_is_rendered_on_index_for_ownership_required_rows(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $challengeValue = 'vc-domain-verify=ownership-test-' . uniqid('', false);
        $domain = $this->seedDomain('ownership-' . uniqid('', false) . '.example.com', [
            'health_code' => 'ownership_required',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
            'ownership_challenge' => [
                'type' => 'txt',
                'domain' => '_vercel.example.com',
                'value' => $challengeValue,
            ],
        ]);

        $this->fakeInventory([$domain->custom_name]);

        $response = $this->get(route('admin.custom-domain.index', ['health' => 'ownership_required']));

        $response->assertOk();
        $response->assertSee($challengeValue, false);
        $response->assertSee('_vercel.example.com', false);
        $response->assertSee('domain-ownership-challenge', false);
    }

    /** @test */
    public function diagnostics_drawer_renders_persisted_last_check_payload(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $challengeValue = 'vc-domain-verify=diag-' . uniqid('', false);
        $domain = $this->seedDomain('diag-' . uniqid('', false) . '.example.com', [
            'health_code' => 'ownership_required',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
            'ownership_challenge' => [
                'type' => 'txt',
                'domain' => '_vercel.diag.example.com',
                'value' => $challengeValue,
            ],
            'last_check_at' => now()->toIso8601String(),
        ]);

        $response = $this->get(route('admin.custom-domain.diagnostics', ['id' => $domain->id]));

        $response->assertOk();
        $response->assertSee($challengeValue, false);
        $response->assertSee('_vercel.diag.example.com', false);
        $response->assertSee($domain->custom_name, false);
        $response->assertSee('name="confirm_domain"', false);
        $response->assertDontSee('type="hidden" name="confirm_domain"', false);
    }

    /** @test */
    public function claim_ownership_surfaces_pending_outcome(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $this->signInWebAdmin();

        $apex = 'claim-pending-' . uniqid('', false) . '.example.com';
        $domain = $this->seedDomain($apex, [
            'health_code' => 'ownership_required',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => false,
            'ownership_challenge' => [
                'type' => 'txt',
                'domain' => '_vercel.' . $apex,
                'value' => 'vc-domain-verify=pending',
            ],
        ]);

        $this->fakeRepairEndpoints($apex, withClaim: true, claimVerified: false);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.claim-ownership'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request) => $request->method() === 'POST'
            && str_contains($request->url(), '/claim'));

        $domain->refresh();
        $this->assertSame('claim_ownership', $domain->dns_records['last_check']['provisioning']['mode'] ?? null);
    }

    /** @test */
    public function claim_ownership_surfaces_terminal_active_outcome(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $apex = 'claim-active-' . uniqid('', false) . '.example.com';
        $domain = $this->seedDomain($apex, [
            'health_code' => 'ownership_required',
            'auto_attach_custom_domain' => true,
            'nameserver_check_enabled' => true,
            'apex_attached' => true,
            'apex_verified' => false,
            'nameservers_ok' => true,
            'ownership_challenge' => [
                'type' => 'txt',
                'domain' => '_vercel.' . $apex,
                'value' => 'vc-domain-verify=active',
            ],
        ]);

        $this->fakeRepairEndpoints($apex, withClaim: true, claimVerified: true);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.claim-ownership'), ['domain_id' => $domain->id])
            ->assertRedirect()
            ->assertSessionHas('success');

        $domain->refresh();
        $this->assertTrue($domain->dns_records['last_check']['apex_verified']
            ?? $domain->dns_records['last_check']['vercel_verified']
            ?? false);
    }

    /** @test */
    public function bulk_repair_isolates_failures_per_domain(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $this->signInWebAdmin();

        $goodApex = 'bulk-good-' . uniqid('', false) . '.example.com';
        $badApex = 'bulk-bad-' . uniqid('', false) . '.example.com';
        $good = $this->seedDomain($goodApex);
        $bad = $this->seedDomain($badApex);

        $this->fakeBulkRepairEndpoints($goodApex, $badApex);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.bulk-repair-verify'), [
                'ids' => [$good->id, $bad->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('success')
            ->assertSessionHas('error');

        $good->refresh();
        $bad->refresh();

        $this->assertTrue($good->dns_records['last_check']['apex_verified']
            ?? $good->dns_records['last_check']['vercel_verified']
            ?? false);
        $this->assertFalse($bad->dns_records['last_check']['apex_verified']
            ?? $bad->dns_records['last_check']['vercel_verified']
            ?? false);
    }

    /** @test */
    public function manual_refresh_busts_inventory_and_health_counter_caches(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $cache = app(VercelDomainCache::class);
        $staleFetchedAt = now()->subMinutes(30)->toIso8601String();
        Cache::put($cache->inventoryKey(), [
            'domains' => [],
            'names' => [],
            'metrics' => ['free_entries' => 10, 'total_entries' => 0],
            'count' => 0,
            'fetched_at' => $staleFetchedAt,
            'is_lower_bound' => false,
        ], 300);
        Cache::put($cache->healthCountersKey(), ['linked' => 1], now()->addMinutes(5));

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [['name' => 'fresh.example.com', 'verified' => true]],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.refresh-inventory'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse(Cache::has($cache->healthCountersKey()));
        $fresh = $cache->cached();
        $this->assertNotNull($fresh);
        $this->assertNotSame($staleFetchedAt, $fresh['fetched_at'] ?? null);
    }

    /** @test */
    public function setup_guide_route_exists_as_get_and_resolves_expected_url(): void
    {
        $route = Route::getRoutes()->getByName('admin.custom-domain.setup-guide');

        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('admin/domain/setup-guide', trim($route->uri(), '/'));
        $this->assertSame('/admin/domain/setup-guide', route('admin.custom-domain.setup-guide', absolute: false));
    }

    /** @test */
    public function setup_guide_route_keeps_custom_domains_permission_middleware(): void
    {
        $route = Route::getRoutes()->getByName('admin.custom-domain.setup-guide');

        $this->assertNotNull($route);
        $this->assertContains('checkpermission:Custom Domains', $route->gatherMiddleware());
    }

    /** @test */
    public function authorized_admin_can_open_setup_guide_view_without_provider_requests_or_domain_rows(): void
    {
        $this->configureVercel();
        $this->signInWebAdmin();

        ApiDomainSetting::query()->delete();

        config([
            'services.vercel.nameservers' => ['ns1.unit.test', 'ns2.unit.test'],
            'services.vercel.external_dns' => [
                'apex_record_type' => 'A',
                'apex_record_host' => '@',
                'apex_record_value' => '192.0.2.10',
                'www_record_type' => 'CNAME',
                'www_record_host' => 'www',
                'www_record_value' => 'customer.unit-vercel.test',
            ],
        ]);

        Http::fake();

        $response = $this->get(route('admin.custom-domain.setup-guide'));
        $html = $response->getContent();

        $response->assertOk();
        $response->assertViewIs('admin.domains.setup-guide');
        $this->assertIsString($html);
        $response->assertSeeText(__('domain_setup_guide.title'));
        $response->assertSeeText(__('domain_setup_guide.tab_overview'));
        $response->assertSeeText(__('domain_setup_guide.tab_vercel_ns'));
        $response->assertSeeText(__('domain_setup_guide.tab_external_dns'));
        $response->assertSeeText(__('domain_setup_guide.tab_troubleshooting'));
        $response->assertSeeText(__('domain_setup_guide.tab_support'));
        $response->assertSee('ns1.unit.test', false);
        $response->assertSee('ns2.unit.test', false);
        $response->assertSeeText(__('domain_dns.mode_vercel_ns'));
        $response->assertSeeText(__('domain_dns.mode_external_dns'));
        $response->assertSeeText(__('domain_setup_guide.diagnostics_title'));
        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('id="dns-guide-overview-tab"', $html);
        $this->assertStringContainsString('href="#dns-guide-overview"', $html);
        $this->assertStringContainsString('aria-controls="dns-guide-overview"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('href="#dns-guide-vercel-ns"', $html);
        $this->assertStringContainsString('aria-controls="dns-guide-vercel-ns"', $html);
        $this->assertStringContainsString('href="#dns-guide-external-dns"', $html);
        $this->assertStringContainsString('aria-controls="dns-guide-external-dns"', $html);
        $this->assertStringContainsString('href="#dns-guide-troubleshooting"', $html);
        $this->assertStringContainsString('aria-controls="dns-guide-troubleshooting"', $html);
        $this->assertStringContainsString('href="#dns-guide-support"', $html);
        $this->assertStringContainsString('aria-controls="dns-guide-support"', $html);
        $this->assertStringContainsString('class="nav-link active"', $html);
        $this->assertStringContainsString('id="dns-guide-overview"', $html);
        $this->assertStringContainsString('role="tabpanel"', $html);
        $this->assertStringContainsString('aria-labelledby="dns-guide-overview-tab"', $html);
        $this->assertStringContainsString('id="dns-guide-vercel-ns"', $html);
        $this->assertStringContainsString('aria-labelledby="dns-guide-vercel-ns-tab"', $html);
        $this->assertStringContainsString('id="dns-guide-external-dns"', $html);
        $this->assertStringContainsString('aria-labelledby="dns-guide-external-dns-tab"', $html);
        $this->assertStringContainsString('id="dns-guide-troubleshooting"', $html);
        $this->assertStringContainsString('aria-labelledby="dns-guide-troubleshooting-tab"', $html);
        $this->assertStringContainsString('id="dns-guide-support"', $html);
        $this->assertStringContainsString('aria-labelledby="dns-guide-support-tab"', $html);
        $this->assertStringContainsString('class="tab-pane fade show active"', $html);
        $this->assertStringNotContainsString('class="tab-pane fade show active" id="dns-guide-vercel-ns"', $html);
        $this->assertStringNotContainsString('class="tab-pane fade show active" id="dns-guide-external-dns"', $html);
        $this->assertStringNotContainsString('class="tab-pane fade show active" id="dns-guide-troubleshooting"', $html);
        $this->assertStringNotContainsString('class="tab-pane fade show active" id="dns-guide-support"', $html);
        $this->assertStringContainsString('data-guide-target="#dns-guide-vercel-ns"', $html);
        $this->assertStringContainsString('data-guide-target="#dns-guide-external-dns"', $html);
        $response->assertSeeText(__('domain_setup_guide.support_external_title'));
        $response->assertSeeText(__('domain_setup_guide.support_vercel_ns_title'));
        $this->assertStringContainsString('id="domainSetupGuideExternalDnsTemplate"', $html);
        $this->assertStringContainsString('id="domainSetupGuideExternalDnsCopyBtn"', $html);
        $this->assertStringContainsString('data-copy-target="domainSetupGuideExternalDnsTemplate"', $html);
        $this->assertStringContainsString('id="domainSetupGuideExternalDnsCopyStatus"', $html);
        $this->assertStringContainsString('id="domainSetupGuideVercelNsTemplate"', $html);
        $this->assertStringContainsString('id="domainSetupGuideVercelNsCopyBtn"', $html);
        $this->assertStringContainsString('data-copy-target="domainSetupGuideVercelNsTemplate"', $html);
        $this->assertStringContainsString('id="domainSetupGuideVercelNsCopyStatus"', $html);
        $this->assertStringContainsString('class="btn btn-outline-primary btn-sm mr-2 domain-setup-guide__copy-btn"', $html);
        $response->assertSeeText(__('domain_setup_guide.placeholder_domain'));
        $response->assertSee('192.0.2.10', false);
        $response->assertSee('customer.unit-vercel.test', false);
        $response->assertDontSee('cname.vercel-dns-0.com', false);
        $response->assertDontSee('.example.com', false);

        Http::assertNothingSent();
    }

    /** @test */
    public function guest_is_redirected_away_from_setup_guide_by_admin_auth_middleware(): void
    {
        $response = $this->get(route('admin.custom-domain.setup-guide'));

        $response->assertRedirect(route('admin.login'));
    }

    /** @test */
    public function domains_index_shows_setup_guide_link_for_authorized_admin(): void
    {
        $this->configureVercel();
        $this->signInWebAdmin();
        Http::fake();

        $response = $this->get(route('admin.custom-domain.index'));

        $response->assertOk();
        $response->assertSee(route('admin.custom-domain.setup-guide'), false);
        $response->assertSeeText(__('domain_setup_guide.toolbar_link'));
    }

    /**
     * @test
     * @dataProvider protectedNewRouteNames
     */
    public function new_domain_routes_require_custom_domains_permission(string $routeName, string $method, array $payload): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $role = Role::factory()->create([
            'permissions' => json_encode(['Dashboard']),
        ]);
        $admin = Admin::factory()->create(['status' => true, 'role_id' => $role->id]);
        $this->app['auth']->guard('admin')->setUser($admin);

        $domain = $this->seedDomain('perm-' . uniqid('', false) . '.example.com');
        $payload = $this->resolveRoutePayload($routeName, $payload, $domain);

        $url = $routeName === 'admin.custom-domain.diagnostics'
            ? route($routeName, ['id' => $domain->id])
            : route($routeName);

        $response = $this->from(route('admin.custom-domain.index'))->{$method}($url, $payload);

        if ($routeName === 'admin.custom-domain.diagnostics') {
            $response->assertRedirect(route('admin.dashboard'));
        } else {
            $response->assertRedirect(route('admin.dashboard'));
        }
    }

    /** @return array<string, array{0: string, 1: string, 2: array<string, mixed>}> */
    public static function protectedNewRouteNames(): array
    {
        return [
            'diagnostics' => ['admin.custom-domain.diagnostics', 'get', []],
            'setup_guide' => ['admin.custom-domain.setup-guide', 'get', []],
            'dns_mode' => ['admin.custom-domain.dns-mode', 'post', ['domain_id' => 0, 'dns_mode' => 'external_dns', 'confirm_domain' => 'x.example.com']],
            'legacy_adopt' => ['admin.custom-domain.legacy-orphan.adopt', 'post', ['legacy_id' => 1]],
            'legacy_delete' => ['admin.custom-domain.legacy-orphan.delete', 'post', ['legacy_id' => 1, 'confirm_domain' => 'x.example.com']],
            'stray_www' => ['admin.custom-domain.stray-www.remove', 'post', ['www' => 'www.x.example.com', 'confirm_domain' => 'www.x.example.com']],
            'fix_redirect' => ['admin.custom-domain.www.fix-redirect', 'post', ['domain_id' => 0, 'confirm_domain' => 'x.example.com']],
            'claim' => ['admin.custom-domain.claim-ownership', 'post', ['domain_id' => 0]],
            'bulk_repair' => ['admin.custom-domain.bulk-repair-verify', 'post', ['ids' => [0]]],
            'refresh' => ['admin.custom-domain.refresh-inventory', 'post', []],
        ];
    }

    /**
     * @test
     * @dataProvider throttledPostRouteNames
     */
    public function throttled_post_routes_declare_rate_limit_middleware(string $routeName): void
    {
        $route = Route::getRoutes()->getByName($routeName);
        $this->assertNotNull($route);
        $hasThrottle = collect($route->gatherMiddleware())
            ->contains(fn (string $middleware) => str_starts_with($middleware, 'throttle:'));
        $this->assertTrue($hasThrottle, "Route {$routeName} must declare throttle middleware.");
    }

    /** @return array<string, array{0: string}> */
    public static function throttledPostRouteNames(): array
    {
        return [
            'dns_mode' => ['admin.custom-domain.dns-mode'],
            'legacy_adopt' => ['admin.custom-domain.legacy-orphan.adopt'],
            'legacy_delete' => ['admin.custom-domain.legacy-orphan.delete'],
            'stray_www' => ['admin.custom-domain.stray-www.remove'],
            'fix_redirect' => ['admin.custom-domain.www.fix-redirect'],
            'claim' => ['admin.custom-domain.claim-ownership'],
            'bulk_repair' => ['admin.custom-domain.bulk-repair-verify'],
            'refresh' => ['admin.custom-domain.refresh-inventory'],
        ];
    }

    /** @test */
    public function destructive_actions_reject_platform_hostnames(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $platform = 'taearif.com';

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.stray-www.remove'), [
                'www' => 'www.' . $platform,
                'confirm_domain' => 'www.' . $platform,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.cleanup-vercel-orphan'), [
                'apex' => $platform,
                'confirm_domain' => $platform,
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    /** @test */
    public function destructive_actions_require_matching_typed_confirm_domain_for_stray_www(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->signInWebAdmin();

        $this->from(route('admin.custom-domain.index'))
            ->post(route('admin.custom-domain.stray-www.remove'), [
                'www' => 'www.stray.example.com',
                'confirm_domain' => 'wrong.example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    /** @test */
    public function mutation_controller_does_not_manually_assign_status_or_ssl(): void
    {
        $source = file_get_contents(app_path('Http/Controllers/Admin/CustomDomainController.php'));
        $this->assertIsString($source);

        $this->assertDoesNotMatchRegularExpression(
            '/\$domain->status\s*=/',
            $source,
            'Controller must not assign domain status directly; use DomainStatusSyncService.'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/\$domain->ssl\s*=/',
            $source,
            'Controller must not assign domain ssl directly; use DomainStatusSyncService.'
        );
        $this->assertDoesNotMatchRegularExpression(
            "/->update\\(\\[\\s*'status'\\s*=>\\s*'(active|failed)'/",
            $source,
            'Controller must not bulk-update status to active/failed.'
        );
    }

    /** @test */
    public function unpaired_www_panel_lists_only_stray_www_not_apex_without_www(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        ApiDomainSetting::create([
            'user_id' => User::factory()->tenant()->create()->id,
            'custom_name' => 'known-apex.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $service = app(\App\Services\Vercel\DomainReconciliationService::class);
        $report = $service->buildReportFromSnapshot([
            'domains' => [
                ['name' => 'known-apex.example.com', 'verified' => true],
                ['name' => 'www.stray-only.example.com', 'verified' => true, 'redirect' => 'stray-only.example.com', 'redirectStatusCode' => 301],
            ],
            'names' => ['known-apex.example.com', 'www.stray-only.example.com'],
            'metrics' => [],
            'fetched_at' => now()->toIso8601String(),
            'is_lower_bound' => false,
        ]);

        $this->assertCount(1, $report['apex_without_www']);
        $this->assertCount(1, $report['www_without_apex']);
        $this->assertCount(1, $report['unpaired_www']);
        $this->assertSame('www.stray-only.example.com', $report['unpaired_www'][0]['vercel_name'] ?? null);
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

    private function mockDnsRecords(?bool $apexMatches = true, ?bool $wwwMatches = true): void
    {
        $this->mock(DomainDnsRecordService::class, function ($mock) use ($apexMatches, $wwwMatches) {
            $mock->shouldReceive('inspect')->andReturn([
                'apex_records' => [['type' => 'A', 'value' => '76.76.21.21']],
                'www_records' => [['type' => 'CNAME', 'value' => 'cname.vercel-dns.com']],
                'apex_addresses' => ['76.76.21.21'],
                'apex_cnames' => [],
                'www_addresses' => [],
                'www_cnames' => ['cname.vercel-dns.com'],
                'apex_matches_recommended' => $apexMatches,
                'www_matches_recommended' => $wwwMatches,
                'dns_provider_reachable' => true,
                'dns_lookup_unknown' => false,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $lastCheck
     */
    private function seedDomain(string $apex, array $lastCheck = []): ApiDomainSetting
    {
        $user = User::factory()->tenant()->create([
            'email' => 'control-' . uniqid('', true) . '@example.com',
        ]);

        $dnsRecords = $lastCheck === [] ? [] : ['last_check' => $lastCheck];

        return ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => $apex,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => $dnsRecords,
        ]);
    }

    /**
     * @param  list<string>  $names
     */
    private function fakeInventory(array $names): void
    {
        $domains = array_map(
            static fn (string $name): array => ['name' => $name, 'verified' => true],
            $names
        );

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => $domains,
                'pagination' => ['count' => count($domains), 'next' => null, 'prev' => null],
            ], 200),
        ]);
    }

    private function fakeAtCapacityInventory(int $count): void
    {
        $domains = [];
        for ($i = 0; $i < $count; $i++) {
            $domains[] = ['name' => 'filled-' . $i . '.example.com', 'verified' => true];
        }

        Http::fake(function (Request $request) use ($domains, $count) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => $domains,
                    'pagination' => ['count' => $count, 'next' => null, 'prev' => null],
                ], 200);
            }

            if ($method === 'GET' && preg_match('#/domains/[^/]+#', $url)) {
                return Http::response(['error' => 'not_found'], 404);
            }

            if ($method === 'POST' && str_contains($url, '/domains')) {
                return Http::response(['error' => ['code' => 'project_domain_limit_reached']], 400);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
    }

    private function fakeAttachProvisioning(string $apex): void
    {
        $state = [
            'account' => null,
            'project_domains' => [],
            'project_domain' => null,
        ];

        Http::fake(function (Request $request) use ($apex, &$state) {
            $url = $request->url();
            $method = $request->method();
            $encoded = rawurlencode($apex);

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response(['id' => 'prj_test', 'accountId' => 'team_test'], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => $state['project_domains'],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && preg_match('#/v(?:5|7)/domains/' . preg_quote($encoded, '#') . '#', $url)) {
                if ($state['account'] === null) {
                    return Http::response(['error' => 'not_found'], 404);
                }

                return Http::response(['domain' => $state['account']], 200);
            }

            if ($method === 'POST' && str_contains($url, '/v7/domains')) {
                $state['account'] = [
                    'name' => $apex,
                    'zone' => true,
                    'verified' => true,
                    'serviceType' => 'external',
                ];

                return Http::response(['domain' => $state['account']], 200);
            }

            if ($method === 'POST' && str_contains($url, '/domains') && ! str_contains($url, '/verify') && ! str_contains($url, '/claim')) {
                $state['project_domains'][] = ['name' => $apex, 'verified' => true];
                $state['project_domain'] = ['name' => $apex, 'verified' => true, 'verification' => []];

                return Http::response(['name' => $apex, 'verified' => true], 200);
            }

            if ($method === 'GET' && preg_match('#/domains/' . preg_quote($encoded, '#') . '(?:\?|$)#', $url)) {
                foreach ($state['project_domains'] as $domain) {
                    if (strtolower((string) ($domain['name'] ?? '')) === $apex) {
                        return Http::response(['name' => $apex, 'verified' => true, 'verification' => []], 200);
                    }
                }

                return Http::response(['error' => 'not_found'], 404);
            }

            if (str_contains($url, '/v8/certs')) {
                return Http::response([
                    'certs' => [[
                        'id' => 'cert_attach',
                        'cns' => [$apex],
                        'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                    ]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (str_contains($url, '/v6/domains/' . $encoded . '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['name' => $apex, 'verified' => true], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });
    }

    private function fakeRepairEndpoints(string $domainName, bool $withClaim = false, bool $claimVerified = true): void
    {
        Http::fake(function (Request $request) use ($domainName, $withClaim, $claimVerified) {
            $url = $request->url();
            $method = $request->method();
            $apex = strtolower($domainName);

            if ($withClaim && $method === 'POST' && str_contains($url, '/claim')) {
                return Http::response(['name' => $apex, 'verified' => $claimVerified], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response(['id' => 'prj_test', 'accountId' => 'team_test'], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $apex, 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v8/certs')) {
                return Http::response([
                    'certs' => [[
                        'id' => 'cert_test',
                        'cns' => [$apex],
                        'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                    ]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && preg_match('#/v(?:5|7)/domains/([^/?]+)#', $url) && ! str_contains($url, '/config')) {
                return Http::response(['name' => $apex, 'zone' => true, 'verified' => true], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['name' => $apex, 'verified' => true], 200);
            }

            if (str_contains($url, '/v6/domains/' . rawurlencode($apex) . '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if (str_contains($url, '/domains/' . rawurlencode($apex))) {
                return Http::response(['name' => $apex, 'verified' => true, 'verification' => []], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });
    }

    private function fakeBulkRepairEndpoints(string $goodApex, string $badApex): void
    {
        Http::fake(function (Request $request) use ($goodApex, $badApex) {
            $url = $request->url();
            $method = $request->method();

            if (str_contains($url, rawurlencode($badApex)) || str_contains($url, $badApex)) {
                return Http::response(['error' => ['message' => 'boom']], 503);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response(['id' => 'prj_test', 'accountId' => 'team_test'], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $goodApex, 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (str_contains($url, '/v8/certs')) {
                return Http::response([
                    'certs' => [['id' => 'c1', 'cns' => [$goodApex], 'expiresAt' => 9999999999999]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (preg_match('#/v(?:5|7)/domains/#', $url)) {
                return Http::response(['name' => $goodApex, 'zone' => true, 'verified' => true], 200);
            }

            if (str_contains($url, '/domains/' . rawurlencode($goodApex))) {
                return Http::response(['name' => $goodApex, 'verified' => true], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['name' => $goodApex, 'verified' => true], 200);
            }

            if (str_contains($url, '/v6/domains/' . rawurlencode($goodApex) . '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            return Http::response(['error' => ['message' => 'unexpected']], 500);
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function resolveRoutePayload(string $routeName, array $payload, ApiDomainSetting $domain): array
    {
        if (isset($payload['domain_id']) && (int) $payload['domain_id'] === 0) {
            $payload['domain_id'] = $domain->id;
        }

        if (isset($payload['ids']) && $payload['ids'] === [0]) {
            $payload['ids'] = [$domain->id];
        }

        return $payload;
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
