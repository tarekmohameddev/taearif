<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Audit\EntityAuditLog;
use App\Models\Building;
use App\Models\BuildingMeter;
use App\Models\User;
use App\Support\AuditContext;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BuildingCrudTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'buildings', 'building_meters', 'api_permissions', 'api_model_has_permissions', 'entity_audit_logs'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Tower ' . Str::random(6),
        ], $overrides);
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

    public function test_create_with_deed_owner_and_meters(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/buildings', $this->validStorePayload([
            'deed_number' => '1234567890',
            'deed_image' => 'buildings/deeds/deed_123.pdf',
            'owner_name' => 'أحمد محمد',
            'owner_phone' => '0500000000',
            'water_meter_numbers' => ['WM-100'],
            'electricity_meter_numbers' => ['EM-200'],
        ]));

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.deed_number', '1234567890')
            ->assertJsonPath('data.deed_image', 'buildings/deeds/deed_123.pdf')
            ->assertJsonPath('data.owner_name', 'أحمد محمد')
            ->assertJsonPath('data.owner_phone', '0500000000')
            ->assertJsonPath('data.user_id', $tenant->id);

        $buildingId = (int) $response->json('data.id');

        $this->assertDatabaseHas('buildings', [
            'id' => $buildingId,
            'user_id' => $tenant->id,
            'deed_number' => '1234567890',
            'owner_name' => 'أحمد محمد',
        ]);

        $this->assertDatabaseHas('building_meters', [
            'building_id' => $buildingId,
            'meter_type' => BuildingMeter::TYPE_WATER,
            'meter_number' => 'WM-100',
        ]);
        $this->assertDatabaseHas('building_meters', [
            'building_id' => $buildingId,
            'meter_type' => BuildingMeter::TYPE_ELECTRICITY,
            'meter_number' => 'EM-200',
        ]);
    }

    public function test_show_returns_deed_owner_and_meters(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.view']);
        Sanctum::actingAs($tenant);

        $building = Building::create([
            'name' => 'Show Tower',
            'user_id' => $tenant->id,
            'deed_number' => 'DEED-99',
            'owner_name' => 'Owner Name',
            'owner_phone' => '0511111111',
        ]);
        BuildingMeter::create([
            'building_id' => $building->id,
            'meter_type' => BuildingMeter::TYPE_WATER,
            'meter_number' => 'WM-SHOW',
        ]);

        $response = $this->getJson("/api/buildings/{$building->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.deed_number', 'DEED-99')
            ->assertJsonPath('data.owner_name', 'Owner Name')
            ->assertJsonPath('data.owner_phone', '0511111111');

        $meters = collect($response->json('data.meters'));
        $this->assertTrue($meters->contains('meter_number', 'WM-SHOW'));
    }

    public function test_update_deed_number_and_replaces_meters(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.update']);
        Sanctum::actingAs($tenant);

        $building = Building::create([
            'name' => 'Update Tower',
            'user_id' => $tenant->id,
            'deed_number' => 'OLD-DEED',
        ]);
        BuildingMeter::create([
            'building_id' => $building->id,
            'meter_type' => BuildingMeter::TYPE_WATER,
            'meter_number' => 'WM-OLD',
        ]);

        $response = $this->putJson("/api/buildings/{$building->id}", [
            'deed_number' => 'NEW-DEED',
            'owner_name' => 'Updated Owner',
            'water_meter_numbers' => ['WM-NEW'],
            'electricity_meter_numbers' => ['EM-NEW'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.deed_number', 'NEW-DEED')
            ->assertJsonPath('data.owner_name', 'Updated Owner');

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'deed_number' => 'NEW-DEED',
            'owner_name' => 'Updated Owner',
        ]);
        $this->assertDatabaseMissing('building_meters', [
            'building_id' => $building->id,
            'meter_number' => 'WM-OLD',
        ]);
        $this->assertDatabaseHas('building_meters', [
            'building_id' => $building->id,
            'meter_number' => 'WM-NEW',
        ]);
        $this->assertDatabaseHas('building_meters', [
            'building_id' => $building->id,
            'meter_number' => 'EM-NEW',
        ]);
    }

    public function test_upload_deed_image_returns_path(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $file = UploadedFile::fake()->create('deed.pdf', 20, 'application/pdf');

        $response = $this->post('/api/buildings/upload-deed-image', [
            'deed_image' => $file,
        ], ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure(['data' => ['deed_image']]);

        $this->assertStringStartsWith('buildings/deeds/', $response->json('data.deed_image'));
    }

    public function test_archive_via_delete_sets_is_archived(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.delete']);
        Sanctum::actingAs($tenant);

        $building = Building::create([
            'name' => 'Archive Tower',
            'user_id' => $tenant->id,
            'is_archived' => false,
        ]);

        $this->deleteJson("/api/buildings/{$building->id}")
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Building archived successfully');

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'is_archived' => true,
        ]);
    }

    public function test_archive_logs_is_archived_field_change(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.delete']);
        Sanctum::actingAs($tenant);

        $building = Building::create([
            'name' => 'Audit Archive Tower',
            'user_id' => $tenant->id,
            'is_archived' => false,
        ]);

        AuditContext::set($tenant->id, 'tenant', $tenant->id, '127.0.0.1', 'test');

        $this->deleteJson("/api/buildings/{$building->id}")->assertOk();

        $row = EntityAuditLog::where('entity_type', 'building')
            ->where('entity_id', $building->id)
            ->where('field_name', 'is_archived')
            ->first();

        $this->assertNotNull($row);
        $this->assertSame('updated', $row->action);
        $this->assertSame('0', $row->old_value);
        $this->assertSame('1', $row->new_value);

        $this->assertNull(
            EntityAuditLog::where('entity_type', 'building')
                ->where('entity_id', $building->id)
                ->where('action', 'deleted')
                ->first()
        );
    }

    public function test_archived_buildings_excluded_from_default_list(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.view']);
        Sanctum::actingAs($tenant);

        $active = Building::create([
            'name' => 'Active Tower',
            'user_id' => $tenant->id,
            'is_archived' => false,
        ]);
        Building::create([
            'name' => 'Archived Tower',
            'user_id' => $tenant->id,
            'is_archived' => true,
        ]);

        $response = $this->getJson('/api/buildings');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $active->id);
    }

    public function test_archived_buildings_visible_with_is_archived_filter(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, $tenant, ['buildings.view']);
        Sanctum::actingAs($tenant);

        Building::create([
            'name' => 'Active Tower',
            'user_id' => $tenant->id,
            'is_archived' => false,
        ]);
        $archived = Building::create([
            'name' => 'Archived Tower',
            'user_id' => $tenant->id,
            'is_archived' => true,
        ]);

        $response = $this->getJson('/api/buildings?is_archived=1');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $archived->id);
    }

    public function test_employee_create_sets_user_id_to_tenant_owner(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['buildings.create']);
        Sanctum::actingAs($employee);

        $response = $this->postJson('/api/buildings', $this->validStorePayload([
            'name' => 'Employee Created Tower',
        ]));

        $response->assertCreated();

        $buildingId = (int) $response->json('data.id');
        $this->assertDatabaseHas('buildings', [
            'id' => $buildingId,
            'user_id' => $tenant->id,
        ]);
        $this->assertNotEquals($employee->id, $response->json('data.user_id'));
    }

    public function test_employee_with_permission_can_update_tenant_building(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $this->grantPermissions($employee, $tenant, ['buildings.update']);
        Sanctum::actingAs($employee);

        $building = Building::create([
            'name' => 'Tenant Tower',
            'user_id' => $tenant->id,
        ]);

        $this->putJson("/api/buildings/{$building->id}", [
            'name' => 'Updated By Employee',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Updated By Employee');

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'name' => 'Updated By Employee',
        ]);
    }

    public function test_employee_without_permission_cannot_create_building(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $this->postJson('/api/buildings', $this->validStorePayload())
            ->assertForbidden();
    }

    public function test_employee_without_permission_cannot_view_building(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $building = Building::create([
            'name' => 'Hidden Tower',
            'user_id' => $tenant->id,
        ]);

        $this->getJson("/api/buildings/{$building->id}")
            ->assertForbidden();
    }

    public function test_employee_without_permission_cannot_update_building(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $building = Building::create([
            'name' => 'Protected Tower',
            'user_id' => $tenant->id,
        ]);

        $this->putJson("/api/buildings/{$building->id}", ['name' => 'Hacked'])
            ->assertForbidden();
    }

    public function test_employee_without_permission_cannot_archive_building(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $building = Building::create([
            'name' => 'No Delete Tower',
            'user_id' => $tenant->id,
        ]);

        $this->deleteJson("/api/buildings/{$building->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'is_archived' => false,
        ]);
    }
}
