<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\CustomersHub\Services\CustomersHubCacheVersion;
use App\Models\Api\UserPropertyRequest;
use App\Models\User;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PropertyRequestMapTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'users_property_requests', 'user_properties', 'user_cities', 'api_permissions', 'api_model_has_permissions'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! Schema::hasColumn('users_property_requests', 'initial_property_id')) {
            $this->markTestSkipped('initial_property_id column required. Run migration.');
        }
    }

    private function skipIfMissingProjectIdColumn(): void
    {
        $this->skipIfMissingSchema();

        if (! Schema::hasTable('user_projects')) {
            $this->markTestSkipped('user_projects table required.');
        }

        if (! Schema::hasColumn('users_property_requests', 'project_id')) {
            $this->markTestSkipped('project_id column required on users_property_requests. Run migration.');
        }
        if (! Schema::hasTable('property_request_project')) {
            $this->markTestSkipped('property_request_project table required. Run migration.');
        }
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

    private function createTenant(): User
    {
        return User::factory()->create([
            'account_type' => 'tenant',
            'username' => 'maptest' . Str::random(6),
        ]);
    }

    private function createCity(array $overrides = []): UserCity
    {
        return UserCity::query()->create(array_merge([
            'name_ar' => 'مدينة اختبار ' . Str::random(4),
            'name_en' => 'Test City ' . Str::random(4),
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ], $overrides));
    }

    private function createDistrict(int $cityId, array $overrides = []): UserDistrict
    {
        return UserDistrict::query()->create(array_merge([
            'name_ar' => 'حي اختبار ' . Str::random(4),
            'name_en' => 'Test District ' . Str::random(4),
            'city_id' => $cityId,
            'city_name_ar' => 'مدينة',
            'city_name_en' => 'City',
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
        ], $overrides));
    }

    private function createProperty(User $tenant, array $overrides = []): Property
    {
        return Property::query()->create(array_merge([
            'user_id' => $tenant->id,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'sale',
            'property_status' => 'available',
            'area' => 120,
            'completion_status' => 'complete',
            'status' => 1,
            'latitude' => 24.8000,
            'longitude' => 46.9000,
            'property_type' => 'residential',
        ], $overrides));
    }

    private function createRequest(User $tenant, array $overrides = []): UserPropertyRequest
    {
        $defaults = [
            'user_id' => $tenant->id,
            'full_name' => 'Map Customer',
            'phone' => '+9665' . random_int(10000000, 99999999),
            'is_active' => true,
            'is_archived' => false,
            'is_read' => false,
            'source' => 'employee_dashboard',
        ];

        return UserPropertyRequest::query()->create(array_merge($defaults, $overrides));
    }

    public function test_map_returns_clicked_property_pin(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $property = $this->createProperty($tenant);
        $request = $this->createRequest($tenant, [
            'initial_property_id' => $property->id,
            'property_type' => 'residential',
        ]);

        $response = $this->getJson('/api/v1/property-requests/map');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.pins.0.request_id', $request->id)
            ->assertJsonPath('data.pins.0.location_source', 'clicked_property')
            ->assertJsonPath('data.pins.0.lat', 24.8)
            ->assertJsonPath('data.pins.0.pin_color', '#2563eb');
    }

    public function test_initial_property_invalid_coords_falls_through_to_request_coordinates(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $property = $this->createProperty($tenant, ['latitude' => null, 'longitude' => null]);
        $this->createRequest($tenant, [
            'initial_property_id' => $property->id,
            'latitude' => 24.5,
            'longitude' => 46.5,
            'property_type' => 'commercial',
        ]);

        $response = $this->getJson('/api/v1/property-requests/map');

        $response->assertOk()
            ->assertJsonPath('data.pins.0.location_source', 'request_coordinates')
            ->assertJsonPath('data.pins.0.pin_color', '#dc2626');
    }

    public function test_property_matches_coords_are_not_used(): void
    {
        $this->skipIfMissingSchema();

        if (! Schema::hasTable('property_matches')) {
            $this->markTestSkipped('property_matches table required.');
        }

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $matchProperty = $this->createProperty($tenant, [
            'latitude' => 25.0000,
            'longitude' => 47.0000,
        ]);

        $request = $this->createRequest($tenant, [
            'property_type' => 'residential',
            'latitude' => null,
            'longitude' => null,
            'city_id' => null,
        ]);

        DB::table('property_matches')->insert([
            'user_id' => $tenant->id,
            'customer_key' => '9665012345',
            'request_type' => 'web',
            'request_id' => $request->id,
            'property_id' => $matchProperty->id,
            'match_score' => 90,
            'database_score' => 40,
            'ai_score' => 50,
            'is_reviewed' => 0,
            'is_contacted' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->getJson('/api/v1/property-requests/map');

        $response->assertOk()
            ->assertJsonPath('data.pins', [])
            ->assertJsonPath('data.skipped_count', 1);
    }

    public function test_capitalized_property_type_gets_correct_pin_color(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $this->createRequest($tenant, [
            'property_type' => 'Commercial',
            'latitude' => 24.7,
            'longitude' => 46.7,
        ]);

        $response = $this->getJson('/api/v1/property-requests/map');

        $response->assertOk()
            ->assertJsonPath('data.pins.0.property_type', 'commercial')
            ->assertJsonPath('data.pins.0.pin_color', '#dc2626');
    }

    public function test_archived_requests_excluded_by_default(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $this->createRequest($tenant, [
            'is_archived' => true,
            'latitude' => 24.7,
            'longitude' => 46.7,
        ]);

        $this->getJson('/api/v1/property-requests/map')
            ->assertOk()
            ->assertJsonPath('data.pins', []);

        $this->getJson('/api/v1/property-requests/map?include_archived=1')
            ->assertOk()
            ->assertJsonCount(1, 'data.pins');
    }

    public function test_store_from_interest_sets_initial_property_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $property = $this->createProperty($tenant);

        $response = $this->postJson('/api/v1/property-requests/interest', [
            'tenant_username' => $tenant->username,
            'property_id' => $property->id,
            'full_name' => 'Interest User',
            'phone' => '+966501112233',
        ]);

        $response->assertCreated();

        $requestId = (int) $response->json('data.request_id');
        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'initial_property_id' => $property->id,
            'source' => 'property_interest',
        ]);
    }

    public function test_store_from_interest_inherits_project_id_from_property_when_omitted(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = $this->createTenant();
        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, ['project_id' => $project->id]);

        $response = $this->postJson('/api/v1/property-requests/interest', [
            'tenant_username' => $tenant->username,
            'property_id' => $property->id,
            'full_name' => 'Interest Inherit Project',
            'phone' => '+966501112244',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.property_id', $property->id)
            ->assertJsonMissingPath('data.project_id');

        $requestId = (int) $response->json('data.request_id');
        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'initial_property_id' => $property->id,
            'project_id' => $project->id,
            'source' => 'property_interest',
        ]);
    }

    public function test_store_from_interest_body_project_id_overrides_property_project(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = $this->createTenant();
        $propertyProject = $this->createProject($tenant);
        $overrideProject = $this->createProject($tenant);
        $property = $this->createProperty($tenant, ['project_id' => $propertyProject->id]);

        $response = $this->postJson('/api/v1/property-requests/interest', [
            'tenant_username' => $tenant->username,
            'property_id' => $property->id,
            'full_name' => 'Interest Override Project',
            'phone' => '+966501112255',
            'project_id' => $overrideProject->id,
        ]);

        $response->assertCreated()
            ->assertJsonMissingPath('data.project_id');

        $requestId = (int) $response->json('data.request_id');
        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'project_id' => $overrideProject->id,
            'source' => 'property_interest',
        ]);
    }

    public function test_store_from_interest_explicit_arrays_override_inherited_links(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = $this->createTenant();
        $inherited = $this->createProject($tenant);
        $first = $this->createProject($tenant);
        $second = $this->createProject($tenant);
        $clicked = $this->createProperty($tenant, ['project_id' => $inherited->id]);
        $additional = $this->createProperty($tenant);

        $response = $this->postJson('/api/v1/property-requests/interest', [
            'tenant_username' => $tenant->username,
            'property_id' => $clicked->id,
            'full_name' => 'Interest Multiple Links',
            'phone' => '+966501112266',
            'project_ids' => [$first->id, $second->id],
            'property_ids' => [$additional->id],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.project_ids', [$first->id, $second->id])
            ->assertJsonPath('data.property_ids', [$additional->id]);
        $requestId = (int) $response->json('data.request_id');
        $this->assertDatabaseHas('users_property_requests', [
            'id' => $requestId,
            'initial_property_id' => $clicked->id,
            'project_id' => $first->id,
        ]);
        $this->assertDatabaseMissing('property_request_project', [
            'property_request_id' => $requestId,
            'project_id' => $inherited->id,
        ]);
    }

    public function test_public_store_with_valid_project_id_persists(): void
    {
        $this->skipIfMissingProjectIdColumn();

        $tenant = $this->createTenant();
        $city = UserCity::query()->first();
        if (! $city) {
            $this->markTestSkipped('user_cities must have at least one row.');
        }
        $project = $this->createProject($tenant);

        $response = $this->postJson('/api/v1/property-requests/public', [
            'tenant_username' => $tenant->username,
            'full_name' => 'Public Project User',
            'phone' => '+966502223355',
            'region' => $city->id,
            'project_id' => $project->id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users_property_requests', [
            'id' => $response->json('data.id'),
            'user_id' => $tenant->id,
            'project_id' => $project->id,
            'source' => 'public_form',
        ]);
    }

    public function test_attach_properties_does_not_change_initial_property_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view', 'property_requests.update']);
        Sanctum::actingAs($tenant);

        $initial = $this->createProperty($tenant, ['latitude' => 24.1, 'longitude' => 46.1]);
        $attached = $this->createProperty($tenant, ['latitude' => 25.1, 'longitude' => 47.1]);
        $request = $this->createRequest($tenant, [
            'initial_property_id' => $initial->id,
            'property_ids' => [$initial->id],
            'latitude' => 24.1,
            'longitude' => 46.1,
        ]);
        $cacheVersion = app(CustomersHubCacheVersion::class);
        $beforeAttach = $cacheVersion->getVersion((int) $tenant->id);

        $this->postJson("/api/v1/property-requests/{$request->id}/properties", [
            'propertyIds' => [$attached->id],
        ])->assertOk();
        $this->assertGreaterThan($beforeAttach, $cacheVersion->getVersion((int) $tenant->id));

        $request->refresh();
        $this->assertSame($initial->id, (int) $request->initial_property_id);

        $map = $this->getJson('/api/v1/property-requests/map')->json('data.pins.0');
        $this->assertSame('clicked_property', $map['location_source']);
        $this->assertSame(24.1, $map['lat']);
    }

    public function test_map_requires_permission(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        Sanctum::actingAs($tenant);

        $this->getJson('/api/v1/property-requests/map')->assertForbidden();
    }

    public function test_public_store_defaults_source_to_public_form(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $city = $this->createCity();

        $response = $this->postJson('/api/v1/property-requests/public', [
            'tenant_username' => $tenant->username,
            'full_name' => 'Public User',
            'phone' => '+966502223344',
            'region' => $city->id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('users_property_requests', [
            'user_id' => $tenant->id,
            'source' => 'public_form',
            'city_id' => $city->id,
        ]);
    }

    public function test_city_fallback_pin(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $city = $this->createCity(['latitude' => 21.4225, 'longitude' => 39.8262]);
        $this->createRequest($tenant, [
            'city_id' => $city->id,
            'property_type' => 'industrial',
        ]);

        $response = $this->getJson('/api/v1/property-requests/map');

        $response->assertOk()
            ->assertJsonPath('data.pins.0.location_source', 'city_fallback')
            ->assertJsonPath('data.pins.0.pin_color', '#7c3aed');
    }

    public function test_filter_by_city_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->createTenant();
        $this->grantPermissions($tenant, ['property_requests.view']);
        Sanctum::actingAs($tenant);

        $cityA = $this->createCity();
        $cityB = $this->createCity();
        $this->createRequest($tenant, ['city_id' => $cityA->id, 'latitude' => 24.1, 'longitude' => 46.1]);
        $this->createRequest($tenant, ['city_id' => $cityB->id, 'latitude' => 24.2, 'longitude' => 46.2]);

        $response = $this->getJson('/api/v1/property-requests/map?city_id=' . $cityA->id);

        $response->assertOk()->assertJsonCount(1, 'data.pins');
    }
}
