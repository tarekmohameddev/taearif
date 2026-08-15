<?php

namespace Tests\Feature\Admin;

use App\Services\Admin\WhatsappNumberDiagnosticsService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class WhatsappNumberDiagnosticsTest extends TestCase
{
    use RefreshDatabase;

    private WhatsappNumberDiagnosticsService $service;

    private static int $sequence = 0;

    private const TEST_ACCESS_TOKEN = 'diag-test-access-token-do-not-leak';

    private const TEST_APP_TOKEN = 'diag-test-app-token';

    protected function refreshTestDatabase(): void
    {
        if (! RefreshDatabaseState::$migrated) {
            if (! Schema::hasTable('whatsapp_users') || ! Schema::hasTable('users')) {
                $this->markTestSkipped(
                    'taearif_testing needs core tables (users, whatsapp_users). Import the application schema into taearif_testing.'
                );
            }

            RefreshDatabaseState::$migrated = true;
            $this->app->make(Kernel::class)->setArtisan(null);
        }

        $this->beginDatabaseTransaction();
    }

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-08-12 14:00:00'));

        config([
            'services.meta.app_token' => self::TEST_APP_TOKEN,
            'services.meta.api_version' => 'v20.0',
        ]);

        $this->service = app(WhatsappNumberDiagnosticsService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function nextSuffix(): string
    {
        return (string) ++self::$sequence;
    }

    private function createTenant(): int
    {
        return (int) DB::table('users')->insertGetId([
            'tenant_id' => null,
            'account_type' => 'tenant',
            'active' => true,
            'email' => 'tenant-' . Str::uuid() . '@example.com',
            'username' => 'tenant-' . Str::uuid(),
            'password' => Hash::make('secret'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createNumber(int $userId, array $overrides = []): int
    {
        $suffix = $this->nextSuffix();

        return (int) DB::table('whatsapp_users')->insertGetId(array_merge([
            'user_id' => $userId,
            'employee_id' => null,
            'number' => '+9665' . str_pad($suffix, 8, '0', STR_PAD_LEFT),
            'name' => 'Number ' . $suffix,
            'status' => 'active',
            'request_status' => 'pending',
            'phone_id' => 'phone-id-' . $suffix,
            'created_at' => now(),
            'updated_at' => now(),
        ], $overrides));
    }

    /**
     * @param  array<string,mixed>  $result
     */
    private function assertNoTokenLeak(array $result, string $token = self::TEST_ACCESS_TOKEN): void
    {
        $encoded = json_encode($result);

        $this->assertIsString($encoded);
        $this->assertStringNotContainsString($token, $encoded);
        $this->assertStringNotContainsString(substr($token, 0, 12), $encoded);
        $this->assertStringNotContainsString(substr($token, -12), $encoded);
    }

    /**
     * @param  array<int,array<string,mixed>>  $checks
     */
    private function checkStatus(array $checks, string $key): string
    {
        foreach ($checks as $check) {
            if (($check['key'] ?? null) === $key) {
                return (string) $check['status'];
            }
        }

        $this->fail("Missing diagnostic check [{$key}]");

        return '';
    }

    private function debugTokenResponse(array $overrides = []): array
    {
        return array_replace_recursive([
            'data' => [
                'is_valid' => true,
                'expires_at' => now()->addDays(30)->timestamp,
                'granular_scopes' => [
                    [
                        'scope' => 'whatsapp_business_management',
                        'target_ids' => ['waba-123'],
                    ],
                ],
            ],
        ], $overrides);
    }

    private function phoneNumbersResponse(array $phones): array
    {
        return [
            'data' => $phones,
        ];
    }

    /** @test */
    public function it_reports_a_fully_healthy_number(): void
    {
        $tenantId = $this->createTenant();
        $phoneId = 'healthy-phone-id';
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => $phoneId,
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
            'token_expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse()),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => $phoneId,
                    'display_phone_number' => '+966500000001',
                    'verified_name' => 'Healthy Biz',
                    'quality_rating' => 'GREEN',
                ],
            ])),
        ]);

        $result = $this->service->diagnose($numberId);

        $this->assertSame('ok', $result['summary']);
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'token_present'));
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'app_token_configured'));
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'token_valid'));
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'token_expiry'));
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'waba_id_match'));
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'phone_id_known_to_meta'));
        $this->assertCount(1, $result['meta_phone_numbers']);
        $this->assertSame($phoneId, $result['meta_phone_numbers'][0]['id']);
        $this->assertInstanceOf(Carbon::class, $result['checked_at']);
        $this->assertNoTokenLeak($result);

        Http::assertSentCount(2);
    }

    /** @test */
    public function it_reports_an_expired_token(): void
    {
        $tenantId = $this->createTenant();
        $phoneId = 'expired-phone-id';
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => $phoneId,
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
            'token_expires_at' => now()->subDay(),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse([
                'data' => [
                    'is_valid' => true,
                    'expires_at' => now()->subDay()->timestamp,
                ],
            ])),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => $phoneId,
                    'display_phone_number' => '+966500000002',
                    'verified_name' => 'Expired Biz',
                    'quality_rating' => 'GREEN',
                ],
            ])),
        ]);

        $result = $this->service->diagnose($numberId);

        $this->assertSame('fail', $result['summary']);
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'token_valid'));
        $this->assertSame('fail', $this->checkStatus($result['checks'], 'token_expiry'));
        $this->assertNoTokenLeak($result);
    }

    /** @test */
    public function it_reports_waba_id_mismatch(): void
    {
        $tenantId = $this->createTenant();
        $phoneId = 'waba-mismatch-phone-id';
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => $phoneId,
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'stored-waba-999',
            'token_expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse([
                'data' => [
                    'granular_scopes' => [
                        [
                            'scope' => 'whatsapp_business_management',
                            'target_ids' => ['token-waba-123'],
                        ],
                    ],
                ],
            ])),
            'graph.facebook.com/v20.0/token-waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => $phoneId,
                    'display_phone_number' => '+966500000003',
                    'verified_name' => 'Mismatch Biz',
                    'quality_rating' => 'GREEN',
                ],
            ])),
        ]);

        $result = $this->service->diagnose($numberId);

        $this->assertSame('fail', $result['summary']);
        $this->assertSame('fail', $this->checkStatus($result['checks'], 'waba_id_match'));
        $this->assertNoTokenLeak($result);
    }

    /** @test */
    public function it_reports_when_meta_does_not_recognise_phone_id(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => 'unknown-phone-id',
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
            'token_expires_at' => now()->addDays(30),
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse()),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([
                [
                    'id' => 'different-phone-id',
                    'display_phone_number' => '+966500000004',
                    'verified_name' => 'Other Biz',
                    'quality_rating' => 'YELLOW',
                ],
            ])),
        ]);

        $result = $this->service->diagnose($numberId);

        $this->assertSame('fail', $result['summary']);
        $this->assertSame('fail', $this->checkStatus($result['checks'], 'phone_id_known_to_meta'));
        $this->assertCount(1, $result['meta_phone_numbers']);
        $this->assertSame('different-phone-id', $result['meta_phone_numbers'][0]['id']);
        $this->assertNoTokenLeak($result);
    }

    /** @test */
    public function it_skips_graph_checks_when_access_token_is_missing(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'access_token' => null,
            'waba_id' => 'waba-123',
        ]);

        Http::fake();

        $result = $this->service->diagnose($numberId);

        $this->assertSame('fail', $result['summary']);
        $this->assertSame('fail', $this->checkStatus($result['checks'], 'token_present'));
        $this->assertSame('ok', $this->checkStatus($result['checks'], 'app_token_configured'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'token_valid'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'token_expiry'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'waba_id_match'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'phone_id_known_to_meta'));
        $this->assertSame([], $result['meta_phone_numbers']);
        $this->assertNoTokenLeak($result);

        Http::assertNothingSent();
    }

    /** @test */
    public function it_fails_when_graph_api_is_unreachable(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'phone_id' => 'unreachable-phone-id',
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response(['error' => ['message' => 'Service unavailable']], 503),
        ]);

        $result = $this->service->diagnose($numberId);

        $this->assertSame('fail', $result['summary']);
        $this->assertSame('fail', $this->checkStatus($result['checks'], 'token_valid'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'token_expiry'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'waba_id_match'));
        $this->assertSame('skipped', $this->checkStatus($result['checks'], 'phone_id_known_to_meta'));
        $this->assertNoTokenLeak($result);

        Http::assertSentCount(1);
    }

    /** @test */
    public function it_returns_not_found_for_unknown_whatsapp_user_id(): void
    {
        Http::fake();

        $result = $this->service->diagnose(999999999);

        $this->assertSame('fail', $result['summary']);
        $this->assertSame('fail', $this->checkStatus($result['checks'], 'not_found'));
        $this->assertSame([], $result['meta_phone_numbers']);
        $this->assertInstanceOf(Carbon::class, $result['checked_at']);
        $this->assertNoTokenLeak($result, self::TEST_ACCESS_TOKEN);

        Http::assertNothingSent();
    }

    /** @test */
    public function diagnose_does_not_write_to_the_database(): void
    {
        $tenantId = $this->createTenant();
        $numberId = $this->createNumber($tenantId, [
            'access_token' => self::TEST_ACCESS_TOKEN,
            'waba_id' => 'waba-123',
        ]);

        Http::fake([
            'graph.facebook.com/v20.0/debug_token*' => Http::response($this->debugTokenResponse()),
            'graph.facebook.com/v20.0/waba-123/phone_numbers*' => Http::response($this->phoneNumbersResponse([])),
        ]);

        $before = DB::table('whatsapp_users')->count();

        $this->service->diagnose($numberId);

        $this->assertSame($before, DB::table('whatsapp_users')->count());
    }
}
