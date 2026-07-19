<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\CRM\Pipedrive;

use App\Domain\CRM\Pipedrive\Contracts\PipedriveClientInterface;
use App\Domain\CRM\Pipedrive\DTOs\PipedriveCredentialsDto;
use App\Domain\CRM\Pipedrive\Exceptions\PipedriveApiException;
use App\Domain\CRM\Pipedrive\Exceptions\PipedriveNotConfiguredException;
use App\Domain\CRM\Pipedrive\Services\PipedriveSettingsService;
use App\Domain\CRM\Pipedrive\Services\PipedriveTenantSyncService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

/**
 * Tests for PipedriveTenantSyncService.
 *
 * Name-resolution tests are pure logic (no DB).
 * Integration-style tests (sync flow, idempotency, persistence) are wrapped
 * in DatabaseTransactions so all changes are rolled back automatically.
 */
class PipedriveTenantSyncServiceTest extends TestCase
{
    use DatabaseTransactions;
    // =========================================================================
    // Name resolution — pure logic, no DB required
    // =========================================================================

    /** @test */
    public function it_uses_full_name_when_available(): void
    {
        $user = $this->makeUser(['first_name' => 'Ahmed', 'last_name' => 'Mohamed']);

        $this->assertSame('Ahmed Mohamed', $this->resolveDisplayName($user));
    }

    /** @test */
    public function it_falls_back_to_company_name_when_no_personal_name(): void
    {
        $user = $this->makeUser(['first_name' => '', 'last_name' => '', 'company_name' => 'Acme Corp']);

        $this->assertSame('Acme Corp', $this->resolveDisplayName($user));
    }

    /** @test */
    public function it_skips_na_company_name_and_falls_back_to_username(): void
    {
        $user = $this->makeUser([
            'first_name' => null,
            'last_name'  => null,
            'company_name' => 'N/A',
            'username' => 'user_123',
        ]);

        $this->assertSame('user_123', $this->resolveDisplayName($user));
    }

    /** @test */
    public function it_falls_back_to_email_when_no_username(): void
    {
        $user = $this->makeUser([
            'first_name'   => null,
            'last_name'    => null,
            'company_name' => null,
            'username'     => '',
            'email'        => 'user@example.com',
        ]);

        $this->assertSame('user@example.com', $this->resolveDisplayName($user));
    }

    /** @test */
    public function it_falls_back_to_tenant_id_as_last_resort(): void
    {
        $user = $this->makeUser([
            'id'           => 99,
            'first_name'   => null,
            'last_name'    => null,
            'company_name' => '',
            'username'     => '',
            'email'        => '',
        ]);

        $this->assertSame('Tenant #99', $this->resolveDisplayName($user));
    }

    // =========================================================================
    // Sync flow — require DB; skip gracefully when taearif_testing is absent
    // =========================================================================

    /** @test */
    public function it_skips_auto_sync_when_disabled(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $service = $this->makeService($this->makeCredentials(enabled: false));

        $result = $service->sync($this->createTenantUser(), 'registration');

        $this->assertFalse($result->success);
        $this->assertSame('skipped', $result->status);
    }

