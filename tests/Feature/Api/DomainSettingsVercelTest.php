<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Api\ApiDomainSetting;
use App\Models\User;
use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\VercelDomainCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
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
    private function fakeVercelSyncEndpoints(array $domainNames, bool $verified = true): void
    {
        $inventoryDomains = array_map(
            static fn (string $name): array => [
                'name' => $name,
                'verified' => $verified,
            ],
            $domainNames
        );

        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domainNames, $verified, $inventoryDomains) {
            $url = $request->url();
            $method = $request->method();

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
                        return Http::response(['name' => $name, 'verified' => $verified], 200);
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
        Http::fake(function (\Illuminate\Http\Client\Request $request) use ($domainName, $verified, $inventoryDomains, $responder) {
            if ($responder !== null) {
                $custom = $responder($request);
                if ($custom !== null) {
                    return $custom;
                }
            }

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
                    'domains' => array_map(
                        static fn (string $name): array => ['name' => $name, 'verified' => $verified],
                        $inventoryDomains
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
                    $fetchedName = rawurldecode($matches[1]);

                    return Http::response(['name' => $fetchedName, 'verified' => $verified], 200);
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

        $response->assertStatus(502)
            ->assertJsonPath('message', 'Failed to register domain with hosting provider. Please try again later.')
            ->assertJsonMissing(['errors']);
        $body = $response->json();
        $this->assertStringNotContainsString('boom', json_encode($body));
        $this->assertDatabaseMissing('api_domains_settings', [
            'user_id' => $tenant->id,
            'custom_name' => 'fail.example.com',
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

        // The limit error must not trigger the "already attached?" lookup.
        Http::assertNotSent(fn ($request) => $request->method() === 'GET'
            && str_contains($request->url(), '/domains/khnas.sa.net'));
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

            if (str_contains($request->url(), '/verify') || str_contains($request->url(), '/domains/leaky.example.com')) {
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
            if ($request->method() === 'POST' && str_contains($request->url(), '/domains') && ! str_contains($request->url(), '/verify')) {
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

        $this->assertSame('uncertain', $domain->dns_records['provisioning']['state'] ?? null);
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
}
