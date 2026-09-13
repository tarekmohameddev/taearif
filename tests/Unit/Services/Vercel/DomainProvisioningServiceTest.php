<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Vercel;

use App\Services\Vercel\DnsNameserverChecker;
use App\Services\Vercel\DomainProvisioningService;
use App\Services\Vercel\VercelDomainCache;
use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainInventoryService;
use App\Services\Vercel\VercelMutationGuard;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class DomainProvisioningServiceTest extends TestCase
{
    private DomainProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureVercel();
        Cache::flush();
        $this->service = app(DomainProvisioningService::class);
    }

    /** @test */
    public function initial_mode_creates_account_domain_zone_and_attaches_apex_only(): void
    {
        $this->mockNameservers(ok: false);

        $state = [
            'account' => null,
            'project_domains' => [],
            'certificates' => [],
            'attach_posted' => false,
        ];

        Http::fake(function (Request $request) use (&$state) {
            return $this->respondVercel($request, $state) ?? Http::response(['error' => 'unmatched: '.$request->method().' '.$request->url()], 500);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_INITIAL);

        $this->assertSame('pending', $result['outcome']);
        $this->assertSame('ns_not_pointing', $result['health']);
        $this->assertFalse($result['ssl']);
        $this->assertTrue($result['retryable']);
        $this->assertSame('created', $result['provisioning']['account_domain']);
        $this->assertSame('created', $result['provisioning']['apex_attachment']);
        $this->assertSame('ns_not_pointing', $result['last_check']['health_code']);
        $this->assertTrue($state['attach_posted']);
        $this->assertFalse(collect(Http::recorded())->contains(fn (array $pair) => str_contains($pair[0]->url(), 'www.')));
    }

    /** @test */
    public function scheduled_repair_enables_zone_only_when_nameservers_match(): void
    {
        $this->mockNameservers(ok: true);

        $state = [
            'account' => $this->accountBody('example.com', zone: false, verified: true, serviceType: 'external'),
            'project_domains' => [['name' => 'example.com', 'verified' => true]],
            'zone_patch_count' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_SCHEDULED);

        $this->assertSame('active', $result['outcome']);
        $this->assertSame('apex_only', $result['health']);
        $this->assertTrue($result['ssl']);
        $this->assertSame('pre_existing', $result['provisioning']['account_domain']);
        $this->assertSame(1, $state['zone_patch_count']);
    }

    /** @test */
    public function scheduled_repair_skips_zone_enable_when_nameservers_do_not_match(): void
    {
        $this->mockNameservers(ok: false);

        $state = [
            'account' => $this->accountBody('example.com', zone: false, verified: true, serviceType: 'external'),
            'project_domains' => [['name' => 'example.com', 'verified' => true]],
            'zone_patch_count' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_SCHEDULED);

        $this->assertSame('pending', $result['outcome']);
        $this->assertSame('zone_disabled', $result['health']);
        $this->assertSame('pre_existing', $result['provisioning']['zone']);
        $this->assertSame(0, $state['zone_patch_count']);
    }

    /** @test */
    public function initial_mode_is_idempotent_when_resources_already_exist(): void
    {
        $this->mockNameservers(ok: true);

        $state = [
            'account' => $this->accountBody('example.com', zone: true, verified: true),
            'project_domain' => [
                'name' => 'example.com',
                'verified' => true,
                'verification' => [],
            ],
            'project_domains' => [['name' => 'example.com', 'verified' => true]],
            'mutation_requests' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            if (in_array($request->method(), ['POST', 'PATCH', 'DELETE'], true)
                && str_contains($request->url(), '/domains')) {
                $state['mutation_requests']++;
            }

            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_INITIAL);

        $this->assertSame('active', $result['outcome']);
        $this->assertSame('pre_existing', $result['provisioning']['account_domain']);
        $this->assertSame('pre_existing', $result['provisioning']['zone']);
        $this->assertSame('pre_existing', $result['provisioning']['apex_attachment']);
        $this->assertSame(0, $state['mutation_requests']);
    }

    /** @test */
    public function transport_ambiguity_marks_provisioning_uncertain_without_destructive_rollback(): void
    {
        $this->mockNameservers(ok: false);

        $state = [
            'account' => null,
            'project_domains' => [],
            'attach_status' => 429,
        ];

        Http::fake(function (Request $request) use (&$state) {
            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_INITIAL);

        $this->assertSame('pending', $result['outcome']);
        $this->assertTrue($result['retryable']);
        $this->assertSame('uncertain', $result['provisioning']['state']);

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'DELETE';
        });
    }

    /** @test */
    public function terminal_failure_after_created_attachment_rolls_back_project_domain_only(): void
    {
        $this->mockNameservers(ok: true);

        $state = [
            'account' => null,
            'project_domains' => [],
            'attach_verified' => true,
            'certificates' => [],
            'issue_certificate_status' => 502,
            'delete_requests' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            if ($request->method() === 'DELETE') {
                $state['delete_requests']++;
            }

            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_INITIAL);

        $this->assertSame('failed', $result['outcome']);
        $this->assertSame('provider_error', $result['health']);
        $this->assertSame(1, $state['delete_requests']);
    }

    /** @test */
    public function adopted_attachment_is_never_deleted_on_subsequent_failure(): void
    {
        $this->mockNameservers(ok: false);

        $state = [
            'account' => $this->accountBody('example.com', zone: true, verified: true),
            'project_domains' => [],
            'certificates' => [],
            'attach_status' => 409,
            'delete_requests' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            if ($request->method() === 'DELETE') {
                $state['delete_requests']++;
            }

            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_INITIAL);

        $this->assertSame('adopted', $result['provisioning']['apex_attachment']);
        $this->assertSame(0, $state['delete_requests']);
    }

    /** @test */
    public function post_mutation_outcome_uses_fresh_inventory_not_stale_cache(): void
    {
        $this->mockNameservers(ok: true);

        $state = [
            'account' => $this->accountBody('example.com', zone: false, verified: true, serviceType: 'external'),
            'project_domains' => [],
            'zone_patch_count' => 0,
            'inventory_fetch_count' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            if ($request->method() === 'GET'
                && str_contains($request->url(), '/v9/projects/prj_test/domains')
                && ! str_contains($request->url(), '/domains/')) {
                $state['inventory_fetch_count']++;

                $domains = $state['zone_patch_count'] > 0
                    ? [['name' => 'example.com', 'verified' => true]]
                    : ($state['project_domains'] ?? []);

                return Http::response([
                    'domains' => $domains,
                    'pagination' => ['next' => null],
                ], 200);
            }

            return $this->respondVercel($request, $state);
        });

        $cache = app(VercelDomainCache::class);
        Cache::put($cache->inventoryKey(), [
            'domains' => [],
            'metrics' => [],
            'fetched_at' => now()->subMinutes(10)->toIso8601String(),
        ], 300);

        $result = $this->service->run('example.com', DomainProvisioningService::MODE_SCHEDULED);

        $this->assertSame('active', $result['outcome']);
        $this->assertTrue($result['ssl']);
        $this->assertSame(1, $state['zone_patch_count']);
        $this->assertGreaterThanOrEqual(1, $state['inventory_fetch_count']);
    }

    /** @test */
    public function verification_only_performs_no_mutations(): void
    {
        $this->mockNameservers(ok: true);

        $state = [
            'account' => $this->accountBody('example.com', zone: true, verified: true),
            'project_domain' => [
                'name' => 'example.com',
                'verified' => true,
                'verification' => [],
            ],
            'project_domains' => [['name' => 'example.com', 'verified' => true]],
            'mutation_requests' => 0,
        ];

        Http::fake(function (Request $request) use (&$state) {
            if (in_array($request->method(), ['POST', 'PATCH', 'DELETE'], true)) {
                $state['mutation_requests']++;
            }

            return $this->respondVercel($request, $state);
        });

        $result = $this->service->run('example.com');

        $this->assertFalse($result['provisioning']['mutations_attempted']);
        $this->assertSame('active', $result['outcome']);
        $this->assertSame(0, $state['mutation_requests']);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function respondVercel(Request $request, array &$state)
    {
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
            $domains = $state['project_domains'] ?? [];
            if ($state['project_domain'] ?? null) {
                $exists = false;
                foreach ($domains as $domain) {
                    if (($domain['name'] ?? null) === ($state['project_domain']['name'] ?? null)) {
                        $exists = true;
                        break;
                    }
                }
                if (! $exists) {
                    $domains[] = $state['project_domain'];
                }
            }

            return Http::response([
                'domains' => $domains,
                'pagination' => ['next' => null],
            ], 200);
        }

        if ($method === 'GET' && str_contains($url, '/v5/domains/example.com')) {
            if (($state['account'] ?? null) === null) {
                return Http::response(['error' => 'not_found'], 404);
            }

            return Http::response(['domain' => $state['account']], 200);
        }

        if ($method === 'POST' && str_contains($url, '/v7/domains')) {
            $state['account'] = $this->accountBody('example.com', zone: true, verified: false);

            return Http::response(['domain' => $state['account']], 200);
        }

        if ($method === 'PATCH' && str_contains($url, '/v3/domains/example.com')) {
            $state['zone_patch_count'] = ($state['zone_patch_count'] ?? 0) + 1;
            $state['account'] = $this->accountBody('example.com', zone: true, verified: true);

            return Http::response(['zone' => true], 200);
        }

        if ($method === 'POST' && str_contains($url, '/v10/projects/prj_test/domains') && ! str_contains($url, '/verify')) {
            $status = (int) ($state['attach_status'] ?? 200);
            if ($status === 429) {
                return Http::response(['error' => ['code' => 'rate_limited']], 429);
            }
            if ($status === 409) {
                $state['project_attached_on_provider'] = [
                    'name' => 'example.com',
                    'verified' => false,
                    'verification' => [[
                        'type' => 'TXT',
                        'domain' => '_vercel.example.com',
                        'value' => 'vc-domain-verify=abc',
                    ]],
                ];

                return Http::response(['error' => ['code' => 'domain_already_owned']], 409);
            }

            $state['attach_posted'] = true;
            $state['project_domain'] = [
                'name' => 'example.com',
                'verified' => (bool) ($state['attach_verified'] ?? false),
                'verification' => [],
            ];
            if (! in_array($state['project_domain'], $state['project_domains'] ?? [], true)) {
                $state['project_domains'][] = $state['project_domain'];
            }

            return Http::response($state['project_domain'], 200);
        }

        if ($method === 'DELETE' && str_contains($url, '/domains/example.com')) {
            $state['project_domains'] = array_values(array_filter(
                $state['project_domains'] ?? [],
                fn (array $domain): bool => ($domain['name'] ?? null) !== 'example.com'
            ));
            unset($state['project_domain']);

            return Http::response([], 204);
        }

        if (str_contains($url, '/v9/projects/prj_test/domains/example.com')) {
            if ($method === 'POST' && str_contains($url, '/verify')) {
                return Http::response(['name' => 'example.com', 'verified' => true], 200);
            }

            $attached = $state['project_attached_on_provider'] ?? $state['project_domain'] ?? null;
            if ($attached !== null) {
                return Http::response($attached, 200);
            }

            return Http::response(['error' => 'not_found'], 404);
        }

        if ($method === 'GET' && str_contains($url, '/v6/domains/example.com/config')) {
            return Http::response(['misconfigured' => false, 'configuredBy' => 'CNAME'], 200);
        }

        if (str_contains($url, '/v8/certs')) {
            if ($method === 'POST') {
                $status = (int) ($state['issue_certificate_status'] ?? 200);
                if ($status >= 400) {
                    return Http::response(['error' => ['message' => 'provider failure']], $status);
                }

                return Http::response([
                    'id' => 'cert_new',
                    'cns' => ['example.com'],
                    'expiresAt' => 1_900_000_000_000,
                    'autoRenew' => true,
                ], 200);
            }

            $certs = array_key_exists('certificates', $state)
                ? $state['certificates']
                : [[
                    'id' => 'cert_apex',
                    'cns' => ['example.com'],
                    'expiresAt' => 1_900_000_000_000,
                    'autoRenew' => true,
                ]];

            return Http::response([
                'certs' => $certs,
                'pagination' => ['next' => null],
            ], 200);
        }

        return Http::response(['error' => 'unmatched'], 500);
    }

    /**
     * @return array<string, mixed>
     */
    private function accountBody(
        string $name,
        bool $zone,
        bool $verified,
        string $serviceType = 'zeit.world'
    ): array {
        return [
            'id' => 'dom_' . str_replace('.', '_', $name),
            'name' => $name,
            'zone' => $zone,
            'verified' => $verified,
            'serviceType' => $serviceType,
            'nameservers' => ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'],
            'intendedNameservers' => ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'],
        ];
    }

    private function mockNameservers(bool $ok): void
    {
        $checker = Mockery::mock(DnsNameserverChecker::class);
        $checker->shouldReceive('getObservedNameservers')
            ->andReturn($ok ? ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'] : ['ns1.other-dns.com']);
        $checker->shouldReceive('hasExpectedNameservers')
            ->andReturn($ok);

        $this->app->instance(DnsNameserverChecker::class, $checker);
        $this->service = new DomainProvisioningService(
            app(VercelDomainClient::class),
            app(VercelDomainCache::class),
            app(VercelDomainInventoryService::class),
            app(VercelMutationGuard::class),
            $checker
        );
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
            'services.vercel.max_project_domains' => 50,
            'services.vercel.retry_max_attempts' => 1,
        ]);
    }
}
