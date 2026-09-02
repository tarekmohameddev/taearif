<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Vercel;

use App\Services\Vercel\VercelDomainClient;
use App\Services\Vercel\VercelDomainException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VercelDomainClientTest extends TestCase
{
    private VercelDomainClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureVercel();
        $this->client = app(VercelDomainClient::class);
    }

    /** @test */
    public function get_account_domain_normalizes_v5_response(): void
    {
        Http::fake([
            'api.vercel.com/v5/domains/example.com*' => Http::response(
                $this->accountDomainFixture('example.com', zone: true, verified: true),
                200
            ),
        ]);

        $result = $this->client->getAccountDomain('example.com');

        $this->assertNotNull($result);
        $this->assertSame('example.com', $result['name']);
        $this->assertTrue($result['zone']);
        $this->assertTrue($result['verified']);
        $this->assertSame('zeit.world', $result['serviceType']);
        $this->assertSame(['ns1.vercel-dns.com', 'ns2.vercel-dns.com'], $result['nameservers']);
    }

    /** @test */
    public function get_account_domain_returns_null_on_404(): void
    {
        Http::fake([
            'api.vercel.com/v5/domains/missing.example*' => Http::response(['error' => 'not_found'], 404),
        ]);

        $this->assertNull($this->client->getAccountDomain('missing.example'));
    }

    /** @test */
    public function create_account_domain_posts_v7_with_zone_and_team_id(): void
    {
        Http::fake([
            'api.vercel.com/v7/domains*' => function (Request $request) {
                $payload = $request->data();
                $this->assertSame('POST', $request->method());
                $this->assertStringContainsString('teamId=team_test', $request->url());
                $this->assertSame('add', $payload['method'] ?? null);
                $this->assertSame('example.com', $payload['name'] ?? null);
                $this->assertTrue($payload['zone'] ?? false);

                return Http::response(
                    $this->accountDomainFixture('example.com', zone: true, verified: false),
                    200
                );
            },
        ]);

        $result = $this->client->createAccountDomain('example.com');

        $this->assertTrue($result['was_created']);
        $this->assertFalse($result['was_adopted']);
        $this->assertTrue($result['zone']);
        $this->assertSame('example.com', $result['name']);
    }

    /** @test */
    public function create_account_domain_adopts_existing_domain_on_conflict(): void
    {
        Http::fake([
            'api.vercel.com/v7/domains*' => Http::response(['error' => ['code' => 'domain_already_owned']], 409),
            'api.vercel.com/v5/domains/example.com*' => Http::response(
                $this->accountDomainFixture('example.com', zone: true, verified: true),
                200
            ),
        ]);

        $result = $this->client->createAccountDomain('example.com');

        $this->assertFalse($result['was_created']);
        $this->assertTrue($result['was_adopted']);
        $this->assertTrue($result['zone']);
    }

    /** @test */
    public function create_account_domain_enables_zone_when_domain_exists_without_zone(): void
    {
        Http::fake([
            'api.vercel.com/v7/domains*' => Http::response(['error' => ['code' => 'domain_already_owned']], 409),
            'api.vercel.com/v5/domains/example.com*' => Http::sequence()
                ->push($this->accountDomainFixture('example.com', zone: false, verified: true, serviceType: 'external'))
                ->push($this->accountDomainFixture('example.com', zone: true, verified: true)),
            'api.vercel.com/v3/domains/example.com*' => Http::response(['zone' => true], 200),
        ]);

        $result = $this->client->createAccountDomain('example.com');

        $this->assertTrue($result['zone']);
        $this->assertFalse($result['was_created']);
    }

    /** @test */
    public function enable_account_domain_zone_patches_v3_with_zone_true(): void
    {
        Http::fake([
            'api.vercel.com/v5/domains/example.com*' => Http::sequence()
                ->push($this->accountDomainFixture('example.com', zone: false, verified: true, serviceType: 'external'))
                ->push($this->accountDomainFixture('example.com', zone: true, verified: true)),
            'api.vercel.com/v3/domains/example.com*' => function (Request $request) {
                $this->assertSame('PATCH', $request->method());
                $this->assertStringContainsString('teamId=team_test', $request->url());
                $this->assertSame('update', $request->data()['op'] ?? null);
                $this->assertTrue($request->data()['zone'] ?? false);

                return Http::response(['zone' => true], 200);
            },
        ]);

        $result = $this->client->enableAccountDomainZone('example.com');

        $this->assertTrue($result['zone']);
        $this->assertFalse($result['was_created']);
        $this->assertFalse($result['was_adopted']);
    }

    /** @test */
    public function enable_account_domain_zone_is_idempotent_when_zone_already_enabled(): void
    {
        Http::fake([
            'api.vercel.com/v5/domains/example.com*' => Http::response(
                $this->accountDomainFixture('example.com', zone: true, verified: true),
                200
            ),
        ]);

        $result = $this->client->enableAccountDomainZone('example.com');

        $this->assertTrue($result['zone']);
        $this->assertFalse($result['was_created']);
        $this->assertTrue($result['was_adopted']);
        Http::assertSentCount(1);
    }

    /** @test */
    public function list_certificates_normalizes_inventory(): void
    {
        Http::fake([
            'api.vercel.com/v8/certs*' => Http::response([
                'certs' => [
                    [
                        'id' => 'cert_apex',
                        'cns' => ['example.com'],
                        'createdAt' => 1_700_000_000_000,
                        'expiresAt' => 1_900_000_000_000,
                        'autoRenew' => true,
                    ],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $result = $this->client->listCertificates();

        $this->assertFalse($result['is_lower_bound']);
        $this->assertCount(1, $result['certificates']);
        $this->assertSame('cert_apex', $result['certificates'][0]['id']);
        $this->assertSame(['example.com'], $result['certificates'][0]['cns']);
        $this->assertSame('issued', $result['certificates'][0]['readiness']);
    }

    /** @test */
    public function get_certificate_reads_v8_cert_by_id(): void
    {
        Http::fake([
            'api.vercel.com/v8/certs/cert_apex*' => Http::response([
                'id' => 'cert_apex',
                'cns' => ['example.com'],
                'createdAt' => 1_700_000_000_000,
                'expiresAt' => 1_900_000_000_000,
                'autoRenew' => true,
            ], 200),
        ]);

        $cert = $this->client->getCertificate('cert_apex');

        $this->assertSame('cert_apex', $cert['id']);
        $this->assertSame(['example.com'], $cert['cns']);
    }

    /** @test */
    public function issue_certificate_posts_cns_to_v8(): void
    {
        Http::fake([
            'api.vercel.com/v8/certs*' => function (Request $request) {
                if ($request->method() === 'GET') {
                    return Http::response([
                        'certs' => [],
                        'pagination' => ['count' => 0, 'next' => null, 'prev' => null],
                    ], 200);
                }

                $this->assertSame('POST', $request->method());
                $this->assertStringContainsString('teamId=team_test', $request->url());
                $this->assertSame(['example.com'], $request->data()['cns'] ?? null);

                return Http::response([
                    'id' => 'cert_new',
                    'cns' => ['example.com'],
                    'createdAt' => 1_700_000_000_000,
                    'expiresAt' => 1_900_000_000_000,
                    'autoRenew' => true,
                ], 200);
            },
        ]);

        $result = $this->client->issueCertificate('example.com');

        $this->assertTrue($result['was_created']);
        $this->assertFalse($result['was_adopted']);
        $this->assertSame('cert_new', $result['id']);
    }

    /** @test */
    public function issue_certificate_adopts_existing_covering_cert_without_post(): void
    {
        Http::fake([
            'api.vercel.com/v8/certs*' => Http::response([
                'certs' => [
                    [
                        'id' => 'cert_existing',
                        'cns' => ['example.com'],
                        'createdAt' => 1_700_000_000_000,
                        'expiresAt' => 1_900_000_000_000,
                        'autoRenew' => true,
                    ],
                ],
                'pagination' => ['count' => 1, 'next' => null, 'prev' => null],
            ], 200),
        ]);

        $result = $this->client->issueCertificate('example.com');

        $this->assertFalse($result['was_created']);
        $this->assertTrue($result['was_adopted']);
        $this->assertSame('cert_existing', $result['id']);
        Http::assertSent(fn (Request $request): bool => $request->method() !== 'POST');
    }

    /** @test */
    public function certificate_covers_host_with_exact_san_match(): void
    {
        $cert = [
            'cns' => ['example.com'],
            'expiresAt' => 1_900_000_000_000,
        ];

        $this->assertTrue($this->client->certificateCoversHost('example.com', $cert));
    }

    /** @test */
    public function certificate_wildcard_covers_www_but_not_apex(): void
    {
        $cert = [
            'cns' => ['*.example.com'],
            'expiresAt' => 1_900_000_000_000,
        ];

        $this->assertTrue($this->client->certificateCoversHost('www.example.com', $cert));
        $this->assertFalse($this->client->certificateCoversHost('example.com', $cert));
    }

    /** @test */
    public function certificate_wildcard_does_not_cover_deeper_subdomains(): void
    {
        $cert = [
            'cns' => ['*.example.com'],
            'expiresAt' => 1_900_000_000_000,
        ];

        $this->assertFalse($this->client->certificateCoversHost('a.b.example.com', $cert));
    }

    /** @test */
    public function expired_certificate_does_not_cover_host(): void
    {
        $cert = [
            'cns' => ['example.com'],
            'expiresAt' => 1_000_000_000_000,
        ];

        $this->assertFalse($this->client->certificateCoversHost('example.com', $cert));
        $this->assertNull($this->client->findCoveringCertificate('example.com', [
            'certificates' => [
                [
                    'id' => 'expired',
                    'cns' => ['example.com'],
                    'expiresAt' => 1_000_000_000_000,
                    'readiness' => 'certificate_error',
                ],
            ],
        ]));
    }

    /** @test */
    public function find_covering_certificate_returns_matching_inventory_entry(): void
    {
        $inventory = [
            'certificates' => [
                [
                    'id' => 'cert_www',
                    'cns' => ['*.example.com'],
                    'expiresAt' => 1_900_000_000_000,
                    'readiness' => 'issued',
                ],
            ],
        ];

        $match = $this->client->findCoveringCertificate('www.example.com', $inventory);

        $this->assertNotNull($match);
        $this->assertSame('cert_www', $match['id']);
    }

    /** @test */
    public function get_domain_config_reuses_v6_config_endpoint(): void
    {
        Http::fake([
            'api.vercel.com/v6/domains/example.com/config*' => function (Request $request) {
                $this->assertSame('GET', $request->method());
                $this->assertStringContainsString('teamId=team_test', $request->url());

                return Http::response(['misconfigured' => false, 'configuredBy' => 'CNAME'], 200);
            },
        ]);

        $config = $this->client->getDomainConfig('example.com');

        $this->assertFalse($config['misconfigured']);
        $this->assertSame('CNAME', $config['configuredBy']);
    }

    /** @test */
    public function provider_errors_are_normalized_to_vercel_domain_exception(): void
    {
        Http::fake([
            'api.vercel.com/v5/domains/example.com*' => Http::response(['error' => ['code' => 'forbidden']], 403),
        ]);

        $this->expectException(VercelDomainException::class);
        $this->expectExceptionCode(0);

        try {
            $this->client->getAccountDomain('example.com');
        } catch (VercelDomainException $exception) {
            $this->assertSame(403, $exception->statusCode);
            $this->assertSame(VercelDomainException::CODE_UNAUTHORIZED, $exception->internalCode);

            throw $exception;
        }
    }

    /** @test */
    public function certificate_pretest_failure_maps_to_pending_and_does_not_leak_raw_json(): void
    {
        Http::fake([
            'api.vercel.com/v8/certs*' => function ($request) {
                if ($request->method() === 'GET') {
                    return Http::response(['certs' => [], 'pagination' => ['next' => null]], 200);
                }

                return Http::response([
                    'error' => [
                        'cns' => ['fesal-1998-site.taearif'],
                        'code' => 'http_pretest_domain_not_resolving_to_vercel_error',
                        'statusCode' => 449,
                        'name' => 'HttpPretestDomainNotResolvingToVercelError',
                        'domain' => 'fesal-1998-site.taearif',
                    ],
                ], 449);
            },
        ]);

        try {
            $this->client->issueCertificate('fesal-1998-site.taearif');
            $this->fail('Expected a VercelDomainException for the certificate pretest failure.');
        } catch (VercelDomainException $exception) {
            // Classified as pending (not a fatal provider_error) so the run can
            // resolve the domain's true health.
            $this->assertSame(VercelDomainException::CODE_VERIFICATION_PENDING, $exception->internalCode);
            $this->assertSame(449, $exception->statusCode);
            // The raw provider JSON must not leak into the surfaced message.
            $this->assertStringNotContainsString('{', $exception->getMessage());
            $this->assertStringNotContainsString('statusCode', $exception->getMessage());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function accountDomainFixture(
        string $name,
        bool $zone,
        bool $verified,
        string $serviceType = 'zeit.world'
    ): array {
        return [
            'domain' => [
                'id' => 'dom_' . str_replace('.', '_', $name),
                'name' => $name,
                'zone' => $zone,
                'verified' => $verified,
                'serviceType' => $serviceType,
                'nameservers' => ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'],
                'intendedNameservers' => ['ns1.vercel-dns.com', 'ns2.vercel-dns.com'],
                'createdAt' => 1_700_000_000_000,
            ],
        ];
    }

    private function configureVercel(): void
    {
        config([
            'services.vercel.token' => 'test-token',
            'services.vercel.project_id' => 'prj_test',
            'services.vercel.team_id' => 'team_test',
            'services.vercel.base_url' => 'https://api.vercel.com',
            'services.vercel.retry_max_attempts' => 1,
        ]);
    }
}
