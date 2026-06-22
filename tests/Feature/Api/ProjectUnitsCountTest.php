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
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class ProjectUnitsCountTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_projects', 'user_properties', 'user_property_contents', 'api_permissions', 'api_model_has_permissions', 'memberships', 'packages', 'user_languages'] as $table) {
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
            ['title' => 'Project Units Count Test Package'],
            [
                'slug' => 'project-units-count-test-package',
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
        $membership->transaction_id = 'project-units-count-' . uniqid();
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

    private function createProject(User $tenant, int $manualUnits = 10): Project
    {
        return Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/test.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Test Developer',
            'units' => $manualUnits,
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
            'title' => 'Unit ' . $property->id,
            'slug' => 'unit-' . $property->id,
            'address' => 'Test Address',
            'description' => 'Test Description',
        ]);

        return $property;
    }

    private function setupTenant(): User
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, [
            'projects.view',
            'properties.create',
            'properties.update',
        ]);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    public function test_show_returns_computed_units_count_not_manual_units(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->setupTenant();
        $project = $this->createProject($tenant, 10);
        $this->createProperty($tenant, $project->id);
        $this->createProperty($tenant, $project->id);

        $response = $this->getJson("/api/projects/{$project->id}");

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.project.units_count', 2)
            ->assertJsonPath('data.project.units', 10)
            ->assertJsonPath('data.project.units_display_only', 10);
    }

    public function test_index_includes_units_count(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->setupTenant();
        $project = $this->createProject($tenant, 10);
        $this->createProperty($tenant, $project->id);
        $this->createProperty($tenant, $project->id);

        $response = $this->getJson('/api/projects');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $projectRow = collect($response->json('data.projects'))
            ->firstWhere('id', $project->id);

        $this->assertNotNull($projectRow);
        $this->assertSame(2, $projectRow['units_count']);
        $this->assertSame(10, $projectRow['units']);
    }

    public function test_units_count_increases_on_create_and_attach(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->setupTenant();
        $project = $this->createProject($tenant);
        $unassigned = $this->createProperty($tenant, null);

        $this->postJson("/api/projects/{$project->id}/properties", [
            'title' => 'New Unit',
            'address' => 'Block A',
            'description' => 'Created under project',
            'featured_image' => 'properties/new-unit.jpg',
            'purpose' => 'sale',
        ])->assertCreated();

        $this->getJson("/api/projects/{$project->id}")
            ->assertJsonPath('data.project.units_count', 1);

        $this->postJson("/api/projects/{$project->id}/properties/attach", [
            'property_ids' => [$unassigned->id],
        ])->assertOk();

        $this->getJson("/api/projects/{$project->id}")
            ->assertJsonPath('data.project.units_count', 2);
    }

    public function test_units_count_unchanged_on_soft_detach(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->setupTenant();
        $project = $this->createProject($tenant);
        $property = $this->createProperty($tenant, $project->id);

        $this->getJson("/api/projects/{$project->id}")
            ->assertJsonPath('data.project.units_count', 1);

        $this->deleteJson("/api/projects/{$project->id}/properties/{$property->id}")
            ->assertStatus(422)
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('message', 'project_id cannot be changed after creation.');

        $this->getJson("/api/projects/{$project->id}")
            ->assertJsonPath('data.project.units_count', 1);

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'project_id' => $project->id,
        ]);
    }

    public function test_units_count_decreases_on_hard_delete(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->setupTenant();
        $project = $this->createProject($tenant);
        $propertyA = $this->createProperty($tenant, $project->id);
        $propertyB = $this->createProperty($tenant, $project->id);

        $this->deleteJson("/api/projects/{$project->id}/properties/{$propertyA->id}?hard_delete=1")
            ->assertOk()
            ->assertJsonPath('data.deleted', true);

        $this->getJson("/api/projects/{$project->id}")
            ->assertJsonPath('data.project.units_count', 1);

        $this->assertDatabaseMissing('user_properties', ['id' => $propertyA->id]);
        $this->assertDatabaseHas('user_properties', ['id' => $propertyB->id]);
    }

    public function test_property_counters_total_matches_units_count(): void
    {
        $this->skipIfMissingSchema();

        $tenant = $this->setupTenant();
        $project = $this->createProject($tenant);
        $this->createProperty($tenant, $project->id);
        $this->createProperty($tenant, $project->id);
        $this->createProperty($tenant, $project->id);

        $show = $this->getJson("/api/projects/{$project->id}")->assertOk();
        $counters = $this->getJson("/api/projects/{$project->id}/property-counters")->assertOk();

        $this->assertSame(
            $show->json('data.project.units_count'),
            $counters->json('data.total'),
        );
        $this->assertSame(3, $show->json('data.project.units_count'));
    }
}
