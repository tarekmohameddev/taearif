<?php

namespace Tests\Feature\Api;

use App\Models\Audit\EntityAuditLog;
use App\Models\Building;
use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Support\AuditContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class EntityAuditLogTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['users', 'user_properties', 'user_projects', 'buildings', 'entity_audit_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    public function test_property_update_logs_sensitive_field(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id, ['deed_number' => 'OLD-DEED']);

        AuditContext::set($user->id, 'tenant', $user->id, '127.0.0.1', 'test');
        $property->deed_number = 'NEW-DEED';
        $property->save();

        $row = EntityAuditLog::where('entity_type', 'property')
            ->where('entity_id', $property->id)
            ->where('field_name', 'deed_number')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('OLD-DEED', $row->old_value);
        $this->assertSame('NEW-DEED', $row->new_value);
        $this->assertSame('updated', $row->action);
    }

    public function test_status_change_logs_all_status_fields(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Deal closed',
        ])->assertOk();

        $statusRows = EntityAuditLog::where('entity_type', 'property')
            ->where('entity_id', $property->id)
            ->where('action', 'status_change')
            ->where('field_name', 'unit_status')
            ->get();

        $this->assertCount(1, $statusRows);
        $this->assertSame('available', $statusRows->first()->old_value);
        $this->assertSame('sold', $statusRows->first()->new_value);
        $this->assertSame('Deal closed', $statusRows->first()->reason);

        $this->assertNull(
            EntityAuditLog::where('entity_type', 'property')
                ->where('entity_id', $property->id)
                ->where('action', 'updated')
                ->first()
        );
    }

    public function test_project_update_logs_field_row(): void
    {
        $user = $this->actingAsTenant();
        $project = Project::create([
            'user_id' => $user->id,
            'featured' => false,
            'published' => true,
            'units' => 10,
        ]);

        AuditContext::set($user->id, 'tenant', $user->id, '127.0.0.1', 'test');
        $project->units = 25;
        $project->save();

        $row = EntityAuditLog::where('entity_type', 'project')
            ->where('entity_id', $project->id)
            ->where('field_name', 'units')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('10', $row->old_value);
        $this->assertSame('25', $row->new_value);
    }

    public function test_building_create_and_update_log_rows(): void
    {
        $user = $this->actingAsTenant();

        AuditContext::set($user->id, 'tenant', $user->id, '127.0.0.1', 'test');
        $building = Building::create([
            'name' => 'Tower A',
            'user_id' => $user->id,
        ]);

        $this->assertNotNull(
            EntityAuditLog::where('entity_type', 'building')
                ->where('entity_id', $building->id)
                ->where('action', 'created')
                ->first()
        );

        $building->name = 'Tower B';
        $building->save();

        $row = EntityAuditLog::where('entity_type', 'building')
            ->where('entity_id', $building->id)
            ->where('field_name', 'name')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('Tower A', $row->old_value);
        $this->assertSame('Tower B', $row->new_value);
    }

    public function test_employee_without_permission_cannot_view_property_audit_logs(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        $this->getJson("/api/properties/{$property->id}/audit-logs")
            ->assertForbidden();
    }

    public function test_employee_with_permission_can_view_property_audit_logs(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['properties.view_audit_log']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id);

        AuditContext::set($employee->id, 'employee', $tenant->id, '127.0.0.1', 'test');
        $property->price = 500000;
        $property->save();

        $this->getJson("/api/properties/{$property->id}/audit-logs")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['logs', 'pagination']]);
    }

    public function test_tenant_owner_can_view_audit_logs_without_explicit_permission(): void
    {
        $user = $this->actingAsTenant();
        $property = $this->createProperty($user->id);

        $this->patchJson("/api/properties/{$property->id}/status", [
            'unit_status' => 'sold',
            'reason' => 'Owner review',
        ])->assertOk();

        $this->getJson("/api/properties/{$property->id}/audit-logs")
            ->assertOk()
            ->assertJsonFragment(['reason' => 'Owner review', 'field_name' => 'unit_status']);
    }

    public function test_cross_tenant_cannot_read_other_tenant_audit_logs(): void
    {
        $user = $this->actingAsTenant();
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $property = $this->createProperty($otherTenant->id);

        $this->getJson("/api/properties/{$property->id}/audit-logs")
            ->assertNotFound();
    }

    public function test_project_audit_logs_endpoint_returns_normalized_rows(): void
    {
        $user = $this->actingAsTenant();
        $project = Project::create([
            'user_id' => $user->id,
            'units' => 5,
        ]);

        AuditContext::set($user->id, 'tenant', $user->id, '127.0.0.1', 'test');
        $project->units = 8;
        $project->save();

        $this->getJson("/api/projects/{$project->id}/audit-logs")
            ->assertOk()
            ->assertJsonFragment([
                'entity_type' => 'project',
                'field_name' => 'units',
                'old_value' => '5',
                'new_value' => '8',
            ]);
    }

    public function test_building_audit_logs_endpoint_returns_normalized_rows(): void
    {
        $user = $this->actingAsTenant();

        AuditContext::set($user->id, 'tenant', $user->id, '127.0.0.1', 'test');
        $building = Building::create([
            'name' => 'Block 1',
            'user_id' => $user->id,
        ]);

        $this->getJson("/api/buildings/{$building->id}/audit-logs")
            ->assertOk()
            ->assertJsonFragment([
                'entity_type' => 'building',
                'action' => 'created',
            ]);
    }

    private function actingAsTenant(): User
    {
        $user = User::factory()->create(['account_type' => 'tenant']);
        Sanctum::actingAs($user);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProperty(int $userId, array $overrides = []): Property
    {
        return Property::create(array_merge([
            'user_id' => $userId,
            'price' => 1,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'x.jpg',
            'property_type' => 'apartment',
        ], $overrides));
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
