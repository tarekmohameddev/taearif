<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\Analytics\DashboardPresenceService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Redis\Factory as RedisFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Admin\AdminApiTestCase;
use Tests\Support\FakeDashboardPresenceRedisFactory;

class DashboardPresenceHeartbeatTest extends AdminApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
    }

    /** @test */
    public function guest_requests_are_rejected(): void
    {
        $this->postJson('/api/dashboard/presence/heartbeat')->assertUnauthorized();
    }

    /** @test */
    public function tenant_and_employee_heartbeats_are_recorded(): void
    {
        $this->app->instance(RedisFactory::class, new FakeDashboardPresenceRedisFactory());

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'presence-tenant',
            'email' => 'presence-tenant@example.test',
        ]);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'username' => 'presence-employee',
            'email' => 'presence-employee@example.test',
        ]);

        Sanctum::actingAs($tenant);
        $this->postJson('/api/dashboard/presence/heartbeat')->assertNoContent();

        Sanctum::actingAs($employee);
        $this->postJson('/api/dashboard/presence/heartbeat')->assertNoContent();

        $snapshot = app(DashboardPresenceService::class)->snapshot(CarbonImmutable::now('UTC'));

        $this->assertTrue($snapshot['available']);
        $this->assertSame(2, $snapshot['online_users']);
        $this->assertSame(1, $snapshot['online_tenant_organizations']);
    }

    /** @test */
    public function expired_package_users_are_still_countable(): void
    {
        $this->app->instance(RedisFactory::class, new FakeDashboardPresenceRedisFactory());

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'presence-expired',
            'email' => 'presence-expired@example.test',
        ]);

        Sanctum::actingAs($tenant);

        $this->postJson('/api/dashboard/visit')->assertNoContent();
        $this->postJson('/api/dashboard/presence/heartbeat')->assertNoContent();
    }

    /** @test */
    public function inactive_or_impersonated_users_receive_forbidden(): void
    {
        $this->app->instance(RedisFactory::class, new FakeDashboardPresenceRedisFactory());

        $inactive = User::factory()->create([
            'account_type' => 'tenant',
            'active' => false,
            'username' => 'presence-inactive',
            'email' => 'presence-inactive@example.test',
        ]);

        Sanctum::actingAs($inactive);
        $this->postJson('/api/dashboard/presence/heartbeat')->assertForbidden();

        $impersonated = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'presence-impersonated',
            'email' => 'presence-impersonated@example.test',
        ]);
        $token = $impersonated->createToken('impersonated-by-admin-7')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/dashboard/presence/heartbeat')
            ->assertForbidden();
    }

    /** @test */
    public function redis_failures_return_a_stable_service_unavailable_response(): void
    {
        $this->app->instance(RedisFactory::class, new FakeDashboardPresenceRedisFactory(true));

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'presence-redis-down',
            'email' => 'presence-redis-down@example.test',
        ]);

        Sanctum::actingAs($tenant);

        $this->postJson('/api/dashboard/presence/heartbeat')
            ->assertStatus(503)
            ->assertJson([
                'code' => 'dashboard_presence_unavailable',
            ]);
    }

    /** @test */
    public function redis_failure_does_not_block_daily_visit_recording(): void
    {
        $this->app->instance(RedisFactory::class, new FakeDashboardPresenceRedisFactory(true));

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'presence-visit-isolation',
            'email' => 'presence-visit-isolation@example.test',
        ]);

        Sanctum::actingAs($tenant);

        $this->postJson('/api/dashboard/visit')->assertNoContent();

        $this->assertDatabaseCount('dashboard_daily_visits', 1);
        $this->assertSame(1, (int) DB::table('dashboard_daily_visits')->where('user_id', $tenant->id)->count());

        $this->postJson('/api/dashboard/presence/heartbeat')
            ->assertStatus(503)
            ->assertJson([
                'code' => 'dashboard_presence_unavailable',
            ]);
    }

    /** @test */
    public function production_heartbeat_limiter_enforces_ten_per_minute_and_is_disabled_outside_production(): void
    {
        $limiter = RateLimiter::limiter('dashboard_presence_heartbeat');
        $this->assertNotNull($limiter);

        $user = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'presence-limiter',
            'email' => 'presence-limiter@example.test',
        ]);

        $request = Request::create('/api/dashboard/presence/heartbeat', 'POST');
        $request->setUserResolver(fn () => $user);

        $previousEnv = $this->app['env'];

        try {
            $this->app['env'] = 'production';
            $productionLimit = $limiter($request);
            $this->assertInstanceOf(Limit::class, $productionLimit);
            $this->assertSame(10, $productionLimit->maxAttempts);
            $this->assertSame(60, $productionLimit->decayMinutes * 60);

            $this->app['env'] = 'testing';
            $nonProductionLimit = $limiter($request);
            $this->assertInstanceOf(Limit::class, $nonProductionLimit);
            $this->assertSame(PHP_INT_MAX, $nonProductionLimit->maxAttempts);
        } finally {
            $this->app['env'] = $previousEnv;
        }
    }
}
