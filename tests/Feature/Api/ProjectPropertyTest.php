<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\ProjectContent;
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

class ProjectPropertyTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_projects', 'user_project_contents', 'user_properties', 'user_property_contents', 'user_districts', 'api_permissions', 'api_model_has_permissions', 'memberships', 'packages', 'user_languages'] as $table) {
            if (!Schema::hasTable($table)) {
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
            ['title' => 'Project Property Test Package'],
            [
                'slug' => 'project-property-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
                'project_limit_number' => 100,
                'real_estate_limit_number' => 100,
                'serial_number' => 998,
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
        $membership->transaction_id = 'project-property-' . uniqid();
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

    private function createProperty(User $tenant, ?int $projectId = null): Property
    {
        $property = Property::query()->create([
            'user_id' => $tenant->id,
            'project_id' => $projectId,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'sale',
            'area' => 120,
            'completion_status' => 'complete',
            'status' => 1,
        ]);

        PropertyContent::query()->create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::query()->where('user_id', $tenant->id)->where('is_default', 1)->value('id'),
            'title' => 'Existing Unit',
            'slug' => 'existing-unit-' . $property->id,
            'address' => 'Existing Address',
            'description' => 'Existing Description',
        ]);

        return $property;
    }

    private function seedDefaultLanguageProjectContent(User $tenant, Project $project, ?string $address): ProjectContent
    {
        $languageId = Language::query()
            ->where('user_id', $tenant->id)
            ->where('is_default', 1)
            ->value('id');

        return ProjectContent::query()->create([
            'user_id' => $tenant->id,
            'project_id' => $project->id,
            'language_id' => $languageId,
            'title' => 'Project marketing ' . $project->id,
            'slug' => 'project-marketing-' . $project->id,
            'address' => $address,
        ]);
    }

    public function test_index_lists_properties_for_project_with_status_fields(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);
        $property->update([
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
        ]);

        $response = $this->getJson("/api/projects/{$project->id}/properties?per_page=10");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.properties.0.id', $property->id)
            ->assertJsonPath('data.properties.0.project_id', $project->id)
            ->assertJsonPath('data.properties.0.listing_purpose', 'sale')
            ->assertJsonPath('data.properties.0.unit_status', 'available')
            ->assertJsonPath('data.properties.0.publish_status', 'published');
    }

    public function test_index_returns_not_found_for_unknown_project(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->grantPermissions($tenant, ['projects.view']);
        Sanctum::actingAs($tenant);

        $this->getJson('/api/projects/999999999/properties')
            ->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    public function test_properties_index_unassigned_filter_excludes_linked_units(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $unassigned = $this->createProperty($tenant, null);
        $linked = $this->createProperty($tenant, $project->id);

        $unassigned->contents()->update(['title' => 'Unassigned Picker Unit']);
        $linked->contents()->update(['title' => 'Linked Project Unit']);

        $response = $this->getJson('/api/properties?unassigned=1&per_page=50');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $ids = collect($response->json('data.properties'))->pluck('id')->all();

        $this->assertContains($unassigned->id, $ids);
        $this->assertNotContains($linked->id, $ids);
    }

    public function test_orchestration_create_new_units_then_attach_existing(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, [
            'projects.view',
            'properties.create',
            'properties.update',
        ]);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $district = $this->createDistrict();
        $existing = $this->createProperty($tenant, null);

        $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Orchestration Unit One',
            'address' => 'Block A',
            'description' => 'First new unit after project save',
            'featured_image' => 'properties/orch-unit-1.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
        ])->assertCreated()
            ->assertJsonPath('data.property.project_id', $project->id);

        $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Orchestration Unit Two',
            'address' => 'Block B',
            'description' => 'Second new unit after project save',
            'featured_image' => 'properties/orch-unit-2.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
        ])->assertCreated();

        $this->postJson("/api/projects/{$project->id}/properties/attach", [
            'property_ids' => [$existing->id],
        ])->assertOk()
            ->assertJsonPath('data.properties.0.project_id', $project->id);

        $list = $this->getJson("/api/projects/{$project->id}/properties?per_page=50");

        $list->assertOk()
            ->assertJsonPath('data.pagination.total', 3);

        $ids = collect($list->json('data.properties'))->pluck('id')->all();
        $this->assertContains($existing->id, $ids);
    }

    public function test_create_property_under_project_sets_project_id_and_location(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit A',
            'address' => 'Tower 1',
            'description' => 'Project unit description',
            'featured_image' => 'properties/unit-a.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
            'advertising_license' => 'LIC-123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.property.project_id', $project->id)
            ->assertJsonPath('data.property.district_id', $district->id)
            ->assertJsonPath('data.property.city_id', $district->city_id)
            ->assertJsonPath('data.property.advertising_license', 'LIC-123');

        $this->assertDatabaseHas('user_properties', [
            'id' => $response->json('data.property.id'),
            'project_id' => $project->id,
            'advertising_license' => 'LIC-123',
        ]);
    }

    public function test_create_rejects_city_id_mismatch_with_district(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit B',
            'address' => 'Tower 2',
            'description' => 'Another unit',
            'featured_image' => 'properties/unit-b.jpg',
            'district_id' => $district->id,
            'city_id' => $district->city_id + 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    public function test_create_inherits_address_and_coordinates_from_project_when_omitted(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $project->update([
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);
        $this->seedDefaultLanguageProjectContent($tenant, $project, 'Inherited From Project Content');
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit Inherit',
            'description' => 'Inherited coords and address',
            'featured_image' => 'properties/unit-inherit.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.property.address', 'Inherited From Project Content');

        $this->assertEqualsWithDelta(24.7136, (float) $response->json('data.property.location.latitude'), 0.00001);
        $this->assertEqualsWithDelta(46.6753, (float) $response->json('data.property.location.longitude'), 0.00001);

        $propertyId = (int) $response->json('data.property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'project_id' => $project->id,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);
        $this->assertDatabaseHas('user_property_contents', [
            'property_id' => $propertyId,
            'address' => 'Inherited From Project Content',
        ]);
    }

    public function test_create_overrides_inherited_address_and_coordinates(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $project->update([
            'latitude' => 1.111,
            'longitude' => 2.222,
        ]);
        $this->seedDefaultLanguageProjectContent($tenant, $project, 'Should Not Appear');
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit Override',
            'address' => 'Request Address Line',
            'description' => 'Override test',
            'featured_image' => 'properties/unit-override.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
            'latitude' => 25.2048,
            'longitude' => 55.2708,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.property.address', 'Request Address Line');

        $this->assertEqualsWithDelta(25.2048, (float) $response->json('data.property.location.latitude'), 0.00001);
        $this->assertEqualsWithDelta(55.2708, (float) $response->json('data.property.location.longitude'), 0.00001);

        $propertyId = (int) $response->json('data.property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'latitude' => 25.2048,
            'longitude' => 55.2708,
        ]);
        $this->assertDatabaseHas('user_property_contents', [
            'property_id' => $propertyId,
            'address' => 'Request Address Line',
        ]);
    }

    public function test_create_returns_422_when_address_missing_after_merge(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $this->seedDefaultLanguageProjectContent($tenant, $project, '   ');
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit No Address',
            'description' => 'No usable address',
            'featured_image' => 'properties/unit-no-addr.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Address is required when not provided by the project content.');
    }

    public function test_create_returns_422_when_address_missing_and_no_project_content_row(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit No Content',
            'description' => 'No project content row',
            'featured_image' => 'properties/unit-no-content.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('message', 'Address is required when not provided by the project content.');
    }

    public function test_create_rejects_address_longer_than_255_characters(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.create']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $district = $this->createDistrict();

        $response = $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'Unit Long Address',
            'address' => str_repeat('a', 256),
            'description' => 'Too long',
            'featured_image' => 'properties/unit-long.jpg',
            'district_id' => $district->id,
            'purpose' => 'sale',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'Validation failed');

        $this->assertArrayHasKey('address', $response->json('errors'));
    }

    public function test_attach_existing_property_is_idempotent_and_blocks_other_project(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.update']);
        Sanctum::actingAs($tenant);

        $projectA = $this->createProject($tenant);
        $projectB = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $projectB->id);

        $this->postJson("/api/projects/{$projectA->id}/properties/attach", [
            'property_ids' => [$property->id],
        ])->assertStatus(409);

        $property->update(['project_id' => null]);

        $this->postJson("/api/projects/{$projectA->id}/properties/attach", [
            'property_ids' => [$property->id],
        ])->assertOk()
            ->assertJsonPath('data.properties.0.project_id', $projectA->id);

        $this->postJson("/api/projects/{$projectA->id}/properties/attach", [
            'property_ids' => [$property->id],
        ])->assertOk();
    }

    public function test_update_and_detach_project_property(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $district = $this->createDistrict();
        $property = $this->createProperty($tenant, $project->id);

        $this->patchJson("/api/projects/{$project->id}/properties/{$property->id}", [
            'title' => 'Updated Unit',
            'advertising_license' => 'LIC-999',
            'district_id' => $district->id,
        ])->assertOk()
            ->assertJsonPath('data.property.title', 'Updated Unit')
            ->assertJsonPath('data.property.advertising_license', 'LIC-999');

        $this->deleteJson("/api/projects/{$project->id}/properties/{$property->id}")
            ->assertOk()
            ->assertJsonPath('data.detached', true);

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => null,
        ]);
    }

    public function test_update_returns_not_found_when_property_not_on_project(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['projects.view', 'properties.update']);
        Sanctum::actingAs($tenant);

        $project = $this->createProject($tenant);
        $otherProject = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $otherProject->id);

        $this->patchJson("/api/projects/{$project->id}/properties/{$property->id}", [
            'title' => 'Should Fail',
        ])->assertNotFound();
    }
}