    /** @test */
    public function it_allows_manual_sync_even_when_auto_sync_disabled(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $service = $this->makeService(
            $this->makeCredentials(enabled: false),
            $this->mockClient(),
        );

        $result = $service->sync($this->createTenantUser(), 'manual');

        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_throws_not_configured_when_credentials_missing(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $service = $this->makeService($this->makeCredentials(enabled: true, apiToken: null, baseUrl: null));

        $this->expectException(PipedriveNotConfiguredException::class);

        $service->sync($this->createTenantUser(), 'manual');
    }

    /** @test */
    public function it_skips_if_deal_already_synced_without_force(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $service = $this->makeService($this->makeCredentials());

        $result = $service->sync($this->createTenantUser(['pipedrive_deal_id' => 9999]), 'manual', force: false);

        $this->assertFalse($result->success);
        $this->assertSame('skipped', $result->status);
        $this->assertStringContainsString('9999', $result->errorMessage);
    }

    /** @test */
    public function it_re_syncs_when_force_is_true(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $service = $this->makeService($this->makeCredentials(), $this->mockClient());

        $result = $service->sync($this->createTenantUser(['pipedrive_deal_id' => 9999]), 'manual', force: true);

        $this->assertTrue($result->success);
    }

    /** @test */
    public function it_skips_org_creation_when_company_is_na(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $mockClient = Mockery::mock(PipedriveClientInterface::class);
        $mockClient->shouldNotReceive('createOrganization');
        $mockClient->shouldReceive('createPerson')->once()->andReturn(['data' => ['id' => 1]]);
        $mockClient->shouldReceive('createDeal')->once()->andReturn(['data' => ['id' => 2]]);

        $service = $this->makeService($this->makeCredentials(), $mockClient);

        $service->sync($this->createTenantUser(['company_name' => 'N/A']), 'manual');
    }

    /** @test */
    public function it_creates_org_when_company_name_is_set(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $mockClient = Mockery::mock(PipedriveClientInterface::class);
        $mockClient->shouldReceive('createOrganization')
            ->once()
            ->with(Mockery::on(fn ($d) => $d['name'] === 'Taearif Company'))
            ->andReturn(['data' => ['id' => 10]]);
        $mockClient->shouldReceive('createPerson')->once()->andReturn(['data' => ['id' => 1]]);
        $mockClient->shouldReceive('createDeal')->once()->andReturn(['data' => ['id' => 2]]);

        $service = $this->makeService($this->makeCredentials(), $mockClient);

        $result = $service->sync($this->createTenantUser(['company_name' => 'Taearif Company']), 'manual');

        $this->assertSame(10, $result->orgId);
    }

    /** @test */
    public function it_returns_failed_result_on_api_exception(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $mockClient = Mockery::mock(PipedriveClientInterface::class);
        $mockClient->shouldReceive('createOrganization')->andThrow(
            new PipedriveApiException('Unauthorized', 401)
        );

        $service = $this->makeService($this->makeCredentials(), $mockClient);

        $result = $service->sync($this->createTenantUser(['company_name' => 'Some Company']), 'manual');

        $this->assertFalse($result->success);
        $this->assertSame('failed', $result->status);
    }

    /** @test */
    public function it_persists_pipedrive_ids_on_user_after_success(): void
    {
        if (!$this->dbAvailable()) {
            $this->markTestSkipped('taearif_testing DB unavailable.');
        }

        $service = $this->makeService($this->makeCredentials(), $this->mockClient(personId: 500, dealId: 600));

        $user = $this->createTenantUser();
        $service->sync($user, 'manual');

        $fresh = $user->fresh();
        $this->assertSame(500, (int) $fresh->pipedrive_person_id);
        $this->assertSame(600, (int) $fresh->pipedrive_deal_id);
        $this->assertNotNull($fresh->pipedrive_synced_at);
    }

    // =========================================================================
    // Helpers
    // =========================================================================

    private function makeUser(array $attributes): User
    {
        $user = new User();
        foreach ($attributes as $key => $value) {
            $user->$key = $value;
        }
        return $user;
    }

    private function createTenantUser(array $attributes = []): User
    {
        // Pipedrive columns are not mass-assignable on User; split them out and
        // set via direct property access after creation so they're persisted.
        $pipedriveKeys = ['pipedrive_deal_id', 'pipedrive_person_id', 'pipedrive_synced_at'];
        $pipedriveAttrs = array_intersect_key($attributes, array_flip($pipedriveKeys));
        $userAttrs = array_diff_key($attributes, $pipedriveAttrs);

        $user = User::create(array_merge([
            'first_name'   => 'Test',
            'last_name'    => 'User',
            'email'        => 'user' . uniqid() . '@example.com',
            'username'     => 'user' . uniqid(),
            'password'     => bcrypt('password'),
            'account_type' => 'tenant',
            'status'       => 1,
        ], $userAttrs));

        if (!empty($pipedriveAttrs)) {
            foreach ($pipedriveAttrs as $key => $value) {
                $user->$key = $value;
            }
            $user->save();
        }

        return $user;
    }

    private function makeCredentials(
        bool $enabled = true,
        ?string $apiToken = 'fake-token',
        ?string $baseUrl = 'https://company.pipedrive.com',
    ): PipedriveCredentialsDto {
        return new PipedriveCredentialsDto(
            enabled: $enabled,
            apiToken: $apiToken,
            baseUrl: $baseUrl,
            pipelineId: 2,
            stageId: 8,
            dealTitlePrefix: 'New Lead - ',
        );
    }

    private function mockClient(int $personId = 1, int $dealId = 2): PipedriveClientInterface
    {
        $mock = Mockery::mock(PipedriveClientInterface::class);
        $mock->shouldReceive('createOrganization')->andReturn(['data' => ['id' => 10]]);
        $mock->shouldReceive('createPerson')->andReturn(['data' => ['id' => $personId]]);
        $mock->shouldReceive('createDeal')->andReturn(['data' => ['id' => $dealId]]);
        return $mock;
    }

    private function makeService(PipedriveCredentialsDto $credentials, ?PipedriveClientInterface $client = null): PipedriveTenantSyncService
    {
        $settingsService = Mockery::mock(PipedriveSettingsService::class);
        $settingsService->shouldReceive('getCredentials')->andReturn($credentials);

        return new class($settingsService, $client) extends PipedriveTenantSyncService {
            public function __construct(
                PipedriveSettingsService $settingsService,
                private readonly ?PipedriveClientInterface $injectedClient,
            ) {
                parent::__construct($settingsService);
            }

            protected function buildClient(PipedriveCredentialsDto $credentials): PipedriveClientInterface
            {
                return $this->injectedClient ?? parent::buildClient($credentials);
            }
        };
    }

    private function resolveDisplayName(User $user): string
    {
        $credentials = $this->makeCredentials();
        $settingsService = Mockery::mock(PipedriveSettingsService::class);
        $settingsService->shouldReceive('getCredentials')->andReturn($credentials);
        $service = new PipedriveTenantSyncService($settingsService);

        $method = (new \ReflectionClass($service))->getMethod('resolveDisplayName');
        $method->setAccessible(true);

        return $method->invoke($service, $user);
    }

    private function dbAvailable(): bool
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return \Illuminate\Support\Facades\Schema::hasTable('users');
        } catch (\Throwable) {
            return false;
        }
    }
}
