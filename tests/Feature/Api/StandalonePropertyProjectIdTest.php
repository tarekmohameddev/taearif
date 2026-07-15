<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class StandalonePropertyProjectIdTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_projects', 'user_properties', 'user_property_contents', 'api_permissions', 'api_model_has_permissions', 'memberships', 'packages', 'user_languages'] as $table) {
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

    private function seedTenantContext(User $tenant): void
    {
        $package = Package::firstOrCreate(
            ['title' => 'Standalone Property Project Id Test Package'],
            [
                'slug' => 'standalone-property-project-id-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => 100,
                'serial_number' => 997,
            ]
        );

        $membership = Membership::firstOrNew(['user_id' => $tenant->id]);
        $membership->status = 1;
        $membership->start_date = now()->subDay();
        $membership->expire_date = now()->addMonth();
        $membership->package_id = $package->id;
        $membership->price = 0;
        $membership->currency = 'USD';
        $membership->currency_symbol = '$';
        $membership->payment_method = 'test';
        $membership->transaction_id = 'standalone-project-id-' . uniqid();
        $membership->save();

        MembershipCacheService::clearCache($tenant->id);

        Language::firstOrCreate(
            ['user_id' => $tenant->id, 'is_default' => 1],
            [
                'name' => 'Arabic',
                'code' => 'ar',
                'rtl' => 1,
            ]
        );

        ApiUserCategory::firstOrCreate(
            ['slug' => 'other'],
            [
                'name' => 'Other',
                'type' => 'property',
                'is_active' => 1,
            ]
        );
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
            'title' => 'Standalone Unit',
            'address' => 'Tower A',
            'description' => 'Unit description for standalone create',
            'featured_image' => 'properties/standalone-unit.jpg',
            'purpose' => 'sale',
            'property_type' => 'residential',
            'price' => 150000,
            'area' => 200,
            'status' => 1,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ], $overrides);
    }

    public function test_standalone_create_without_project_id_sets_null(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload());

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('user_property.project_id', null);

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => null,
        ]);
    }

    public function test_standalone_create_with_valid_project_id_links_unit_and_lists_on_project(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create', 'projects.view']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload([
            'title' => 'Linked Unit 101',
            'project_id' => $project->id,
        ]));

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('user_property.project_id', $project->id);

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => $project->id,
        ]);

        $list = $this->getJson("/api/projects/{$project->id}/properties?per_page=50");

        $list->assertOk()
            ->assertJsonPath('status', 'success');

        $ids = collect($list->json('data.properties'))->pluck('id')->all();
        $this->assertContains($propertyId, $ids);
    }

    public function test_standalone_create_rejects_nonexistent_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload([
            'project_id' => 999999999,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Validation failed');

        $this->assertArrayHasKey('project_id', $response->json('errors'));
    }

    public function test_standalone_create_rejects_other_tenant_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->seedTenantContext($otherTenant);
        $this->grantPermissions($tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        $otherProject = $this->createProject($otherTenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload([
            'project_id' => $otherProject->id,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Validation failed');

        $this->assertArrayHasKey('project_id', $response->json('errors'));
    }

    public function test_standalone_create_ignores_project_name_fields(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload([
            'project_name' => 'Marketing Project Name',
            'project_title' => 'Should Not Link By Title',
        ]));

        $response->assertCreated()
            ->assertJsonPath('user_property.project_id', null);

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => null,
        ]);

        $this->assertNotSame($project->id, $response->json('user_property.project_id'));
    }

    public function test_standalone_create_treats_empty_project_id_as_null(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create']);
        Sanctum::actingAs($tenant);

        $response = $this->postJson('/api/properties', $this->validStorePayload([
            'project_id' => '',
        ]));

        $response->assertCreated()
            ->assertJsonPath('user_property.project_id', null);

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUpdatePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Standalone Unit Updated',
            'address' => 'Tower A',
            'description' => 'Unit description for standalone update',
            'featured_image' => 'properties/standalone-unit.jpg',
            'purpose' => 'sale',
            'property_type' => 'residential',
        ], $overrides);
    }

    public function test_update_with_unchanged_project_id_succeeds(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $created = $this->postJson('/api/properties', $this->validStorePayload([
            'title' => 'Linked Unit For Update',
            'project_id' => $project->id,
        ]));
        $created->assertCreated();
        $propertyId = (int) $created->json('user_property.id');

        $response = $this->postJson("/api/properties/{$propertyId}", $this->validUpdatePayload([
            'project_id' => $project->id,
        ]));

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => $project->id,
        ]);
    }

    public function test_update_omitting_project_id_succeeds(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $created = $this->postJson('/api/properties', $this->validStorePayload([
            'title' => 'Linked Unit For Update Omit',
            'project_id' => $project->id,
        ]));
        $created->assertCreated();
        $propertyId = (int) $created->json('user_property.id');

        $response = $this->postJson("/api/properties/{$propertyId}", $this->validUpdatePayload());

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => $project->id,
        ]);
    }

    public function test_update_rejects_changing_project_id_to_different_project(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $otherProject = $this->createProject($tenant);

        $created = $this->postJson('/api/properties', $this->validStorePayload([
            'title' => 'Linked Unit For Reject',
            'project_id' => $project->id,
        ]));
        $created->assertCreated();
        $propertyId = (int) $created->json('user_property.id');

        $response = $this->postJson("/api/properties/{$propertyId}", $this->validUpdatePayload([
            'project_id' => $otherProject->id,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Validation failed');

        $this->assertArrayHasKey('project_id', $response->json('errors'));

        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => $project->id,
        ]);
    }

    public function test_update_with_null_project_id_does_not_change_existing_link(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.create', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);

        $created = $this->postJson('/api/properties', $this->validStorePayload([
            'title' => 'Linked Unit For Clear',
            'project_id' => $project->id,
        ]));
        $created->assertCreated();
        $propertyId = (int) $created->json('user_property.id');

        // Laravel's "prohibited" rule treats null as empty, so it passes
        // validation; the update path never writes project_id, so the
        // existing link is preserved either way.
        $response = $this->postJson("/api/properties/{$propertyId}", $this->validUpdatePayload([
            'project_id' => null,
        ]));

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => $project->id,
        ]);
    }
}
