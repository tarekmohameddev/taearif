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
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\UserDistrict;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PropertyProjectIdImmutableTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_projects', 'user_properties', 'user_property_contents', 'user_districts', 'api_permissions', 'api_model_has_permissions', 'memberships', 'packages', 'user_languages'] as $table) {
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
            ['title' => 'Property Project Id Immutable Test Package'],
            [
                'slug' => 'property-project-id-immutable-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => 100,
                'serial_number' => 996,
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
        $membership->transaction_id = 'property-project-id-immutable-' . uniqid();
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

    private function createProperty(User $tenant, ?int $projectId = null, string $completionStatus = 'complete'): Property
    {
        $property = Property::query()->create([
            'user_id' => $tenant->id,
            'project_id' => $projectId,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'sale',
            'property_type' => 'residential',
            'area' => 120,
            'completion_status' => $completionStatus,
            'status' => 1,
        ]);

        PropertyContent::query()->create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::query()->where('user_id', $tenant->id)->where('is_default', 1)->value('id'),
            'title' => 'Immutable Test Unit',
            'slug' => 'immutable-test-unit-' . $property->id,
            'address' => 'Test Address',
            'description' => 'Test Description',
        ]);

        return $property;
    }

    private function createDistrict(): UserDistrict
    {
        return UserDistrict::query()->create([
            'name_ar' => 'حي الاختبار',
            'name_en' => 'Test District',
            'city_id' => 101,
            'city_name_ar' => 'الرياض',
            'city_name_en' => 'Riyadh',
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validStandaloneUpdatePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Updated Unit Title',
            'address' => 'Updated Address',
            'description' => 'Updated description',
            'featured_image' => 'properties/updated.jpg',
            'property_type' => 'residential',
            'purpose' => 'sale',
            'area' => 130,
        ], $overrides);
    }

    public function test_standalone_update_without_project_id_preserves_existing_link(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);

        $this->postJson("/api/properties/{$property->id}", $this->validStandaloneUpdatePayload())
            ->assertOk();

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_standalone_update_with_project_id_in_body_returns_422(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $otherProject = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);

        $response = $this->postJson("/api/properties/{$property->id}", $this->validStandaloneUpdatePayload([
            'project_id' => $otherProject->id,
        ]));

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertArrayHasKey('project_id', $response->json('errors'));
        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_soft_detach_linked_unit_returns_422(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);

        $this->deleteJson("/api/projects/{$project->id}/properties/{$property->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'project_id cannot be changed after creation.');

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_hard_delete_linked_unit_succeeds(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);

        $this->deleteJson("/api/projects/{$project->id}/properties/{$property->id}?hard_delete=true")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->assertDatabaseMissing('user_properties', ['id' => $property->id]);
    }

    public function test_attach_null_unit_to_project_sets_project_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update', 'projects.view']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, null);

        $this->postJson("/api/projects/{$project->id}/properties/attach", [
            'property_ids' => [$property->id],
        ])->assertOk();

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_reattach_same_project_is_idempotent(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update', 'projects.view']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);

        $this->postJson("/api/projects/{$project->id}/properties/attach", [
            'property_ids' => [$property->id],
        ])->assertOk();

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_attach_unit_linked_to_project_a_to_project_b_returns_409(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update', 'projects.view']);
        Sanctum::actingAs($tenant);

        $projectA = $this->createProject($tenant);
        $projectB = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $projectA->id);

        $this->postJson("/api/projects/{$projectB->id}/properties/attach", [
            'property_ids' => [$property->id],
        ])->assertStatus(409)
            ->assertJsonPath('status', 'error');

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $projectA->id,
        ]);
    }

    public function test_draft_set_project_id_when_null_succeeds(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, null, 'incomplete');

        $this->patchJson("/api/properties/drafts/{$property->id}", [
            'project_id' => $project->id,
        ])->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_draft_change_project_id_when_already_set_returns_422(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.update']);
        Sanctum::actingAs($tenant);

        $projectA = $this->createProject($tenant);
        $projectB = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $projectA->id, 'incomplete');

        $this->patchJson("/api/properties/drafts/{$property->id}", [
            'project_id' => $projectB->id,
        ])->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'project_id cannot be changed after creation.');

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $projectA->id,
        ]);
    }

    public function test_project_scoped_update_with_project_id_in_body_returns_422(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $otherProject = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);
        $district = $this->createDistrict();

        $response = $this->patchJson("/api/projects/{$project->id}/properties/{$property->id}", [
            'title' => 'Updated Unit',
            'project_id' => $otherProject->id,
            'district_id' => $district->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');

        $this->assertArrayHasKey('project_id', $response->json('errors'));
        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }
}
