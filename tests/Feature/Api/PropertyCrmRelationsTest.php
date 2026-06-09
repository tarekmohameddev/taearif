<?php

namespace Tests\Feature\Api;

use App\Models\Api\Crm\CrmRequest;
use App\Models\Api\UserApiCustomerStage;
use App\Models\ApiCustomer;
use App\Models\Property\PropertyCrmRelation;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertyCrmRelationsTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! Schema::hasTable('property_crm_relations')) {
            $this->markTestSkipped('property_crm_relations table not available.');
        }
    }

    public function test_summary_returns_three_counters(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $request = $this->createCrmRequest($user->id);

        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_AI_MATCHED,
            'occurred_at' => now(),
        ]);
        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_MANUALLY_ADDED,
            'employee_id' => $user->id,
            'occurred_at' => now(),
        ]);
        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_SENT_TO_CUSTOMER,
            'employee_id' => $user->id,
            'occurred_at' => now(),
        ]);

        $this->getJson("/api/properties/{$property->id}/crm-relations/summary")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.ai_matched', 1)
            ->assertJsonPath('data.manually_added', 1)
            ->assertJsonPath('data.sent_to_customer', 1);
    }

    public function test_legacy_crm_counters_route_matches_summary(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->getJson("/api/properties/{$property->id}/crm-counters")
            ->assertOk()
            ->assertJsonPath('data.ai_matched', 0)
            ->assertJsonPath('data.manually_added', 0)
            ->assertJsonPath('data.sent_to_customer', 0);
    }

    public function test_summary_forbidden_without_view_permission(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->getJson("/api/properties/{$property->id}/crm-relations/summary")
            ->assertForbidden();
    }

    public function test_summary_returns_404_for_other_tenant_property(): void
    {
        $user = $this->actingAsTenant();
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($otherTenant->id);

        $this->getJson("/api/properties/{$property->id}/crm-relations/summary")
            ->assertNotFound();
    }

    public function test_list_returns_paginated_relations(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $request = $this->createCrmRequest($user->id);

        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_AI_MATCHED,
            'occurred_at' => now()->subDay(),
        ]);
        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_MANUALLY_ADDED,
            'employee_id' => $user->id,
            'occurred_at' => now(),
        ]);

        $this->getJson("/api/properties/{$property->id}/crm-relations?per_page=10")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('data.0.relation_type', PropertyCrmRelation::TYPE_MANUALLY_ADDED);
    }

    public function test_list_filters_by_relation_type(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $request = $this->createCrmRequest($user->id);

        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_AI_MATCHED,
            'occurred_at' => now(),
        ]);
        PropertyCrmRelation::create([
            'property_id' => $property->id,
            'request_id' => $request->id,
            'relation_type' => PropertyCrmRelation::TYPE_MANUALLY_ADDED,
            'employee_id' => $user->id,
            'occurred_at' => now(),
        ]);

        $this->getJson("/api/properties/{$property->id}/crm-relations?relation_type=ai_matched")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.relation_type', 'ai_matched')
            ->assertJsonPath('meta.total', 1);
    }

    public function test_list_rejects_invalid_relation_type(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->getJson("/api/properties/{$property->id}/crm-relations?relation_type=invalid")
            ->assertStatus(422)
            ->assertJsonValidationErrors(['relation_type']);
    }

    public function test_list_forbidden_without_view_permission(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->getJson("/api/properties/{$property->id}/crm-relations")
            ->assertForbidden();
    }

    public function test_property_crm_relations_table_supports_entity_fields(): void
    {
        $this->assertTrue(Schema::hasTable('property_crm_relations'));

        foreach ([
            'property_id',
            'request_id',
            'relation_type',
            'employee_id',
            'customer_id',
            'occurred_at',
            'metadata',
        ] as $column) {
            $this->assertTrue(
                Schema::hasColumn('property_crm_relations', $column),
                "Missing column: {$column}"
            );
        }
    }

    public function test_manual_add_creates_manually_added_and_sent_to_customer_relations(): void
    {
        if (! Schema::hasTable('crm_requests')) {
            $this->markTestSkipped('crm_requests table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $crmRequest = $this->createCrmRequest($user->id);

        $this->postJson("/api/properties/{$property->id}/crm-relations", [
            'request_id' => $crmRequest->id,
            'customer_id' => $crmRequest->customer_id,
        ])->assertCreated()
            ->assertJsonPath('data.relation_type', PropertyCrmRelation::TYPE_MANUALLY_ADDED)
            ->assertJsonPath('data.request_id', $crmRequest->id)
            ->assertJsonPath('data.employee.id', $user->id);

        $this->assertDatabaseHas('property_crm_relations', [
            'property_id' => $property->id,
            'request_id' => $crmRequest->id,
            'relation_type' => PropertyCrmRelation::TYPE_MANUALLY_ADDED,
            'employee_id' => $user->id,
        ]);

        $this->assertDatabaseHas('property_crm_relations', [
            'property_id' => $property->id,
            'request_id' => $crmRequest->id,
            'relation_type' => PropertyCrmRelation::TYPE_SENT_TO_CUSTOMER,
            'employee_id' => $user->id,
        ]);

        $this->assertSame(
            $property->id,
            (int) CrmRequest::find($crmRequest->id)->property_id
        );
    }

    public function test_duplicate_manual_add_returns_conflict(): void
    {
        if (! Schema::hasTable('crm_requests')) {
            $this->markTestSkipped('crm_requests table not available.');
        }

        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);
        $crmRequest = $this->createCrmRequest($user->id);

        $payload = ['request_id' => $crmRequest->id];

        $this->postJson("/api/properties/{$property->id}/crm-relations", $payload)->assertCreated();

        $this->postJson("/api/properties/{$property->id}/crm-relations", $payload)
            ->assertStatus(409);

        $this->assertSame(
            1,
            PropertyCrmRelation::query()
                ->where('property_id', $property->id)
                ->where('request_id', $crmRequest->id)
                ->where('relation_type', PropertyCrmRelation::TYPE_MANUALLY_ADDED)
                ->count()
        );
    }

    public function test_manual_add_records_employee_from_authenticated_user(): void
    {
        if (! Schema::hasTable('crm_requests') || ! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('crm_requests or api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'first_name' => 'Sara',
            'last_name' => 'Agent',
        ]);

        $this->grantPermissions($employee, $tenant, ['properties.update', 'properties.view']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);
        $crmRequest = $this->createCrmRequest($tenant->id);

        $this->postJson("/api/properties/{$property->id}/crm-relations", [
            'request_id' => $crmRequest->id,
        ])->assertCreated()
            ->assertJsonPath('data.employee.id', $employee->id);

        $this->getJson("/api/properties/{$property->id}/crm-relations?relation_type=manually_added")
            ->assertOk()
            ->assertJsonPath('data.0.employee.id', $employee->id);
    }

    public function test_manual_add_forbidden_without_update_permission(): void
    {
        if (! Schema::hasTable('crm_requests') || ! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('crm_requests or api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);
        $crmRequest = $this->createCrmRequest($tenant->id);

        $this->postJson("/api/properties/{$property->id}/crm-relations", [
            'request_id' => $crmRequest->id,
        ])->assertForbidden();
    }

    public function test_employee_with_view_permission_can_access_summary_and_list(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['properties.view']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->getJson("/api/properties/{$property->id}/crm-relations/summary")->assertOk();
        $this->getJson("/api/properties/{$property->id}/crm-relations")->assertOk();
    }

    private function actingAsTenant(): User
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Sanctum::actingAs($user);

        return $user;
    }

    private function createProperty(int $userId): Property
    {
        return Property::create([
            'user_id' => $userId,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ]);
    }

    private function createCrmRequest(int $userId): CrmRequest
    {
        if (! Schema::hasTable('crm_requests')) {
            $this->markTestSkipped('crm_requests table not available.');
        }

        $stage = UserApiCustomerStage::create([
            'user_id' => $userId,
            'stage_name' => 'New',
            'order' => 1,
            'is_active' => true,
        ]);

        $customer = ApiCustomer::create([
            'user_id' => $userId,
            'name' => 'CRM Customer',
            'phone_number' => '+9665' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
        ]);

        return CrmRequest::create([
            'user_id' => $userId,
            'stage_id' => $stage->id,
            'customer_id' => $customer->id,
            'position' => 0,
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
    {
        $registrar = app(PermissionRegistrar::class);
        $registrar->setPermissionsTeamId((int) $tenant->id);
        $registrar->forgetCachedPermissions();

        foreach ($permissions as $permissionName) {
            try {
                $permission = Permission::findByName($permissionName, 'sanctum');
            } catch (\Throwable $e) {
                $permission = Permission::create([
                    'name' => $permissionName,
                    'guard_name' => 'sanctum',
                    'team_id' => $tenant->id,
                ]);
            }

            $user->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }
}
