<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Dashboard;

use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Models\Role;
use App\Http\Middleware\RequireActiveMembership;
use App\Http\Middleware\SetTenantForPermissions;
use App\Models\User;
use App\Services\Analytics\DashboardVisitService;
use App\Services\Admin\AdminDashboardMetricsService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Admin\AdminApiTestCase;

class DashboardBusinessMetricsParityTest extends AdminApiTestCase
{
    /** @test */
    public function tenant_dashboard_visit_endpoint_deduplicates_per_riyadh_day_and_rolls_over_next_day(): void
    {
        $this->withoutMiddleware(RequireActiveMembership::class);
        $this->withoutMiddleware(SetTenantForPermissions::class);

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'visit-owner',
            'email' => 'visit-owner@example.test',
        ]);

        Sanctum::actingAs($tenant);

        Carbon::setTestNow(CarbonImmutable::parse('2026-09-06 23:50:00', 'Asia/Riyadh'));
        $this->postJson('/api/dashboard/visit')->assertNoContent();
        $this->postJson('/api/dashboard/visit')->assertNoContent();

        $this->assertDatabaseCount('dashboard_daily_visits', 1);
        $this->assertSame(2, (int) DB::table('dashboard_daily_visits')->value('visits_count'));

        app(DashboardVisitService::class)->recordFor(
            $tenant,
            CarbonImmutable::parse('2026-09-07 00:05:00', 'Asia/Riyadh')
        );

        $this->assertDatabaseCount('dashboard_daily_visits', 2);
    }

    /** @test */
    public function business_metrics_count_unique_users_and_unique_tenants_from_daily_visits(): void
    {
        $clock = CarbonImmutable::parse('2026-09-06 10:00:00', 'Asia/Riyadh');
        Carbon::setTestNow($clock);

        $this->withoutMiddleware(RequireActiveMembership::class);
        $this->withoutMiddleware(SetTenantForPermissions::class);

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'tenant-a',
            'email' => 'tenant-a@example.test',
        ]);
        $employeeOne = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'username' => 'employee-one',
            'email' => 'employee-one@example.test',
        ]);
        $employeeTwo = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'username' => 'employee-two',
            'email' => 'employee-two@example.test',
        ]);

        foreach ([$employeeOne, $employeeTwo] as $user) {
            Sanctum::actingAs($user);
            $this->postJson('/api/dashboard/visit')->assertNoContent();
        }

        $role = Role::factory()->create(['permissions' => ['Dashboard', 'Registered Users']]);
        $admin = Admin::factory()->create(['role_id' => $role->id, 'status' => true]);

        $dashboard = app(AdminDashboardMetricsService::class)->build($admin, $clock);

        $this->assertSame(2, $dashboard['executiveSummary']['uniqueDashboardUsersToday']);
        $this->assertSame(1, $dashboard['executiveSummary']['uniqueTenantsOpenedDashboardToday']);
    }

    /** @test */
    public function blade_and_admin_api_share_the_same_business_metrics_snapshot(): void
    {
        $clock = CarbonImmutable::parse('2026-09-06 14:30:00', 'Asia/Riyadh');
        Carbon::setTestNow($clock);

        $tenant = User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'parity-tenant',
            'email' => 'parity-tenant@example.test',
        ]);

        DB::table('user_properties')->insert([
            'user_id' => $tenant->id,
            'project_id' => 100,
            'price' => 700000.10,
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'purpose' => 'sale',
            'property_type' => 'apartment',
            'area' => 120,
            'status' => 1,
            'featured' => false,
            'created_at' => $clock,
            'updated_at' => $clock,
        ]);

        DB::table('sales')->insert([
            'property_id' => 1,
            'user_id' => $tenant->id,
            'sale_price' => 500000.40,
            'sale_date' => $clock,
            'status' => 'completed',
            'created_at' => $clock,
            'updated_at' => $clock,
        ]);

        DB::table('rm_rentals')->insert([
            'user_id' => $tenant->id,
            'tenant_full_name' => 'Parity Tenant',
            'tenant_phone' => '500000003',
            'currency' => 'SAR',
            'total_rental_amount' => 25000.90,
            'status' => 'active',
            'created_at' => $clock,
            'updated_at' => $clock,
        ]);

        $admin = $this->signInAdmin();
        $bladeDashboard = app(AdminDashboardMetricsService::class)->build($admin, $clock);

        $response = $this->getJson(route('admin.api.dashboard'));
        $response->assertOk();

        $apiBusinessMetrics = $response->json('data.business_metrics');

        $this->assertSame(
            $bladeDashboard['executiveSummary']['registeredTenantUsers'],
            $apiBusinessMetrics['executive_summary']['registeredTenantUsers']
        );
        $this->assertSame(
            $bladeDashboard['executiveSummary']['activePaidSubscriberUsers'],
            $apiBusinessMetrics['executive_summary']['activePaidSubscriberUsers']
        );
        $this->assertSame(
            $bladeDashboard['financialMetrics']['projectInventoryValue']['amount'],
            $apiBusinessMetrics['financial_metrics']['projectInventoryValue']['amount']
        );
        $this->assertSame(
            $bladeDashboard['financialMetrics']['completedSalesValue']['amount'],
            $apiBusinessMetrics['financial_metrics']['completedSalesValue']['amount']
        );
    }
}
