<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Analytics;

use App\Models\User;
use App\Services\Analytics\DashboardPresenceEligibilityService;
use App\Services\Analytics\DashboardPresenceService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Event;
use Illuminate\Log\Events\MessageLogged;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\Support\FakeDashboardPresenceRedisFactory;
use Tests\TestCase;

class DashboardPresenceServiceTest extends TestCase
{
    private FakeDashboardPresenceRedisFactory $redis;

    private DashboardPresenceService $service;

    protected function setUp(): void
    {
        parent::setUp();

        DashboardPresenceService::resetConfigurationWarnings();

        config([
            'dashboard-presence.enabled' => true,
            'dashboard-presence.heartbeat_seconds' => 45,
            'dashboard-presence.online_window_seconds' => 120,
            'dashboard-presence.redis_connection' => 'cache',
            'dashboard-presence.users_key' => 'presence:dashboard:users',
            'dashboard-presence.tenant_organizations_key' => 'presence:dashboard:tenant-organizations',
            'dashboard-presence.admin_poll_seconds' => 30,
            'dashboard-presence.exclude_impersonation' => true,
        ]);

        $this->redis = new FakeDashboardPresenceRedisFactory();
        $this->service = new DashboardPresenceService($this->redis, new DashboardPresenceEligibilityService());
    }

    /** @test */
    public function it_records_tenants_and_employees_using_distinct_users_and_canonical_tenant_owners(): void
    {
        $tenant = $this->makeTenant(10);
        $employeeOne = $this->makeEmployee(20, $tenant);
        $employeeTwo = $this->makeEmployee(21, $tenant);

        $this->service->record($employeeOne, CarbonImmutable::parse('2026-09-08 10:00:00', 'UTC'));
        $this->service->record($employeeTwo, CarbonImmutable::parse('2026-09-08 10:00:30', 'UTC'));
        $this->service->record($tenant, CarbonImmutable::parse('2026-09-08 10:01:00', 'UTC'));

        $snapshot = $this->service->snapshot(CarbonImmutable::parse('2026-09-08 10:01:30', 'UTC'));

        $this->assertTrue($snapshot['available']);
        $this->assertSame(3, $snapshot['online_users']);
        $this->assertSame(1, $snapshot['online_tenant_organizations']);
    }

    /** @test */
    public function it_counts_repeated_heartbeats_for_the_same_user_once(): void
    {
        $tenant = $this->makeTenant(10);

        $this->service->record($tenant, CarbonImmutable::parse('2026-09-08 10:00:00', 'UTC'));
        $this->service->record($tenant, CarbonImmutable::parse('2026-09-08 10:00:50', 'UTC'));

        $snapshot = $this->service->snapshot(CarbonImmutable::parse('2026-09-08 10:01:00', 'UTC'));

        $this->assertSame(1, $snapshot['online_users']);
        $this->assertSame(1, $snapshot['online_tenant_organizations']);
    }

    /** @test */
    public function it_excludes_exact_cutoff_scores_and_keeps_just_inside_the_window(): void
    {
        $tenantA = $this->makeTenant(10);
        $tenantB = $this->makeTenant(11);

        $this->service->record($tenantA, CarbonImmutable::parse('2026-09-08 10:00:00', 'UTC'));
        $this->service->record($tenantB, CarbonImmutable::parse('2026-09-08 10:00:01', 'UTC'));

        $snapshot = $this->service->snapshot(CarbonImmutable::parse('2026-09-08 10:02:00', 'UTC'));

        $this->assertSame(1, $snapshot['online_users']);
        $this->assertSame(1, $snapshot['online_tenant_organizations']);
    }

    /** @test */
    public function it_marks_scores_after_the_window_as_offline(): void
    {
        $tenant = $this->makeTenant(10);

        $this->service->record($tenant, CarbonImmutable::parse('2026-09-08 10:00:00', 'UTC'));

        $snapshot = $this->service->snapshot(CarbonImmutable::parse('2026-09-08 10:02:01', 'UTC'));

        $this->assertTrue($snapshot['available']);
        $this->assertSame(0, $snapshot['online_users']);
        $this->assertSame(0, $snapshot['online_tenant_organizations']);
    }

    /** @test */
    public function it_rejects_banned_inactive_soft_deleted_invalid_owner_and_impersonated_users(): void
    {
        $impersonatedAdmin = $this->makeTenant(10)
            ->withAccessToken(new PersonalAccessToken(['name' => 'impersonated-by-admin-99']));
        $inactive = $this->makeTenant(12, ['active' => false]);
        $banned = $this->makeTenant(13, ['status' => 0]);
        $softDeleted = $this->makeTenant(14, ['deleted_at' => '2026-09-01 00:00:00']);
        $invalidEmployee = new User([
            'account_type' => 'employee',
            'tenant_id' => 0,
            'status' => 1,
            'active' => true,
        ]);
        $invalidEmployee->id = 15;

        foreach ([$impersonatedAdmin, $inactive, $banned, $softDeleted, $invalidEmployee] as $user) {
            $result = $this->service->record($user);
            $this->assertFalse($result['recorded']);
            $this->assertTrue($result['available']);
        }

        $this->assertSame([], $this->redis->connection()->sets);
    }

