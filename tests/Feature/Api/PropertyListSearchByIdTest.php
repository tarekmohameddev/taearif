<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
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

class PropertyListSearchByIdTest extends TestCase
{
    use DatabaseTransactions;

    private function skipIfMissingSchema(): void
    {
        foreach ([
            'users',
            'user_properties',
            'user_property_contents',
            'api_permissions',
            'api_model_has_permissions',
            'memberships',
            'packages',
            'user_languages',
        ] as $table) {
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
            ['title' => 'Property List Search By Id Test Package'],
            [
                'slug' => 'property-list-search-by-id-test-package',
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
        $membership->transaction_id = 'property-list-search-by-id-' . uniqid();
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

    private function createCompleteProperty(User $tenant, array $overrides = [], array $contentOverrides = []): Property
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

        PropertyContent::query()->create(array_merge([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::query()->where('user_id', $tenant->id)->where('is_default', 1)->value('id'),
            'title' => 'List Unit ' . $property->id,
            'slug' => 'list-unit-' . $property->id . '-' . Str::random(4),
            'address' => 'Riyadh Test Address',
            'description' => 'Test Description',
        ], $contentOverrides));

        return $property;
    }

    private function createIncompleteProperty(User $tenant): Property
    {
        $property = Property::query()->create([
            'user_id' => $tenant->id,
            'featured_image' => 'properties/test.jpg',
            'purpose' => 'sale',
            'property_status' => 'available',
            'area' => 120,
            'completion_status' => 'complete',
            'status' => 1,
        ]);

        PropertyContent::query()->create([
            'user_id' => $tenant->id,
            'property_id' => $property->id,
            'language_id' => Language::query()->where('user_id', $tenant->id)->where('is_default', 1)->value('id'),
            'title' => '',
            'slug' => 'empty-title-' . $property->id . '-' . Str::random(4),
            'address' => '',
            'description' => 'Incomplete',
        ]);

        return $property;
    }

    public function test_index_search_by_numeric_id_returns_property(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchid' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $property = $this->createCompleteProperty($tenant, [], [
            'title' => 'UniqueVillaWithoutDigits',
        ]);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?' . http_build_query([
            'search' => (string) $property->id,
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $response->assertOk()->assertJsonPath('status', 'success');
        $ids = collect($response->json('data.properties'))->pluck('id')->all();
        $this->assertContains($property->id, $ids);
    }

    public function test_index_title_search_still_works(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchtitle' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $uniqueFragment = 'ZXQtitle' . Str::random(8);
        $property = $this->createCompleteProperty($tenant, [], [
            'title' => "Luxury {$uniqueFragment} Residence",
        ]);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?' . http_build_query([
            'search' => $uniqueFragment,
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data.properties'))->pluck('id')->all();
        $this->assertContains($property->id, $ids);
    }

    public function test_index_search_by_foreign_tenant_id_returns_empty(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchtena' . Str::random(4)]);
        $otherTenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchtenb' . Str::random(4)]);

        $this->seedTenantContext($tenant);
        $this->seedTenantContext($otherTenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $foreignProperty = $this->createCompleteProperty($otherTenant, [], [
            'title' => 'ForeignOnlyTitleNoSharedDigits',
        ]);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?' . http_build_query([
            'search' => (string) $foreignProperty->id,
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data.properties'))->pluck('id')->all();
        $this->assertNotContains($foreignProperty->id, $ids);
    }

    public function test_index_search_by_id_respects_purpose_filter(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchpurp' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $saleProperty = $this->createCompleteProperty($tenant, [
            'purpose' => 'sale',
        ], [
            'title' => 'PurposeFilterSaleOnly',
        ]);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $wrongPurpose = $this->getJson('/api/properties?' . http_build_query([
            'search' => (string) $saleProperty->id,
            'purpose' => 'rent',
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $wrongPurpose->assertOk();
        $wrongIds = collect($wrongPurpose->json('data.properties'))->pluck('id')->all();
        $this->assertNotContains($saleProperty->id, $wrongIds);

        $matchingPurpose = $this->getJson('/api/properties?' . http_build_query([
            'search' => (string) $saleProperty->id,
            'purpose' => 'sale',
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $matchingPurpose->assertOk();
        $matchIds = collect($matchingPurpose->json('data.properties'))->pluck('id')->all();
        $this->assertContains($saleProperty->id, $matchIds);
    }

    public function test_index_search_by_id_excludes_empty_title_content_gate(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchgate' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $incomplete = $this->createIncompleteProperty($tenant);

        PropertyListCacheVersionService::incrementVersion($tenant->id);

        $response = $this->getJson('/api/properties?' . http_build_query([
            'search' => (string) $incomplete->id,
            'per_page' => 50,
            'include_filters' => 0,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data.properties'))->pluck('id')->all();
        $this->assertNotContains($incomplete->id, $ids);
    }

    public function test_available_units_search_by_numeric_id(): void
    {
        $this->skipIfMissingSchema();

        $tenant = User::factory()->create(['account_type' => 'tenant', 'username' => 'srchunit' . Str::random(4)]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, ['properties.view']);
        Sanctum::actingAs($tenant);

        $property = $this->createCompleteProperty($tenant, [
            'property_status' => 'available',
        ], [
            'title' => 'AvailableUnitNoDigits',
        ]);

        $response = $this->getJson('/api/properties/available-units?' . http_build_query([
            'search' => (string) $property->id,
            'per_page' => 50,
        ]));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();
        if ($ids === []) {
            // Some responses nest under data.properties / data.units
            $ids = collect($response->json('data.properties') ?? $response->json('data.units') ?? [])
                ->pluck('id')
                ->all();
        }
        $this->assertContains($property->id, $ids);
    }
}
