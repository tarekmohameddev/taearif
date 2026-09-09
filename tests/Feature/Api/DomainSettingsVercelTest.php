<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Events\TenantActivityOccurred;
use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\DomainDnsRecordService;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DomainSettingsVercelTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        if (! Schema::hasTable('api_domains_settings') || ! Schema::hasTable('users')) {
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
            'services.vercel.nameservers' => [
                'ns1.vercel-dns.com',
                'ns2.vercel-dns.com',
            ],
            'services.vercel.auto_attach_custom_domain' => true,
            'services.vercel.check_nameservers' => true,
            'services.vercel.health_failure_threshold' => 1,
            'services.vercel.health_failure_grace_hours' => 0,
            'services.vercel.max_project_domains' => 50,
            'services.vercel.max_domains_per_tenant' => 100,
            'services.vercel.sync_pace_us' => 0,
            'services.vercel.sync_verify_pace_us' => 0,
        ]);
        $this->mockDnsRecords();
    }

    /**
     * @param  list<string>  $names
     * @return list<array{name: string, verified: bool}>
     */
    private function inventoryPayload(array $names, bool $verified = false): array
    {
        return array_map(
            static fn (string $name): array => ['name' => $name, 'verified' => $verified],
            $names
        );
    }

    /**
     * @param  list<string>  $inventoryNames
     */
    private function fakePreflightInventory(array $inventoryNames): void
    {
        $domains = $this->inventoryPayload($inventoryNames);

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domains) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => $domains,
                    'pagination' => ['count' => count($domains), 'next' => null],
                ], 200);
            }

            if ($method === 'POST' && str_contains($url, '/v10/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/verify')) {
                return Http::response(['name' => 'unexpected.example.com', 'verified' => false], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v6/domains/') && str_contains($url, '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if ($method === 'GET' && str_contains($url, '/domains/')) {
                return Http::response(['name' => 'unknown', 'verified' => false], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['verified' => false], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
    }

    /**
     * @param  list<string>  $domainNames
     */
    private function respondToAccountDomainAndCertificates(\Illuminate\Http\Client\Request $request, array $domainNames)
    {
        $url = $request->url();
        $method = $request->method();

        if ($method === 'GET' && str_contains($url, '/v8/certs') && ! preg_match('#/v8/certs/[^/?]#', $url)) {
            $certs = [];
            foreach ($domainNames as $name) {
                $certs[] = [
                    'id' => 'cert_' . md5($name),
                    'cns' => [$name],
                    'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                    'autoRenew' => true,
                ];
            }

            return Http::response([
                'certs' => $certs,
                'pagination' => ['next' => null],
            ], 200);
        }

        if ($method === 'GET'
            && preg_match('#/v(?:5|7)/domains/([^/?]+)#', $url, $matches)
            && ! str_contains($url, '/config')) {
            $apex = strtolower(rawurldecode($matches[1]));
            $known = array_map('strtolower', $domainNames);

            if (in_array($apex, $known, true)) {
                return Http::response([
                    'name' => $apex,
                    'zone' => true,
                    'verified' => true,
                ], 200);
            }

            return Http::response(['error' => 'not_found'], 404);
        }

        if ($method === 'POST' && str_contains($url, '/v7/domains')) {
            $postedName = strtolower((string) ($request->data()['name'] ?? ''));

            return Http::response([
                'domain' => [
                    'name' => $postedName,
                    'zone' => true,
                    'verified' => false,
                ],
            ], 200);
        }

        if ($method === 'PATCH' && preg_match('#/v3/domains/([^/?]+)#', $url, $matches)) {
            $apex = strtolower(rawurldecode($matches[1]));

            return Http::response([
                'domain' => [
                    'name' => $apex,
                    'zone' => true,
                    'verified' => true,
                ],
            ], 200);
        }

        return null;
    }

    /**
     * @param  list<string>  $domainNames
     */
    private function fakeVercelSyncEndpoints(array $domainNames, bool $verified = true): void
    {
        $inventoryDomains = array_map(
            static function (string $name) use ($verified): array {
                $domain = [
                    'name' => $name,
                    'verified' => $verified,
                ];

                if (str_starts_with($name, 'www.')) {
                    $domain['redirect'] = substr($name, 4);
                    $domain['redirectStatusCode'] = 301;
                }

                return $domain;
            },
            $domainNames
        );

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domainNames, $verified, $inventoryDomains) {
            $url = $request->url();
            $method = $request->method();

            $accountOrCert = $this->respondToAccountDomainAndCertificates($request, $domainNames);
            if ($accountOrCert !== null) {
                return $accountOrCert;
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => $inventoryDomains,
                    'pagination' => ['next' => null],
                ], 200);
            }

            foreach ($domainNames as $name) {
                if ($method === 'GET' && str_contains($url, '/v6/domains/' . rawurlencode($name) . '/config')) {
                    return Http::response(['misconfigured' => false], 200);
                }

                if (str_contains($url, '/domains/' . rawurlencode($name))) {
                    if (str_contains($url, '/verify') && $method === 'POST') {
                        return Http::response(['name' => $name, 'verified' => $verified], 200);
                    }

                    if ($method === 'GET') {
                        return Http::response(['name' => $name, 'verified' => $verified, 'verification' => []], 200);
                    }
                }
            }

            if ($method === 'GET' && str_contains($url, '/v6/domains/') && str_contains($url, '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if ($method === 'GET' && str_contains($url, '/domains/')) {
                return Http::response(['error' => 'not_found'], 404);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
    }

    /**
     * @param  list<string>  $inventoryDomains
     */
    private function fakeVercelStoreFlow(
        string $domainName,
        bool $verified = false,
        array $inventoryDomains = [],
        ?callable $responder = null
    ): void {
        $attachedProjectDomains = [];

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domainName, $verified, $inventoryDomains, $responder, &$attachedProjectDomains) {
            if ($responder !== null) {
                $custom = $responder($request);
                if ($custom !== null) {
                    return $custom;
                }
            }

            $url = $request->url();
            $method = $request->method();

            $knownDomains = array_values(array_unique(array_merge(
                [$domainName],
                $inventoryDomains
            )));
            $accountOrCert = $this->respondToAccountDomainAndCertificates($request, $knownDomains);
            if ($accountOrCert !== null) {
                return $accountOrCert;
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                $listed = array_values(array_unique(array_merge(
                    $inventoryDomains,
                    array_keys($attachedProjectDomains)
                )));

                return Http::response([
                    'domains' => array_map(
                        static fn (string $name): array => [
                            'name' => $name,
                            'verified' => $verified,
                        ],
                        $listed
                    ),
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v6/domains/')) {
                if (preg_match('#/v6/domains/([^/]+)/config#', $url, $matches)) {
                    $configDomain = rawurldecode($matches[1]);

                    return Http::response(['misconfigured' => false], 200);
                }
            }

            if ($method === 'POST' && str_contains($url, '/v10/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/verify')) {
                $postedName = strtolower((string) ($request->data()['name'] ?? $domainName));
                $attachedProjectDomains[$postedName] = $verified;

                return Http::response(['name' => $postedName, 'verified' => $verified], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                if (preg_match('#/domains/([^/]+)/verify#', $url, $matches)) {
                    $verifiedName = rawurldecode($matches[1]);

                    return Http::response(['name' => $verifiedName, 'verified' => $verified], 200);
                }

                return Http::response(['name' => $domainName, 'verified' => $verified], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/') && str_contains($url, '/domains/')) {
                if (preg_match('#/domains/([^/?]+)#', $url, $matches)) {
                    $fetchedName = strtolower(rawurldecode($matches[1]));
                    $knownAttached = array_map('strtolower', array_merge(
                        $inventoryDomains,
                        array_keys($attachedProjectDomains)
                    ));

                    if (! in_array($fetchedName, $knownAttached, true)) {
                        return Http::response(['error' => 'not_found'], 404);
                    }

                    $isVerified = $attachedProjectDomains[$fetchedName] ?? $verified;

                    return Http::response([
                        'name' => $fetchedName,
                        'verified' => $isVerified,
                        'verification' => [],
                    ], 200);
                }
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
    }

    private function actingTenant(): User
    {
        $tenant = User::factory()->tenant()->create([
            'email' => 'domain-tenant-' . uniqid('', true) . '@example.com',
        ]);
        ApiDomainSetting::where('user_id', $tenant->id)->delete();
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    private function mockNameservers(bool $ok): void
    {
        $this->mock(DnsNameserverChecker::class, function ($mock) use ($ok) {
            $mock->shouldReceive('getObservedNameservers')->andReturn(
                $ok ? ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'] : ['ns1.example.com']
            );
            $mock->shouldReceive('hasExpectedNameservers')->andReturn($ok);
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

    public function test_store_creates_pending_and_calls_vercel(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('mybrand.com');

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'mybrand.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.custom_name', 'mybrand.com')
            ->assertJsonPath('verification.verified', false)
            ->assertJsonPath('verification.nameservers_ok', false)
            ->assertJsonPath('verification.status', 'pending')
            ->assertJsonPath('dnsInstructions.mode', 'nameservers');

        $this->assertNotEmpty($response->json('verification.message'));

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'mybrand.com',
            'status' => 'pending',
        ]);
    }

    public function test_store_returns_verified_when_nameservers_already_ok(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('ready.example.com', verified: true);

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'ready.example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.ssl', true)
            ->assertJsonPath('verification.verified', true)
            ->assertJsonPath('verification.nameservers_ok', true)
            ->assertJsonPath('verification.status', 'active');

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'ready.example.com',
            'status' => 'active',
        ]);

        $response->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('dnsInstructions.nameservers.0', 'ns1.vercel-dns.com');
    }

    public function test_store_without_auto_attach_skips_vercel_http(): void
    {
        $this->skipIfMissingSchema();
        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
            'services.vercel.auto_attach_custom_domain' => false,
            'services.vercel.check_nameservers' => true,
            'services.vercel.nameservers' => [
                'ns1.vercel-dns.com',
                'ns2.vercel-dns.com',
            ],
        ]);
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        Http::fake();

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'local-only.example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('verification.verified', false);

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'local-only.example.com',
        ]);
        Http::assertNothingSent();
    }

    public function test_store_without_nameserver_check_activates_when_vercel_verified(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.check_nameservers' => false]);
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('skip-ns.example.com', verified: true);

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'skip-ns.example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('verification.verified', true)
            ->assertJsonPath('verification.nameservers_ok', true);

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'skip-ns.example.com',
            'status' => 'active',
        ]);
    }

    public function test_tenant_destroy_route_is_not_registered(): void
    {
        $this->skipIfMissingSchema();
        $this->actingTenant();

        $hasDestroyRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->contains(fn ($route) => in_array('DELETE', $route->methods(), true)
                && str_contains($route->uri(), 'settings/domain/{id}'));
        $this->assertFalse($hasDestroyRoute, 'Tenant domain delete must not be registered');
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Api\DomainSettingsController::class, 'destroy')
        );

        $response = $this->deleteJson('/api/settings/domain/1');
        $this->assertNotEquals(200, $response->status());
        $this->assertNotTrue($response->json('success'));
    }

    public function test_tenant_request_ssl_route_is_not_registered(): void
    {
        $this->skipIfMissingSchema();
        $this->actingTenant();

        $hasRequestSslRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->contains(fn ($route) => str_contains($route->uri(), 'settings/domain/request-ssl'));
        $this->assertFalse($hasRequestSslRoute, 'Tenant request-ssl must not be registered');
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Api\DomainSettingsController::class, 'requestSsl')
        );

        $response = $this->patchJson('/api/settings/domain/request-ssl', ['id' => 1]);
        $this->assertNotEquals(200, $response->status());
        $this->assertNotTrue($response->json('success'));
    }

    public function test_store_without_vercel_config_returns_503_and_does_not_persist(): void
    {
        $this->skipIfMissingSchema();
        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
        ]);
        $tenant = $this->actingTenant();

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'noconfig.example.com',
        ]);

        $response->assertStatus(503);
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'noconfig.example.com',
        ]);
    }

    public function test_store_rolls_back_when_vercel_fails(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('fail.example.com', responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/v10/projects/')) {
                return Http::response(['error' => ['message' => 'boom']], 500);
            }

            return null;
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'fail.example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('outcome', 'pending')
            ->assertJsonPath('retryable', true);
        $body = $response->json();
        $this->assertStringNotContainsString('boom', json_encode($body));
        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'fail.example.com',
            'status' => 'pending',
        ]);
    }

    public function test_store_returns_clear_message_when_vercel_project_domain_limit_reached(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('khnas.sa.net', responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/domains')) {
                return Http::response([
                    'error' => [
                        'code' => 'project_domain_limit_reached',
                        'message' => 'Unable to add the domain. The project taearif-v2 contains maximum allowed number of domains (50). If you would like to lift this constraint, please contact sales.',
                        'link' => 'https://vercel.com/contact/sales',
                    ],
                ], 400);
            }

            return null;
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'khnas.sa.net',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('code', 'HOSTING_CAPACITY_REACHED')
            ->assertJsonPath('message', 'We cannot add more domains right now because the hosting limit has been reached. Please contact support.');
        $body = json_encode($response->json());
        $this->assertStringNotContainsString('taearif-v2', $body);
        $this->assertStringNotContainsString('project_domain_limit_reached', $body);
        $this->assertStringNotContainsString('vercel.com', $body);
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'khnas.sa.net',
        ]);
    }

    public function test_store_falls_back_to_502_for_an_unmapped_vercel_error_code(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('unmapped.example.com', responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/v10/projects/')) {
                return Http::response([
                    'error' => ['code' => 'some_other_error', 'message' => 'nope'],
                ], 500);
            }

            return null;
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'unmapped.example.com',
        ]);

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Failed to register domain with hosting provider. Please try again later.')
            ->assertJsonMissingPath('code');
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'unmapped.example.com',
        ]);
    }

    public function test_store_success_response_does_not_leak_vercel_internals(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('leaky.example.com', responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/v10/projects/') && ! str_contains($request->url(), '/verify')) {
                return Http::response(['name' => 'leaky.example.com', 'verified' => false], 200);
            }

            if ($request->method() === 'POST' && str_contains($request->url(), '/verify')) {
                return Http::response([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'The project taearif-v2 rejected this request.',
                    ],
                ], 500);
            }

            if ($request->method() === 'GET' && str_contains($request->url(), '/v6/domains/leaky.example.com/config')) {
                return Http::response([
                    'error' => [
                        'code' => 'forbidden',
                        'message' => 'The project taearif-v2 rejected this request.',
                    ],
                ], 500);
            }

            return null;
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'leaky.example.com',
        ]);

        $response->assertCreated();

        // The leak lives on the SUCCESS path: verification.message is built from
        // the sync service, which used to pass the raw exception text straight out.
        $body = json_encode($response->json());
        $this->assertStringNotContainsString('taearif-v2', $body);
        $this->assertNotEmpty($response->json('verification.message'));
    }

    public function test_store_rejects_domain_owned_by_another_tenant(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $owner = User::factory()->tenant()->create([
            'email' => 'owner-' . uniqid('', true) . '@example.com',
        ]);
        ApiDomainSetting::create([
            'user_id' => $owner->id,
            'custom_name' => 'taken.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->actingTenant();
        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'taken.example.com',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Domain already in use')
            ->assertJsonPath('errors.0.message', 'This domain is already in use');
        $this->assertStringNotContainsString((string) $owner->email, $response->getContent());
        $this->assertStringNotContainsString((string) $owner->id, json_encode($response->json('errors')));
    }

    public function test_store_rejects_when_domain_limit_reached(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.max_domains_per_tenant' => 1]);
        $tenant = $this->actingTenant();

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'first-limit.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'second-limit.example.com',
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Domain limit reached');
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'second-limit.example.com',
        ]);
    }

    public function test_tenant_ssl_status_route_is_removed(): void
    {
        $this->skipIfMissingSchema();
        $this->actingTenant();

        $hasSslStatusRoute = collect(\Illuminate\Support\Facades\Route::getRoutes())
            ->contains(function ($route) {
                return str_contains($route->uri(), 'settings/domain/ssl-status');
            });
        $this->assertFalse($hasSslStatusRoute, 'Tenant ssl-status route must not be registered');
        $this->assertFalse(
            method_exists(\App\Http\Controllers\Api\DomainSettingsController::class, 'updateSslStatus')
        );

        $response = $this->patchJson('/api/settings/domain/ssl-status', [
            'domain_id' => 1,
            'ssl' => true,
        ]);

        // App exception handler may map MethodNotAllowed to 500 for unmatched PATCH on {id}.
        $this->assertNotEquals(200, $response->status());
        $this->assertNotTrue($response->json('success'));
        $content = $response->getContent();
        $this->assertStringNotContainsString('SSL status updated successfully', $content);
    }

    public function test_verify_success_sets_active_and_ssl(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'ok.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->fakeVercelSyncEndpoints(['ok.example.com'], verified: true);

        $response = $this->postJson('/api/settings/domain/verify', [
            'id' => $domain->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'active')
            ->assertJsonPath('data.verificationStatus', 'verified');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertTrue((bool) $domain->ssl);
    }

    public function test_verify_not_ready_stays_pending(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'pending.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/v9/projects/prj_test/domains/pending.example.com/verify*' => Http::response([
                'name' => 'pending.example.com',
                'verified' => false,
            ], 200),
        ]);

        $response = $this->postJson('/api/settings/domain/verify', [
            'id' => $domain->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('data.verificationStatus', 'pending');

        $domain->refresh();
        $this->assertSame('pending', $domain->status);
        $this->assertFalse((bool) $domain->ssl);
    }

    public function test_verify_external_dns_returns_record_based_instructions(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        config(['services.vercel.check_nameservers' => false]);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'records.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'recommended_ipv4' => ['76.76.21.21'],
                    'recommended_cname' => ['cname.vercel-dns.com'],
                ],
            ],
        ]);

        $this->fakeVercelSyncEndpoints(['records.example.com', 'www.records.example.com'], verified: false);

        $response = $this->postJson('/api/settings/domain/verify', [
            'id' => $domain->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('dnsInstructions.mode', 'records')
            ->assertJsonMissingPath('dnsInstructions.message')
            ->assertJsonMissingPath('dnsInstructions.nameservers')
            ->assertJsonPath('dnsInstructions.apex.host', '@')
            ->assertJsonPath('dnsInstructions.apex.records.0.type', 'A')
            ->assertJsonPath('dnsInstructions.apex.records.0.value', '76.76.21.21')
            ->assertJsonPath('dnsInstructions.www.host', 'www')
            ->assertJsonPath('dnsInstructions.www.records.0.type', 'CNAME')
            ->assertJsonPath('dnsInstructions.www.records.0.value', 'cname.vercel-dns.com');

        $this->assertSame(
            ['mode', 'apex', 'www'],
            array_keys($response->json('dnsInstructions'))
        );
    }

    public function test_tenant_delete_endpoint_is_not_available(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'gone.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake();

        $response = $this->deleteJson('/api/settings/domain/' . $domain->id);

        $this->assertNotEquals(200, $response->status());
        $this->assertDatabaseHas('api_domains_settings', ['id' => $domain->id]);
        Http::assertNothingSent();
    }

    public function test_index_returns_nameserver_instructions(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->actingTenant();

        $response = $this->getJson('/api/settings/domain');

        $response->assertOk()
            ->assertJsonPath('dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('dnsInstructions.nameservers.0', 'ns1.vercel-dns.com');
    }

    public function test_index_returns_per_domain_dns_guidance_for_mixed_modes(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'ns.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'records.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
            'primary' => false,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'recommended_ipv4' => ['76.76.21.21'],
                    'recommended_cname' => ['cname.vercel-dns.com'],
                ],
            ],
        ]);

        $response = $this->getJson('/api/settings/domain');

        $response->assertOk()
            ->assertJsonPath('dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('dnsInstructions.nameservers.0', 'ns1.vercel-dns.com')
            ->assertJsonPath('domains.0.custom_name', 'ns.example.com')
            ->assertJsonPath('domains.0.dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('domains.0.dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('domains.0.dnsInstructions.nameservers.0', 'ns1.vercel-dns.com')
            ->assertJsonPath('domains.1.custom_name', 'records.example.com')
            ->assertJsonPath('domains.1.dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('domains.1.dnsInstructions.mode', 'records')
            ->assertJsonMissingPath('domains.1.dnsInstructions.message')
            ->assertJsonMissingPath('domains.1.dnsInstructions.nameservers')
            ->assertJsonPath('domains.1.dnsInstructions.apex.records.0.type', 'A')
            ->assertJsonPath('domains.1.dnsInstructions.apex.records.0.value', '76.76.21.21')
            ->assertJsonPath('domains.1.dnsInstructions.www.records.0.type', 'CNAME')
            ->assertJsonPath('domains.1.dnsInstructions.www.records.0.value', 'cname.vercel-dns.com');

        $this->assertSame(
            ['mode', 'apex', 'www'],
            array_keys($response->json('domains.1.dnsInstructions'))
        );
    }

    public function test_show_returns_nameserver_instructions_for_vercel_ns_mode(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'show-ns.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $this->getJson('/api/settings/domain/' . $domain->id)
            ->assertOk()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('dnsInstructions.nameservers.0', 'ns1.vercel-dns.com');
    }

    public function test_show_returns_record_instructions_for_external_dns_mode(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'show-records.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'recommended_ipv4' => ['76.76.21.21'],
                    'recommended_cname' => ['cname.vercel-dns.com'],
                ],
            ],
        ]);

        $response = $this->getJson('/api/settings/domain/' . $domain->id);
        $response->assertOk()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('dnsInstructions.mode', 'records')
            ->assertJsonMissingPath('dnsInstructions.message')
            ->assertJsonMissingPath('dnsInstructions.nameservers')
            ->assertJsonPath('dnsInstructions.apex.records.0.type', 'A')
            ->assertJsonPath('dnsInstructions.apex.records.0.value', '76.76.21.21')
            ->assertJsonPath('dnsInstructions.www.records.0.type', 'CNAME')
            ->assertJsonPath('dnsInstructions.www.records.0.value', 'cname.vercel-dns.com');

        $this->assertSame(
            ['mode', 'apex', 'www'],
            array_keys($response->json('dnsInstructions'))
        );
        $this->assertSame(
            [
                'mode' => 'records',
                'apex' => [
                    'host' => '@',
                    'records' => [
                        ['type' => 'A', 'name' => '@', 'value' => '76.76.21.21'],
                    ],
                ],
                'www' => [
                    'host' => 'www',
                    'records' => [
                        ['type' => 'CNAME', 'name' => 'www', 'value' => 'cname.vercel-dns.com'],
                    ],
                ],
            ],
            $response->json('dnsInstructions')
        );
    }

    public function test_sync_command_activates_pending_when_ready(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-tenant-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'sync-ok.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->fakeVercelSyncEndpoints(['sync-ok.example.com'], true);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertTrue((bool) $domain->ssl);
    }

    public function test_sync_command_fails_active_when_missing_on_vercel(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-fail-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'missing.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $this->fakeVercelSyncEndpoints([]);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('failed', $domain->status);
        $this->assertFalse((bool) $domain->ssl);
    }

    public function test_sync_command_fails_when_expires_at_past(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-exp-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'expired.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now()->subYear(),
            'expires_at' => now()->subDay(),
        ]);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('failed', $domain->status);
        $this->assertFalse((bool) $domain->ssl);
    }

    public function test_sync_command_leaves_active_unchanged_when_still_ok(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'sync-ok2-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'still.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $this->fakeVercelSyncEndpoints(['still.example.com'], true);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertTrue((bool) $domain->ssl);
    }

    public function test_store_adds_apex_only(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $this->actingTenant();

        $postDomains = 0;
        $this->fakeVercelStoreFlow('apexonly.example.com', responder: function (\Illuminate\Http\Client\Request $request) use (&$postDomains) {
            if ($request->method() === 'POST'
                && str_contains($request->url(), '/v10/projects/')
                && str_contains($request->url(), '/domains')
                && ! str_contains($request->url(), '/verify')) {
                $postDomains++;
                $body = $request->data();
                $name = $body['name'] ?? null;

                if ($name === 'apexonly.example.com') {
                    $this->assertArrayNotHasKey('redirect', $body);

                    return Http::response(['name' => 'apexonly.example.com', 'verified' => false], 200);
                }
            }

            return null;
        });

        $this->postJson('/api/settings/domain', ['custom_name' => 'apexonly.example.com'])
            ->assertCreated();

        $this->assertSame(1, $postDomains);
    }

    public function test_store_preflight_rejects_when_project_is_at_capacity(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->actingTenant();

        $filled = [];
        for ($i = 0; $i < 50; $i++) {
            $filled[] = 'filled-' . $i . '.example.com';
        }

        $this->fakePreflightInventory($filled);

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'over-cap.example.com',
        ]);

        $response->assertStatus(503)
            ->assertJsonPath('code', 'HOSTING_CAPACITY_REACHED');
        $this->assertDatabaseMissing('api_domains_settings', [
            'custom_name' => 'over-cap.example.com',
        ]);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST'
            && str_contains($request->url(), '/domains'));
    }

    public function test_store_preflight_allows_when_one_slot_remains(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $filled = [];
        for ($i = 0; $i < 49; $i++) {
            $filled[] = 'slot-' . $i . '.example.com';
        }

        $this->fakeVercelStoreFlow('one-slot.example.com', inventoryDomains: $filled, responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/domains') && ! str_contains($request->url(), '/verify')) {
                $name = $request->data()['name'] ?? '';

                return Http::response(['name' => $name, 'verified' => false], 200);
            }

            return null;
        });

        $this->postJson('/api/settings/domain', ['custom_name' => 'one-slot.example.com'])
            ->assertCreated();

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'one-slot.example.com',
        ]);
    }

    public function test_store_preflight_allows_when_two_slots_remain(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $filled = [];
        for ($i = 0; $i < 48; $i++) {
            $filled[] = 'slot-' . $i . '.example.com';
        }

        $this->fakeVercelStoreFlow('two-slot.example.com', inventoryDomains: $filled, responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/domains') && ! str_contains($request->url(), '/verify')) {
                $name = $request->data()['name'] ?? '';

                return Http::response(['name' => $name, 'verified' => false], 200);
            }

            return null;
        });

        $this->postJson('/api/settings/domain', ['custom_name' => 'two-slot.example.com'])
            ->assertCreated();

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'two-slot.example.com',
        ]);
    }

    public function test_store_preflight_rejects_when_inventory_is_lower_bound(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->actingTenant();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            if ($request->method() === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response(['id' => 'prj_test', 'accountId' => 'team_test'], 200);
            }
            if ($request->method() === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => 'partial.example.com', 'verified' => true]],
                    'pagination' => ['count' => 1, 'next' => 9999999999999],
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->postJson('/api/settings/domain', ['custom_name' => 'lower-bound.example.com'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'HOSTING_INVENTORY_UNAVAILABLE');

        $this->assertDatabaseMissing('api_domains_settings', [
            'custom_name' => 'lower-bound.example.com',
        ]);
    }

    public function test_store_adopted_apex_does_not_compensate_by_deleting_existing_entry(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $apex = 'adopted-fail.example.com';

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($apex) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response(['id' => 'prj_test', 'accountId' => 'team_test'], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [['name' => $apex, 'verified' => false]],
                    'pagination' => ['count' => 1, 'next' => null],
                ], 200);
            }

            if ($method === 'POST' && str_contains($url, '/domains') && ! str_contains($url, '/verify')) {
                $postedName = strtolower((string) ($request->data()['name'] ?? ''));

                if ($postedName === $apex) {
                    return Http::response(['error' => ['code' => 'domain_already_in_use']], 409);
                }

                return Http::response(['error' => ['code' => 'domain_already_in_use']], 409);
            }

            if ($method === 'GET' && str_contains($url, '/domains/')) {
                if (str_contains($url, '/domains/' . rawurlencode($apex))) {
                    return Http::response(['name' => $apex, 'verified' => false], 200);
                }
            }

            if ($method === 'GET' && str_contains($url, '/v6/domains/')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if (str_contains($url, '/verify') && $method === 'POST') {
                return Http::response(['name' => $apex, 'verified' => false], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->postJson('/api/settings/domain', ['custom_name' => $apex])
            ->assertCreated();

        Http::assertNotSent(fn ($request) => $request->method() === 'DELETE');
        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => $apex,
        ]);
    }

    public function test_store_connection_timeout_preserves_recoverable_pending_row(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $apex = 'timeout.example.com';

        $this->fakeVercelStoreFlow($apex, responder: function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'POST' && str_contains($request->url(), '/domains') && ! str_contains($request->url(), '/verify')) {
                throw new ConnectionException('Connection timed out');
            }

            return null;
        });

        $response = $this->postJson('/api/settings/domain', ['custom_name' => $apex]);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'pending');

        $domain = ApiDomainSetting::where('user_id', $tenant->id)
            ->where('custom_name', $apex)
            ->firstOrFail();

        $this->assertSame('uncertain', $domain->dns_records['provisioning']['state']
            ?? $domain->dns_records['last_check']['provisioning']['state']
            ?? null);
    }

    public function test_tenant_cannot_delete_domain_via_api(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $user = $this->actingTenant();

        $primary = ApiDomainSetting::create([
            'user_id' => $user->id,
            'custom_name' => 'primary-keep.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake([
            'api.vercel.com/*' => Http::response(['error' => ['message' => 'boom']], 500),
        ]);

        $response = $this->deleteJson('/api/settings/domain/' . $primary->id);
        $this->assertNotEquals(200, $response->status());
        $this->assertNotTrue($response->json('success'));

        $primary->refresh();
        $this->assertTrue((bool) $primary->primary);
        $this->assertDatabaseHas('api_domains_settings', ['id' => $primary->id, 'primary' => 1]);
        Http::assertNothingSent();
    }

    public function test_store_invalidates_vercel_inventory_cache(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.max_domains_per_tenant' => 100]);
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $cache = app(VercelDomainCache::class);

        Http::fake([
            'api.vercel.com/*' => Http::response([
                'domains' => [],
                'pagination' => ['next' => null],
            ], 200),
        ]);

        $cache->fresh();
        $this->assertNotNull(Cache::get($cache->inventoryKey()));

        $this->fakeVercelStoreFlow('cache-bust.example.com');

        $this->postJson('/api/settings/domain', ['custom_name' => 'cache-bust.example.com'])
            ->assertCreated();

        $this->assertNull(Cache::get($cache->inventoryKey()));
    }

    public function test_store_rejects_duplicate_domain_for_second_tenant_under_quota_lock(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $owner = User::factory()->tenant()->create([
            'email' => 'quota-owner-' . uniqid('', true) . '@example.com',
        ]);

        ApiDomainSetting::create([
            'user_id' => $owner->id,
            'custom_name' => 'quota-race.example.com',
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $this->actingTenant();
        Http::fake();

        $this->postJson('/api/settings/domain', ['custom_name' => 'quota-race.example.com'])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Domain already in use');
    }

    public function test_sync_preserves_active_status_on_provider_unknown(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.health_failure_threshold' => 3]);

        $tenant = User::factory()->tenant()->create([
            'email' => 'provider-unknown-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'provider-unknown.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() === 'GET' && str_contains($request->url(), '/v9/projects/') && str_contains($request->url(), '/domains') && ! str_contains($request->url(), '/domains/')) {
                return Http::response([
                    'domains' => [['name' => 'provider-unknown.example.com', 'verified' => true]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if (str_contains($request->url(), '/verify')) {
                return Http::response(['error' => ['message' => 'rate limited']], 429);
            }

            return Http::response(['error' => ['message' => 'upstream']], 503);
        });

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertSame('provider_error', $domain->health()['code']);
    }

    public function test_sync_failure_threshold_resets_after_success(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.health_failure_threshold' => 2]);
        $this->mockNameservers(true);

        $tenant = User::factory()->tenant()->create([
            'email' => 'threshold-reset-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'threshold-reset.example.com',
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'consecutive_failures' => 1,
                    'first_failure_at' => now()->subHour()->toIso8601String(),
                    'auto_attach_custom_domain' => true,
                    'nameserver_check_enabled' => true,
                ],
            ],
        ]);

        $this->fakeVercelSyncEndpoints(['threshold-reset.example.com'], true);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $this->assertSame('active', $domain->status);
        $this->assertSame(0, $domain->dns_records['last_check']['consecutive_failures'] ?? -1);
    }

    public function test_sync_command_recovers_external_dns_row_after_dns_is_corrected(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        config(['services.vercel.check_nameservers' => false]);
        $tenant = User::factory()->tenant()->create([
            'email' => 'external-dns-recover-' . uniqid('', true) . '@example.com',
        ]);

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'external-recover.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'failed',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'health_code' => 'dns_misconfigured',
                    'apex_attached' => true,
                    'apex_verified' => false,
                    'apex_matches_recommended' => false,
                    'www_matches_recommended' => false,
                    'consecutive_failures' => 2,
                    'first_failure_at' => now()->subHours(2)->toIso8601String(),
                ],
            ],
        ]);

        $this->fakeVercelSyncEndpoints(['external-recover.example.com', 'www.external-recover.example.com'], true);
        $this->mockDnsRecords(true, true);

        Artisan::call('domains:sync-vercel-status');

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? [];
        $this->assertSame('active', $domain->status);
        $this->assertSame(ApiDomainSetting::DNS_MODE_EXTERNAL_DNS, $domain->dns_mode);
        $this->assertSame(ApiDomainSetting::DNS_MODE_EXTERNAL_DNS, $lastCheck['dns_mode'] ?? null);
        $this->assertSame('linked', $lastCheck['health_code'] ?? null);
        $this->assertSame(0, $lastCheck['consecutive_failures'] ?? -1);
    }

    public function test_store_defaults_dns_mode_to_vercel_ns_when_omitted(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();
        $this->fakeVercelStoreFlow('default-mode.example.com');

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'default-mode.example.com',
        ]);

        $response->assertCreated()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('data.dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('data.www.status', 'not_enabled');

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'default-mode.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
        ]);
    }

    public function test_store_persists_explicit_vercel_ns_and_external_dns_modes(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        $this->fakeVercelStoreFlow('ns-mode.example.com');
        $nsResponse = $this->postJson('/api/settings/domain', [
            'custom_name' => 'ns-mode.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
        ]);
        $nsResponse->assertCreated()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('data.dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('dnsInstructions.mode', 'nameservers');

        $this->fakeVercelStoreFlow('ext-mode.example.com');
        $extResponse = $this->postJson('/api/settings/domain', [
            'custom_name' => 'ext-mode.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
        ]);
        $extResponse->assertCreated()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('data.dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('dnsInstructions.mode', 'records')
            ->assertJsonPath('dnsInstructions.apex.records.0.type', 'A')
            ->assertJsonPath('dnsInstructions.apex.records.0.value', '76.76.21.21')
            ->assertJsonCount(1, 'dnsInstructions.apex.records')
            ->assertJsonPath('dnsInstructions.www.records.0.type', 'CNAME')
            ->assertJsonCount(1, 'dnsInstructions.www.records');

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'ns-mode.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
        ]);
        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'ext-mode.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
        ]);
    }

    public function test_store_rejects_invalid_dns_mode_values(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->actingTenant();

        foreach ([null, 'unknown', ['vercel_ns']] as $invalid) {
            $response = $this->postJson('/api/settings/domain', [
                'custom_name' => 'invalid-mode.example.com',
                'dns_mode' => $invalid,
            ]);

            $response->assertStatus(422)
                ->assertJsonPath('success', false);
        }
    }

    public function test_store_without_auto_attach_preserves_selected_dns_mode(): void
    {
        $this->skipIfMissingSchema();
        config([
            'services.vercel.token' => null,
            'services.vercel.project_id' => null,
            'services.vercel.auto_attach_custom_domain' => false,
            'services.vercel.check_nameservers' => true,
            'services.vercel.nameservers' => [
                'ns1.vercel-dns.com',
                'ns2.vercel-dns.com',
            ],
            'services.vercel.external_dns' => [
                'apex_record_type' => 'A',
                'apex_record_host' => '@',
                'apex_record_value' => '76.76.21.21',
                'www_record_type' => 'CNAME',
                'www_record_host' => 'www',
                'www_record_value' => 'cname.vercel-dns.com',
            ],
        ]);
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();
        Http::fake();

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'local-ext.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
        ]);

        $response->assertCreated()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('data.dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS);

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'local-ext.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
        ]);
    }

    public function test_store_external_dns_attaches_project_domain_only(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $this->actingTenant();
        $this->fakeVercelStoreFlow('project-only.example.com');

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'project-only.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
        ]);

        $response->assertCreated()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS);

        $recorded = Http::recorded();
        $accountDomainPosts = collect($recorded)->filter(function (array $pair): bool {
            /** @var \Illuminate\Http\Client\Request $request */
            $request = $pair[0];

            return $request->method() === 'POST'
                && str_contains($request->url(), '/v7/domains')
                && ! str_contains($request->url(), '/projects/');
        });
        $zonePatches = collect($recorded)->filter(function (array $pair): bool {
            /** @var \Illuminate\Http\Client\Request $request */
            $request = $pair[0];

            return $request->method() === 'PATCH'
                && preg_match('#/v3/domains/#', $request->url()) === 1;
        });
        $projectAttaches = collect($recorded)->filter(function (array $pair): bool {
            /** @var \Illuminate\Http\Client\Request $request */
            $request = $pair[0];

            return $request->method() === 'POST'
                && str_contains($request->url(), '/v10/projects/')
                && str_contains($request->url(), '/domains')
                && ! str_contains($request->url(), '/verify');
        });

        $this->assertCount(0, $accountDomainPosts);
        $this->assertCount(0, $zonePatches);
        $this->assertGreaterThanOrEqual(1, $projectAttaches->count());
    }

    public function test_store_provider_error_pending_retains_dns_mode(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(false);
        $tenant = $this->actingTenant();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [],
                    'pagination' => ['count' => 0, 'next' => null],
                ], 200);
            }

            if ($method === 'POST' && str_contains($url, '/v10/projects/') && str_contains($url, '/domains')) {
                throw new ConnectionException('Connection timed out');
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $response = $this->postJson('/api/settings/domain', [
            'custom_name' => 'timeout-ext.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
        ]);

        $response->assertCreated()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS);

        $this->assertDatabaseHas('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'timeout-ext.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
        ]);
    }

    public function test_external_instructions_use_config_fallback_and_keep_ownership_separate(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'fallback-ext.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'ownership_challenge' => [
                        'type' => 'txt',
                        'domain' => '_vercel',
                        'value' => 'vc-domain-verify=abc',
                    ],
                    'recommended_ipv4' => [],
                    'recommended_cname' => [],
                ],
            ],
        ]);

        $response = $this->getJson('/api/settings/domain/' . $domain->id);

        $response->assertOk()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('dnsInstructions.mode', 'records')
            ->assertJsonCount(1, 'dnsInstructions.apex.records')
            ->assertJsonPath('dnsInstructions.apex.records.0.type', 'A')
            ->assertJsonPath('dnsInstructions.apex.records.0.value', '76.76.21.21')
            ->assertJsonCount(1, 'dnsInstructions.www.records')
            ->assertJsonPath('dnsInstructions.www.records.0.type', 'CNAME')
            ->assertJsonPath('dnsInstructions.www.records.0.value', 'cname.vercel-dns.com')
            ->assertJsonMissingPath('dnsInstructions.nameservers')
            ->assertJsonMissingPath('dnsInstructions.message')
            ->assertJsonPath('dnsInstructions.ownership.required', true)
            ->assertJsonPath('dnsInstructions.ownership.record.type', 'TXT')
            ->assertJsonPath('dnsInstructions.ownership.record.name', '_vercel')
            ->assertJsonPath('dnsInstructions.ownership.record.value', 'vc-domain-verify=abc')
            ->assertJsonPath('www.hostname', 'www.fallback-ext.example.com')
            ->assertJsonPath('www.status', 'not_enabled');

        $this->assertSame(
            ['mode', 'apex', 'www', 'ownership'],
            array_keys($response->json('dnsInstructions'))
        );
        $this->assertSame(
            [
                'mode' => 'records',
                'apex' => [
                    'host' => '@',
                    'records' => [
                        ['type' => 'A', 'name' => '@', 'value' => '76.76.21.21'],
                    ],
                ],
                'www' => [
                    'host' => 'www',
                    'records' => [
                        ['type' => 'CNAME', 'name' => 'www', 'value' => 'cname.vercel-dns.com'],
                    ],
                ],
                'ownership' => [
                    'required' => true,
                    'record' => [
                        'type' => 'TXT',
                        'name' => '_vercel',
                        'value' => 'vc-domain-verify=abc',
                    ],
                ],
            ],
            $response->json('dnsInstructions')
        );
    }

    public function test_external_instructions_ignore_stale_last_check_recommended_records(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config([
            'services.vercel.external_dns' => [
                'apex_record_type' => 'A',
                'apex_record_host' => '@',
                'apex_record_value' => '203.0.113.50',
                'www_record_type' => 'CNAME',
                'www_record_host' => 'www',
                'www_record_value' => 'platform-standard.cname.test',
            ],
        ]);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'stale-recs.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'recommended_ipv4' => ['198.51.100.99'],
                    'recommended_cname' => ['stale-override.cname.test'],
                    'ownership_challenge' => [
                        'type' => 'txt',
                        'domain' => '_vercel.stale-recs.example.com',
                        'value' => 'vc-domain-verify=stale-recs',
                    ],
                ],
            ],
        ]);

        $standard = ApiDomainSetting::externalDnsInstructions();

        $response = $this->getJson('/api/settings/domain/' . $domain->id);

        $response->assertOk()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('dnsInstructions.mode', 'records')
            ->assertJsonPath('dnsInstructions.apex.records.0.type', $standard['apex_record_type'])
            ->assertJsonPath('dnsInstructions.apex.records.0.value', $standard['apex_record_value'])
            ->assertJsonPath('dnsInstructions.www.records.0.type', $standard['www_record_type'])
            ->assertJsonPath('dnsInstructions.www.records.0.value', $standard['www_record_value'])
            ->assertJsonPath('dnsInstructions.ownership.required', true)
            ->assertJsonPath('dnsInstructions.ownership.record.name', '_vercel.stale-recs.example.com')
            ->assertJsonPath('dnsInstructions.ownership.record.value', 'vc-domain-verify=stale-recs');

        $this->assertSame('203.0.113.50', $response->json('dnsInstructions.apex.records.0.value'));
        $this->assertSame('platform-standard.cname.test', $response->json('dnsInstructions.www.records.0.value'));
        $this->assertNotSame('198.51.100.99', $response->json('dnsInstructions.apex.records.0.value'));
        $this->assertNotSame('stale-override.cname.test', $response->json('dnsInstructions.www.records.0.value'));
    }

    public function test_external_instructions_omit_blank_or_malformed_ownership(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'blank-own.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'ownership_challenge' => [
                        'type' => 'txt',
                        'domain' => '',
                        'value' => '   ',
                    ],
                ],
            ],
        ]);

        $response = $this->getJson('/api/settings/domain/' . $domain->id);
        $response->assertOk()
            ->assertJsonMissingPath('dnsInstructions.ownership');

        $this->assertSame(
            [
                'mode' => 'records',
                'apex' => [
                    'host' => '@',
                    'records' => [
                        ['type' => 'A', 'name' => '@', 'value' => '76.76.21.21'],
                    ],
                ],
                'www' => [
                    'host' => 'www',
                    'records' => [
                        ['type' => 'CNAME', 'name' => 'www', 'value' => 'cname.vercel-dns.com'],
                    ],
                ],
            ],
            $response->json('dnsInstructions')
        );
    }

    public function test_index_includes_available_dns_modes_and_www_payload(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $tenant = $this->actingTenant();

        ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'listed.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'pending',
            'primary' => true,
            'ssl' => false,
            'added_date' => now(),
        ]);

        $response = $this->getJson('/api/settings/domain');

        $response->assertOk()
            ->assertJsonPath('availableDnsModes.0.value', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('availableDnsModes.0.label', __('domain_dns.form_mode_vercel_ns'))
            ->assertJsonPath('availableDnsModes.0.instructions.mode', 'nameservers')
            ->assertJsonPath('availableDnsModes.1.value', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('availableDnsModes.1.label', __('domain_dns.form_mode_external_dns'))
            ->assertJsonPath('availableDnsModes.1.instructions.mode', 'records')
            ->assertJsonPath('availableDnsModes.1.instructions.apex.type', 'A')
            ->assertJsonPath('availableDnsModes.1.instructions.www.type', 'CNAME')
            ->assertJsonPath('domains.0.www.hostname', 'www.listed.example.com')
            ->assertJsonPath('domains.0.www.status', 'unknown');
    }

    public function test_enable_www_for_own_domain_is_idempotent_and_rejects_wrong_redirect(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-enable.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'www_present' => false,
                    'www_redirect_correct' => false,
                ],
            ],
        ]);

        $attached = [
            'www-enable.example.com' => [
                'name' => 'www-enable.example.com',
                'verified' => true,
            ],
        ];

        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$attached) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => array_values($attached),
                    'pagination' => ['count' => count($attached), 'next' => null],
                ], 200);
            }

            if ($method === 'POST' && str_contains($url, '/v10/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/verify')) {
                $name = strtolower((string) ($request->data()['name'] ?? ''));
                $attached[$name] = [
                    'name' => $name,
                    'verified' => true,
                    'redirect' => (string) ($request->data()['redirect'] ?? ''),
                    'redirectStatusCode' => (int) ($request->data()['redirectStatusCode'] ?? 301),
                ];

                return Http::response($attached[$name], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v6/domains/') && str_contains($url, '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if ($method === 'GET' && preg_match('#/v(?:5|7)/domains/([^/?]+)#', $url, $matches) && ! str_contains($url, '/config')) {
                return Http::response([
                    'name' => strtolower(rawurldecode($matches[1])),
                    'zone' => false,
                    'verified' => true,
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v8/certs')) {
                return Http::response([
                    'certs' => [[
                        'id' => 'cert_www',
                        'cns' => ['www-enable.example.com', 'www.www-enable.example.com'],
                        'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                        'autoRenew' => true,
                    ]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/domains/')) {
                $name = 'www-enable.example.com';
                if (preg_match('#/domains/([^/?]+)#', $url, $matches)) {
                    $name = strtolower(rawurldecode($matches[1]));
                }

                return Http::response([
                    'name' => $name,
                    'verified' => true,
                    'verification' => [],
                    'redirect' => $attached[$name]['redirect'] ?? null,
                    'redirectStatusCode' => $attached[$name]['redirectStatusCode'] ?? null,
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $enabled = $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id]);
        $enabled->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.hostname', 'www.www-enable.example.com')
            ->assertJsonPath('data.redirectTarget', 'www-enable.example.com')
            ->assertJsonPath('data.redirectStatusCode', 301)
            ->assertJsonPath('data.alreadyEnabled', false)
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_EXTERNAL_DNS)
            ->assertJsonPath('dnsInstructions.mode', 'records');

        $again = $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id]);
        $again->assertOk()
            ->assertJsonPath('data.alreadyEnabled', true);

        $attached['www.www-enable.example.com'] = [
            'name' => 'www.www-enable.example.com',
            'verified' => true,
            'redirect' => 'other.example.com',
            'redirectStatusCode' => 301,
        ];
        Cache::flush();

        $mismatch = $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id]);
        $mismatch->assertStatus(409)
            ->assertJsonPath('code', 'WWW_REDIRECT_MISMATCH');
    }

    public function test_enable_www_rejects_other_tenant_and_missing_apex(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();
        $other = User::factory()->tenant()->create([
            'email' => 'other-domain-' . uniqid('', true) . '@example.com',
        ]);

        $foreign = ApiDomainSetting::create([
            'user_id' => $other->id,
            'custom_name' => 'foreign.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $this->postJson('/api/settings/domain/www/enable', ['id' => $foreign->id])
            ->assertNotFound();

        $missingApex = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'missing-apex.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'pending',
            'primary' => false,
            'ssl' => false,
            'added_date' => now(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [],
                    'pagination' => ['count' => 0, 'next' => null],
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->postJson('/api/settings/domain/www/enable', ['id' => $missingApex->id])
            ->assertStatus(422)
            ->assertJsonPath('code', 'APEX_NOT_ATTACHED');
    }

    public function test_enable_www_fails_closed_on_unreliable_inventory(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'unreliable.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [[
                        'name' => 'unreliable.example.com',
                        'verified' => true,
                    ]],
                    'pagination' => ['count' => 1, 'next' => 'cursor'],
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id])
            ->assertStatus(503)
            ->assertJsonPath('code', 'HOSTING_PROVIDER_UNAVAILABLE');
    }

    public function test_enable_www_returns_capacity_error_when_no_free_slot(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        config(['services.vercel.max_project_domains' => 1]);
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'capacity.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => [[
                        'name' => 'capacity.example.com',
                        'verified' => true,
                    ]],
                    'pagination' => ['count' => 1, 'next' => null],
                ], 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });

        $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id])
            ->assertStatus(503)
            ->assertJsonPath('code', 'HOSTING_CAPACITY_REACHED');
    }

    public function test_enable_www_requires_auth_and_valid_id(): void
    {
        $this->skipIfMissingSchema();

        $this->postJson('/api/settings/domain/www/enable', ['id' => 1])
            ->assertUnauthorized();

        $this->configureVercel();
        $this->actingTenant();

        $this->postJson('/api/settings/domain/www/enable', [])
            ->assertStatus(422);
    }

    public function test_enable_www_emits_activity_before_and_after_on_new_enable(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-activity.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        Event::fake([TenantActivityOccurred::class]);
        $this->fakeWwwEnableInventory('www-activity.example.com');

        $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id])
            ->assertOk()
            ->assertJsonPath('data.alreadyEnabled', false);

        Event::assertDispatched(TenantActivityOccurred::class, function (TenantActivityOccurred $event) use ($domain, $tenant) {
            return $event->action === 'domain.www_enabled'
                && (int) $event->tenantId === (int) $tenant->id
                && (int) $event->targetId === (int) $domain->id
                && is_array($event->oldValues)
                && ($event->oldValues['custom_name'] ?? null) === 'www-activity.example.com'
                && ($event->oldValues['status'] ?? null) === 'active'
                && is_array($event->newValues)
                && ($event->newValues['custom_name'] ?? null) === 'www-activity.example.com'
                && ($event->newValues['www'] ?? null) === 'www.www-activity.example.com';
        });
    }

    public function test_enable_www_invalidates_cache_and_refreshes_inventory(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-cache.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $cache = app(VercelDomainCache::class);
        $this->fakeWwwEnableInventory('www-cache.example.com');
        $cache->fresh();
        $this->assertNotNull(Cache::get($cache->inventoryKey()));
        Cache::put('admin.domain_health_counts', ['x' => 1], 300);

        $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id])
            ->assertOk();

        // Mutation invalidates, then refreshDomainState()->fresh() repopulates inventory.
        $this->assertNotNull(Cache::get($cache->inventoryKey()));
        $this->assertNull(Cache::get('admin.domain_health_counts'));
        $snapshot = Cache::get($cache->inventoryKey());
        $this->assertContains('www.www-cache.example.com', $snapshot['names'] ?? []);
    }

    public function test_enable_www_persists_synced_www_state(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-sync.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
            'dns_records' => [
                'last_check' => [
                    'dns_mode' => ApiDomainSetting::DNS_MODE_EXTERNAL_DNS,
                    'www_present' => false,
                    'www_redirect_correct' => false,
                ],
            ],
        ]);

        $this->fakeWwwEnableInventory('www-sync.example.com');

        $response = $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id]);
        $response->assertOk()
            ->assertJsonPath('data.www.present', true)
            ->assertJsonPath('data.www.redirectCorrect', true)
            ->assertJsonPath('data.redirectStatusCode', 301);

        $domain->refresh();
        $lastCheck = $domain->dns_records['last_check'] ?? [];
        $this->assertTrue((bool) ($lastCheck['www_present'] ?? false));
        $this->assertTrue((bool) ($lastCheck['www_redirect_correct'] ?? false));
    }

    public function test_enable_www_returns_nameserver_instructions_for_vercel_ns(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-ns.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $this->fakeWwwEnableInventory('www-ns.example.com');

        $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id])
            ->assertOk()
            ->assertJsonPath('dnsMode', ApiDomainSetting::DNS_MODE_VERCEL_NS)
            ->assertJsonPath('dnsInstructions.mode', 'nameservers')
            ->assertJsonPath('dnsInstructions.nameservers.0', 'ns1.vercel-dns.com');
    }

    public function test_enable_www_permission_denied_for_employee_without_settings_update(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();

        $tenant = User::factory()->tenant()->create([
            'email' => 'www-perm-tenant-' . uniqid('', true) . '@example.com',
        ]);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'email' => 'www-perm-emp-' . uniqid('', true) . '@example.com',
        ]);

        Sanctum::actingAs($employee);

        $this->postJson('/api/settings/domain/www/enable', ['id' => 1])
            ->assertForbidden();
    }

    public function test_enable_www_is_throttled(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->actingTenant();

        Http::fake();

        $sawTooMany = false;
        for ($i = 0; $i < 11; $i++) {
            $status = $this->postJson('/api/settings/domain/www/enable', ['id' => 999999])->status();
            if ($status === 429) {
                $sawTooMany = true;
                break;
            }
        }

        $this->assertTrue($sawTooMany, 'Expected throttle:10,1 to return 429 after repeated www/enable calls');
    }

    public function test_enable_www_posts_exact_301_payload_to_vercel(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-301.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $this->fakeWwwEnableInventory('www-301.example.com');

        $response = $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id]);
        $response->assertOk()
            ->assertJsonPath('data.redirectTarget', 'www-301.example.com')
            ->assertJsonPath('data.redirectStatusCode', 301)
            ->assertJsonPath('data.hostname', 'www.www-301.example.com');

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            if ($request->method() !== 'POST' || ! str_contains($request->url(), '/v10/projects/')) {
                return false;
            }
            if (! str_contains($request->url(), '/domains') || str_contains($request->url(), '/verify')) {
                return false;
            }

            $data = $request->data();

            return ($data['name'] ?? null) === 'www.www-301.example.com'
                && ($data['redirect'] ?? null) === 'www-301.example.com'
                && (int) ($data['redirectStatusCode'] ?? 0) === 301;
        });
    }

    public function test_enable_www_conflicts_on_308_and_missing_redirect_status(): void
    {
        $this->skipIfMissingSchema();
        $this->configureVercel();
        $this->mockNameservers(true);
        $tenant = $this->actingTenant();

        $domain = ApiDomainSetting::create([
            'user_id' => $tenant->id,
            'custom_name' => 'www-conflict.example.com',
            'dns_mode' => ApiDomainSetting::DNS_MODE_VERCEL_NS,
            'status' => 'active',
            'primary' => true,
            'ssl' => true,
            'added_date' => now(),
        ]);

        $cases = [
            ['redirectStatusCode' => 308],
            ['redirectStatusCode' => null],
            [], // missing/unknown status key
        ];

        foreach ($cases as $statusFields) {
            Cache::flush();
            $this->fakeWwwEnableInventory(
                'www-conflict.example.com',
                preAttachedWww: array_merge([
                    'name' => 'www.www-conflict.example.com',
                    'verified' => true,
                    'redirect' => 'www-conflict.example.com',
                ], $statusFields)
            );

            $response = $this->postJson('/api/settings/domain/www/enable', ['id' => $domain->id]);
            $response->assertStatus(409)
                ->assertJsonPath('code', 'WWW_REDIRECT_MISMATCH')
                ->assertJsonMissingPath('data.redirectStatusCode');

            Http::assertNotSent(function (\Illuminate\Http\Client\Request $request): bool {
                return in_array($request->method(), ['POST', 'PATCH', 'PUT', 'DELETE'], true);
            });
        }
    }

    public function test_domain_openapi_docs_are_durable_via_generator_source(): void
    {
        $generator = file_get_contents(app_path('Console/Commands/GenerateSwaggerApiPathsCommand.php'));
        $this->assertNotFalse($generator);
        $this->assertStringContainsString('availableDnsModes', $generator);
        $this->assertStringContainsString('POST /settings/domain/www/enable', $generator);
        $this->assertStringContainsString('optional dns_mode defaults to vercel_ns', $generator);
        $this->assertStringContainsString('redirectStatusCode', $generator);
        $this->assertStringContainsString('Existing www redirect mismatch', $generator);
        $this->assertDoesNotMatchRegularExpression(
            "/'(GET|POST|PATCH|PUT|DELETE) \\/settings\\/domain\\/(request-ssl|ssl-status)'/",
            $generator
        );

        $doc = file_get_contents(app_path('Http/Controllers/Api/GeneratedApiPathsDoc.php'));
        $this->assertNotFalse($doc);
        $this->assertStringContainsString('path="/settings/domain/www/enable"', $doc);
        $this->assertStringContainsString('availableDnsModes', $doc);
        $this->assertStringContainsString('dns_mode', $doc);
        $this->assertStringContainsString('redirectStatusCode', $doc);
        $this->assertStringNotContainsString('path="/settings/domain/request-ssl"', $doc);
        $this->assertStringNotContainsString('path="/settings/domain/ssl-status"', $doc);
        $this->assertStringNotContainsString('operationId="delete_settings_domain_id', $doc);
        $this->assertStringContainsString(
            "'App\\Http\\Controllers\\Api\\DomainSettingsController@enableWww'",
            file_get_contents(config_path('swagger_request_map.php')) ?: ''
        );
        $this->assertStringContainsString(
            'in:vercel_ns,external_dns',
            file_get_contents(config_path('swagger_request_map.php')) ?: ''
        );
    }

    /**
     * @param  array{name: string, verified?: bool, redirect?: string|null, redirectStatusCode?: int|null}|null  $preAttachedWww
     */
    private function fakeWwwEnableInventory(string $apex, ?array $preAttachedWww = null): void
    {
        $attached = [
            $apex => [
                'name' => $apex,
                'verified' => true,
            ],
        ];

        if ($preAttachedWww !== null) {
            $name = (string) $preAttachedWww['name'];
            $entry = [
                'name' => $name,
                'verified' => (bool) ($preAttachedWww['verified'] ?? true),
                'redirect' => $preAttachedWww['redirect'] ?? null,
            ];
            if (array_key_exists('redirectStatusCode', $preAttachedWww)) {
                $entry['redirectStatusCode'] = $preAttachedWww['redirectStatusCode'];
            }
            $attached[$name] = $entry;
        }

        Http::fake(function (\Illuminate\Http\Client\Request $request) use (&$attached, $apex) {
            $url = $request->url();
            $method = $request->method();

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test') && ! str_contains($url, '/domains')) {
                return Http::response([
                    'id' => 'prj_test',
                    'accountId' => 'team_test',
                    'name' => 'test-project',
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v9/projects/prj_test/domains') && ! str_contains($url, '/domains/')) {
                return Http::response([
                    'domains' => array_values($attached),
                    'pagination' => ['count' => count($attached), 'next' => null],
                ], 200);
            }

            if ($method === 'POST' && str_contains($url, '/v10/projects/') && str_contains($url, '/domains') && ! str_contains($url, '/verify')) {
                $name = strtolower((string) ($request->data()['name'] ?? ''));
                $attached[$name] = [
                    'name' => $name,
                    'verified' => true,
                    'redirect' => (string) ($request->data()['redirect'] ?? ''),
                    'redirectStatusCode' => (int) ($request->data()['redirectStatusCode'] ?? 301),
                ];

                return Http::response($attached[$name], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v6/domains/') && str_contains($url, '/config')) {
                return Http::response(['misconfigured' => false], 200);
            }

            if ($method === 'GET' && preg_match('#/v(?:5|7)/domains/([^/?]+)#', $url, $matches) && ! str_contains($url, '/config')) {
                return Http::response([
                    'name' => strtolower(rawurldecode($matches[1])),
                    'zone' => false,
                    'verified' => true,
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/v8/certs')) {
                return Http::response([
                    'certs' => [[
                        'id' => 'cert_www',
                        'cns' => [$apex, 'www.' . $apex],
                        'expiresAt' => ((int) (microtime(true) * 1000)) + (90 * 86400 * 1000),
                        'autoRenew' => true,
                    ]],
                    'pagination' => ['next' => null],
                ], 200);
            }

            if ($method === 'GET' && str_contains($url, '/domains/')) {
                $name = $apex;
                if (preg_match('#/domains/([^/?]+)#', $url, $matches)) {
                    $name = strtolower(rawurldecode($matches[1]));
                }

                $payload = [
                    'name' => $name,
                    'verified' => true,
                    'verification' => [],
                    'redirect' => $attached[$name]['redirect'] ?? null,
                ];
                if (array_key_exists($name, $attached) && array_key_exists('redirectStatusCode', $attached[$name])) {
                    $payload['redirectStatusCode'] = $attached[$name]['redirectStatusCode'];
                }

                return Http::response($payload, 200);
            }

            return Http::response(['error' => 'unexpected'], 500);
        });
    }
}