    /** @test */
    public function it_only_excludes_the_real_admin_impersonation_token_prefix(): void
    {
        $regularToken = $this->makeTenant(11)
            ->withAccessToken(new PersonalAccessToken(['name' => 'impersonated-by-42']));

        $result = $this->service->record($regularToken);

        $this->assertTrue($result['recorded']);
        $this->assertTrue($result['available']);
    }

    /** @test */
    public function it_fails_closed_when_disabled_or_invalidly_configured(): void
    {
        $warnings = [];
        Event::listen(
            MessageLogged::class,
            function (MessageLogged $event) use (&$warnings): void {
                if ($event->level === 'warning') {
                    $warnings[] = [
                        'message' => $event->message,
                        'context' => $event->context,
                    ];
                }
            }
        );

        config(['dashboard-presence.enabled' => false]);

        $disabled = $this->service->record($this->makeTenant(10));
        $this->assertFalse($disabled['available']);
        $this->assertNull($disabled['recorded_at']);
        $this->assertSame('disabled', $disabled['reason']);

        $disabledAgain = $this->service->snapshot();
        $this->assertFalse($disabledAgain['available']);
        $this->assertNull($disabledAgain['online_users']);
        $this->assertNull($disabledAgain['online_tenant_organizations']);

        $disabledWarnings = array_values(array_filter(
            $warnings,
            fn (array $entry): bool => ($entry['context']['reason'] ?? null) === 'disabled'
        ));
        $this->assertCount(1, $disabledWarnings);
        $this->assertSame(
            'Dashboard presence unavailable due to configuration.',
            $disabledWarnings[0]['message']
        );

        DashboardPresenceService::resetConfigurationWarnings();
        $warnings = [];

        config([
            'dashboard-presence.enabled' => true,
            'dashboard-presence.online_window_seconds' => 80,
        ]);

        $snapshot = $this->service->snapshot();
        $this->assertFalse($snapshot['available']);
        $this->assertNull($snapshot['online_users']);
        $this->assertNull($snapshot['online_tenant_organizations']);
        $this->assertSame('invalid_configuration', $snapshot['reason']);

        $this->service->snapshot();

        $invalidWarnings = array_values(array_filter(
            $warnings,
            fn (array $entry): bool => ($entry['context']['reason'] ?? null) === 'invalid_configuration'
        ));
        $this->assertCount(1, $invalidWarnings);
        $this->assertSame(
            'Dashboard presence unavailable due to configuration.',
            $invalidWarnings[0]['message']
        );
    }

    /** @test */
    public function it_marks_redis_errors_as_unavailable_with_null_counts_on_record_and_snapshot(): void
    {
        $redis = new FakeDashboardPresenceRedisFactory(true);
        $service = new DashboardPresenceService($redis, new DashboardPresenceEligibilityService());

        $result = $service->record($this->makeTenant(10));

        $this->assertFalse($result['recorded']);
        $this->assertFalse($result['available']);
        $this->assertNull($result['recorded_at']);
        $this->assertSame('redis_unavailable', $result['reason']);

        $snapshot = $service->snapshot(CarbonImmutable::parse('2026-09-08 10:00:00', 'UTC'));

        $this->assertFalse($snapshot['available']);
        $this->assertNull($snapshot['online_users']);
        $this->assertNull($snapshot['online_tenant_organizations']);
        $this->assertSame('redis_unavailable', $snapshot['reason']);
    }

    private function makeTenant(int $id, array $attributes = []): User
    {
        $user = new User(array_merge([
            'account_type' => 'tenant',
            'status' => 1,
            'active' => true,
        ], $attributes));
        $user->id = $id;

        if (array_key_exists('deleted_at', $attributes)) {
            $user->deleted_at = $attributes['deleted_at'];
        }

        return $user;
    }

    private function makeEmployee(int $id, User $tenant, array $attributes = []): User
    {
        $user = new User(array_merge([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'status' => 1,
            'active' => true,
        ], $attributes));
        $user->id = $id;
        $user->setRelation('tenant', $tenant);

        if (array_key_exists('deleted_at', $attributes)) {
            $user->deleted_at = $attributes['deleted_at'];
        }

        return $user;
    }
}
