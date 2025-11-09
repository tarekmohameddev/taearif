<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Domain\Billing\Models\Plan;
use App\Models\User as TenantUser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Feature\Admin\AdminApiTestCase;

class DashboardMetricsTest extends AdminApiTestCase
{
    /** @test */
    public function admin_can_view_dashboard_metrics(): void
    {
        $this->signInAdmin();

        $now = Carbon::now();

        $tenant = TenantUser::factory()->create([
            'created_at' => $now->copy()->subDays(10),
            'active' => true,
        ]);

        $plan = Plan::factory()->create();

        DB::table('memberships')->insert([
            'user_id' => $tenant->id,
            'package_id' => $plan->id,
            'price' => 199.99,
            'status' => 1,
            'is_trial' => false,
            'start_date' => $now->copy()->subDays(10)->toDateString(),
            'expire_date' => $now->copy()->addDays(3)->toDateString(),
            'created_at' => $now->copy()->subDays(5),
            'updated_at' => $now->copy()->subDays(5),
        ]);

        DB::table('user_properties')->insert([
            'user_id' => $tenant->id,
            'status' => 1,
            'is_active' => true,
            'created_at' => $now->copy()->subDays(15),
            'updated_at' => $now->copy()->subDays(15),
        ]);

        $response = $this->getJson(route('admin.api.dashboard'));

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'status',
                'message',
                'data' => [
                    'properties' => [
                        'total',
                        'active',
                        'inactive',
                        'change_percentage',
                        'period_days',
                    ],
                    'revenue' => [
                        'total',
                        'current_period',
                        'previous_period',
                        'change_percentage',
                        'monthly_trend',
                        'period_days',
                    ],
                    'users' => [
                        'total',
                        'active',
                        'inactive',
                        'new_this_month',
                        'change_percentage',
                        'monthly_trend',
                        'period_days',
                    ],
                    'subscriptions' => [
                        'active',
                        'expiring_soon',
                        'expired',
                        'trial',
                        'not_renewed',
                    ],
                ],
                'meta',
            ])
            ->assertJsonPath('data.properties.total', 1)
            ->assertJsonPath('data.revenue.total', 199.99)
            ->assertJsonPath('data.users.total', 1)
            ->assertJsonPath('data.subscriptions.active', 1)
            ->assertJsonPath('data.subscriptions.expiring_soon', 1);
    }

    /** @test */
    public function admin_can_filter_dashboard_metrics_by_type(): void
    {
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.dashboard', [
            'metric' => 'revenue',
            'period' => 7,
        ]));

        $response->assertOk()
            ->assertJsonMissingPath('data.properties')
            ->assertJsonMissingPath('data.users')
            ->assertJsonMissingPath('data.subscriptions')
            ->assertJsonStructure([
                'data' => [
                    'revenue' => [
                        'total',
                        'current_period',
                        'previous_period',
                        'change_percentage',
                        'monthly_trend',
                        'period_days',
                    ],
                ],
            ]);
    }

    /** @test */
    public function dashboard_metric_with_invalid_type_returns_validation_error(): void
    {
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.dashboard', [
            'metric' => 'invalid',
        ]));

        $response->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', 'Invalid metric. Valid values: properties, revenue, users, subscriptions');
    }

    /** @test */
    public function dashboard_metric_with_invalid_period_returns_validation_error(): void
    {
        $this->signInAdmin();

        $response = $this->getJson(route('admin.api.dashboard', [
            'period' => 400,
        ]));

        $response->assertStatus(400)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 400)
            ->assertJsonPath('message', 'Period must be between 1 and 365 days');
    }

    /** @test */
    public function dashboard_metrics_require_authentication(): void
    {
        $this->getJson(route('admin.api.dashboard'))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Authentication required');
    }

    /** @test */
    public function admin_can_view_quick_stats(): void
    {
        $this->signInAdmin();

        $now = Carbon::now();

        $tenant = TenantUser::factory()->create([
            'created_at' => $now,
            'active' => true,
        ]);

        DB::table('memberships')->insert([
            'user_id' => $tenant->id,
            'package_id' => null,
            'price' => 99.50,
            'status' => 1,
            'is_trial' => false,
            'start_date' => $now->toDateString(),
            'expire_date' => $now->copy()->addMonth()->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('user_properties')->insert([
            'user_id' => $tenant->id,
            'status' => 1,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $response = $this->getJson(route('admin.api.dashboard.quick-stats'));

        $response->assertOk()
            ->assertJsonPath('data.total_users', 1)
            ->assertJsonPath('data.active_subscriptions', 1)
            ->assertJsonPath('data.total_revenue', 99.50)
            ->assertJsonPath('data.total_properties', 1);
    }

    /** @test */
    public function quick_stats_require_authentication(): void
    {
        $this->getJson(route('admin.api.dashboard.quick-stats'))
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Authentication required');
    }
}


