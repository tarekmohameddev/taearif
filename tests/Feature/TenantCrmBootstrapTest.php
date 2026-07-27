<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserPropertyRequest;
use App\Models\ApiCustomer;
use App\Models\User;
use App\Services\Matching\MatchingService;
use App\Services\Matching\RequestCompletenessService;
use App\Services\TenantCrmBootstrapService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TenantCrmBootstrapTest extends TestCase
{
    use DatabaseTransactions;

    private function requireTables(): void
    {
        if (!Schema::hasTable('users_api_customers_stages')) {
            $this->markTestSkipped('users_api_customers_stages table required.');
        }
        if (!Schema::hasTable('property_request_auto_customer_settings')) {
            $this->markTestSkipped('property_request_auto_customer_settings table required.');
        }
        if (!Schema::hasTable('api_customers')) {
            $this->markTestSkipped('api_customers table required.');
        }
        if (!Schema::hasTable('users_property_requests')) {
            $this->markTestSkipped('users_property_requests table required.');
        }
    }

    private function createTenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'tenant_id' => null,
        ]);
    }

    /** @test */
    public function creating_a_tenant_seeds_crm_stages_and_auto_customer_settings(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        $stages = UserApiCustomerStage::where('user_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        $this->assertCount(3, $stages);
        $this->assertEquals(
            array_column(TenantCrmBootstrapService::defaultStages(), 'stage_name'),
            $stages->pluck('stage_name')->all()
        );

        $settings = PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->first();
        $this->assertNotNull($settings);
        $this->assertTrue($settings->auto_create_customer);
        $this->assertNotNull($settings->default_stage_id);
        $this->assertEquals($stages->first()->id, $settings->default_stage_id);
    }

    /** @test */
    public function property_request_for_fresh_tenant_auto_creates_customer(): void
    {
        $this->requireTables();

        $this->mock(MatchingService::class, function ($mock) {
            $mock->shouldReceive('generateMatchesForRequest')->andReturn([]);
        });
        $this->mock(RequestCompletenessService::class, function ($mock) {
            $mock->shouldReceive('validate')->andReturn([
                'is_complete' => false,
                'missing_fields' => [],
            ]);
        });

        $tenant = $this->createTenant();

        $pr = UserPropertyRequest::create([
            'full_name' => 'Auto Customer Lead',
            'phone' => '+966501234567',
            'user_id' => $tenant->id,
            'is_active' => 1,
        ]);

        $pr->refresh();

        $this->assertNotNull($pr->customer_id);

        $customer = ApiCustomer::find($pr->customer_id);
        $this->assertNotNull($customer);
        $this->assertEquals($tenant->id, $customer->user_id);
        $this->assertEquals('property_request', $customer->source);
        $this->assertEquals('Auto Customer Lead', $customer->name);
        $this->assertEquals('966501234567', $customer->phone_number);
    }

    /** @test */
    public function ensure_for_tenant_is_idempotent(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();
        $bootstrap = app(TenantCrmBootstrapService::class);

        $bootstrap->ensureForTenant((int) $tenant->id);
        $bootstrap->ensureForTenant((int) $tenant->id);

        $this->assertEquals(
            3,
            UserApiCustomerStage::where('user_id', $tenant->id)->where('is_active', true)->count()
        );
        $this->assertEquals(
            1,
            PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->count()
        );

        $settings = PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->first();
        $this->assertTrue($settings->auto_create_customer);
    }

    /** @test */
    public function ensure_for_tenant_re_enables_auto_create_when_false(): void
    {
        $this->requireTables();

        $tenant = $this->createTenant();

        PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->update([
            'auto_create_customer' => false,
        ]);

        app(TenantCrmBootstrapService::class)->ensureForTenant((int) $tenant->id);

        $settings = PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->first();
        $this->assertNotNull($settings);
        $this->assertTrue($settings->auto_create_customer);
        $this->assertNotNull($settings->default_stage_id);
    }

    /** @test */
    public function property_request_settings_get_reports_auto_create_enabled_when_no_row(): void
    {
        $this->requireTables();

        if (!Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table required for crm.view.');
        }

        $tenant = $this->createTenant();
        PropertyRequestAutoCustomerSetting::where('user_id', $tenant->id)->delete();

        $this->grantCrmView($tenant);
        Sanctum::actingAs($tenant);

        $res = $this->getJson('/api/crm/property-requests/settings');

        $res->assertOk();
        $res->assertJsonPath('data.settings.auto_create_customer', true);
        $res->assertJsonPath('data.settings.default_stage_id', null);
    }

    private function grantCrmView(User $tenant): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        try {
            $permission = Permission::findByName('crm.view', 'sanctum');
        } catch (\Throwable $e) {
            $permission = Permission::create([
                'name' => 'crm.view',
                'guard_name' => 'sanctum',
            ]);
        }

        $tenant->givePermissionTo($permission);
    }
}
