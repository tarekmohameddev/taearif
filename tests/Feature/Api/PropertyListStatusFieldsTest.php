<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\MembershipCacheService;
use App\Services\PropertyListCacheVersionService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PropertyListStatusFieldsTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach (['users', 'user_properties', 'user_property_contents', 'buildings', 'user_projects', 'api_permissions', 'api_model_has_permissions', 'memberships', 'packages', 'user_languages'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! Schema::hasColumn('user_properties', 'listing_purpose')) {
            $this->markTestSkipped('Missing listing status columns on user_properties.');
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
            ['title' => 'Property List Status Test Package'],
            [
                'slug' => 'property-list-status-test-package',
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
        $membership->transaction_id = 'property-list-status-' . uniqid();
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

    private function createCompleteProperty(User $tenant, array $overrides = []): Property
    {
        $property = Property::query()->create(array_merge([
            'user_id' => $tenant->id,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'sale',
            'property_status' => 'available',
            'area' => 120,
            'completion_status' => 'complete',
            'status' => 1,
        ], $overrides));

        PropertyContent::query()->create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::query()->where('user_id', $tenant->id)->where('is_default', 1)->value('id'),
            'title' => 'List Unit ' . $property->id,
            'slug' => 'list-unit-' . $property->id,
            'address' => 'Riyadh Test Address',
            'description' => 'Test Description',
        ]);

        return $property;
    }

    public function test_index_returns_status_linking_and_building_fields(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'liststatus' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $project = Project::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'projects/test.jpg',
            'min_price' => 100000,
            'max_price' => 200000,
            'featured' => 0,
            'published' => 1,
            'developer' => 'Dev',
            'units' => 1,
            'completion_date' => now()->addYear()->toDateString(),
            'complete_status' => 0,
        ]);

        $building = Building::query()->create([
            'name' => 'Tower Alpha',
            'slug' => 'tower-alpha',
            'user_id' => $tenant->id,
        ]);

        $property = $this->createCompleteProperty($tenant, [
            'project_id' => $project->id,
            'building_id' => $building->id,
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
        ]);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?per_page=50&include_filters=0');

        $response->assertOk()
            ->assertJsonPath('status', 'success');

        $row = collect($response->json('data.properties'))->firstWhere('id', $property->id);
        $this->assertNotNull($row);
        $this->assertSame($project->id, $row['project_id']);
        $this->assertSame($building->id, $row['building_id']);
        $this->assertSame('sale', $row['listing_purpose']);
        $this->assertSame('available', $row['unit_status']);
        $this->assertSame('published', $row['publish_status']);
        $this->assertSame('sale', $row['purpose']);
        $this->assertSame('sale', $row['transaction_type']);
        $this->assertSame('tower-alpha', $row['building']['slug']);
        $this->assertSame('Tower Alpha', $row['building']['name']);
    }

    public function test_index_filters_by_unit_status_listing_purpose_publish_status_and_building_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'listfilt' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $buildingA = Building::query()->create(['name' => 'A Tower', 'slug' => 'a-tower', 'user_id' => $tenant->id]);
        $buildingB = Building::query()->create(['name' => 'B Tower', 'slug' => 'b-tower', 'user_id' => $tenant->id]);

        $match = $this->createCompleteProperty($tenant, [
            'building_id' => $buildingA->id,
            'listing_purpose' => 'rent',
            'unit_status' => 'reserved',
            'publish_status' => 'draft',
            'purpose' => 'rent',
            'status' => 0,
        ]);

        $this->createCompleteProperty($tenant, [
            'building_id' => $buildingB->id,
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
        ]);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?' . http_build_query([
            'unit_status' => 'reserved',
            'listing_purpose' => 'rent',
            'publish_status' => 'draft',
            'building_id' => $buildingA->id,
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data.properties'))->pluck('id')->all();
        $this->assertSame([$match->id], $ids);
    }

    public function test_index_includes_status_filter_options_in_specifics_filters(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'listopts' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $this->createCompleteProperty($tenant);
        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?per_page=5');

        $response->assertOk()
            ->assertJsonPath('data.specifics_filters.unit_status', ['available', 'reserved', 'sold', 'rented'])
            ->assertJsonPath('data.specifics_filters.listing_purpose', ['sale', 'rent'])
            ->assertJsonPath('data.specifics_filters.publish_status', ['draft', 'published']);
    }
}
