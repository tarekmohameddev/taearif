<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class BuildingProjectIdTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_projects', 'buildings', 'api_permissions', 'api_model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }
    }

    private function grantPermissions(User $tenant, array $permissions): void
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

            $tenant->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    private function createProject(User $tenant): Project
    {
        return Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/test.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Test Developer',
            'units' => 10,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
        ]);
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

    public function test_create_without_project_id_sets_null(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/buildings', $this->validStorePayload());

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.project_id', null);

        $buildingId = (int) $response->json('data.id');
        $this->assertDatabaseHas('buildings', [
            'id' => $buildingId,
            'project_id' => null,
        ]);
    }

    public function test_create_with_valid_project_id_links_building(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $response = $this->postJson('/api/buildings', $this->validStorePayload([
            'name' => 'Linked Tower',
            'project_id' => $project->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.project_id', $project->id);

        $buildingId = (int) $response->json('data.id');
        $this->assertDatabaseHas('buildings', [
            'id' => $buildingId,
            'project_id' => $project->id,
        ]);
    }

    public function test_create_rejects_nonexistent_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/buildings', $this->validStorePayload([
            'project_id' => 999999999,
        ]));

        $response->assertStatus(422);
        $this->assertArrayHasKey('project_id', $response->json('errors'));
    }

    public function test_create_rejects_other_tenant_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $otherProject = $this->createProject($otherTenant);

        $response = $this->postJson('/api/buildings', $this->validStorePayload([
            'project_id' => $otherProject->id,
        ]));

        $response->assertStatus(422);
        $this->assertArrayHasKey('project_id', $response->json('errors'));
    }

    public function test_create_treats_empty_project_id_as_null(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/buildings', $this->validStorePayload([
            'project_id' => '',
        ]));

        $response->assertCreated()
            ->assertJsonPath('data.project_id', null);

        $buildingId = (int) $response->json('data.id');
        $this->assertDatabaseHas('buildings', [
            'id' => $buildingId,
            'project_id' => null,
        ]);
    }

    public function test_update_sets_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $building = Building::create([
            'name' => 'Unlinked Tower',
            'user_id' => $tenant->id,
        ]);

        $response = $this->putJson("/api/buildings/{$building->id}", [
            'project_id' => $project->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.project_id', $project->id);

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_update_clears_project_id_with_null(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $building = Building::create([
            'name' => 'Linked Tower',
            'user_id' => $tenant->id,
            'project_id' => $project->id,
        ]);

        $response = $this->putJson("/api/buildings/{$building->id}", [
            'project_id' => null,
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.project_id', null);

        $this->assertDatabaseHas('buildings', [
            'id' => $building->id,
            'project_id' => null,
        ]);
    }

    public function test_index_filters_by_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.view']);
        Sanctum::actingAs($tenant);

        $projectA = $this->createProject($tenant);
        $projectB = $this->createProject($tenant);

        $buildingA1 = Building::create([
            'name' => 'Project A Tower 1',
            'user_id' => $tenant->id,
            'project_id' => $projectA->id,
        ]);
        $buildingA2 = Building::create([
            'name' => 'Project A Tower 2',
            'user_id' => $tenant->id,
            'project_id' => $projectA->id,
        ]);
        Building::create([
            'name' => 'Project B Tower',
            'user_id' => $tenant->id,
            'project_id' => $projectB->id,
        ]);
        Building::create([
            'name' => 'Standalone Tower',
            'user_id' => $tenant->id,
            'project_id' => null,
        ]);

        $response = $this->getJson("/api/buildings?project_id={$projectA->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total', 2);

        $ids = collect($response->json('data.data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$buildingA1->id, $buildingA2->id], $ids);

        foreach ($response->json('data.data') as $row) {
            $this->assertSame($projectA->id, $row['project_id']);
        }
    }

    public function test_index_project_id_filter_returns_empty_for_other_tenant_project(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.view']);
        Sanctum::actingAs($tenant);

        $otherProject = $this->createProject($otherTenant);

        Building::create([
            'name' => 'Tenant Building',
            'user_id' => $tenant->id,
            'project_id' => null,
        ]);

        $response = $this->getJson("/api/buildings?project_id={$otherProject->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.total', 0);
    }

    public function test_index_unassigned_filter_returns_only_standalone_buildings(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['buildings.view']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $standalone = Building::create([
            'name' => 'Standalone Tower',
            'user_id' => $tenant->id,
            'project_id' => null,
        ]);
        Building::create([
            'name' => 'Linked Tower',
            'user_id' => $tenant->id,
            'project_id' => $project->id,
        ]);

        $response = $this->getJson('/api/buildings?unassigned=1');

        $response->assertOk()
            ->assertJsonPath('data.total', 1)
            ->assertJsonPath('data.data.0.id', $standalone->id)
            ->assertJsonPath('data.data.0.project_id', null);
    }
}
