<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\ApiUserCategory;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Feature tests for create-then-delete via POST/DELETE /api/properties.
 */
class PropertyCreateAndDeleteTest extends TestCase
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

    /**
     * @param  list<string>  $permissions
     */
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
            ['title' => 'Property Create And Delete Test Package'],
            [
                'slug' => 'property-create-and-delete-test-package',
                'price' => 0,
                'term' => 'monthly',
                'status' => 1,
                'is_active' => 1,
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
        $membership->transaction_id = 'property-create-delete-' . uniqid();
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validStorePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Create Then Delete Unit',
            'address' => 'Test Address',
            'description' => 'Unit created for create-and-delete API test',
            'featured_image' => 'properties/create-delete-unit.jpg',
            'purpose' => 'sale',
            'property_type' => 'residential',
            'price' => 175000,
            'area' => 180,
            'status' => 1,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ], $overrides);
    }

    /**
     * @return array{0: User, 1: int}
     */
    private function createPropertyViaApi(): array
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, [
            'properties.create',
            'properties.view',
            'properties.delete',
        ]);
        Sanctum::actingAs($tenant);

        $create = $this->postJson('/api/properties', $this->validStorePayload());

        $create->assertCreated()
            ->assertJsonPath('status', 'success');

        $propertyId = (int) $create->json('user_property.id');
        $this->assertGreaterThan(0, $propertyId);
        $this->assertDatabaseHas('user_properties', ['id' => $propertyId]);

        return [$tenant, $propertyId];
    }

    public function test_create_then_delete_property_via_api(): void
    {
        $this->skipIfMissingSchema();

        [, $propertyId] = $this->createPropertyViaApi();

        $delete = $this->deleteJson("/api/properties/{$propertyId}");

        $delete->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Property deleted successfully');

        $this->assertDatabaseMissing('user_properties', ['id' => $propertyId]);
    }

    public function test_second_delete_returns_resource_not_found(): void
    {
        $this->skipIfMissingSchema();

        [, $propertyId] = $this->createPropertyViaApi();

        $this->deleteJson("/api/properties/{$propertyId}")
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->deleteJson("/api/properties/{$propertyId}")
            ->assertNotFound()
            ->assertJsonPath('status', 'error')
            ->assertJsonPath('code', 'RESOURCE_NOT_FOUND')
            ->assertJsonPath('message', 'Property not found');
    }

    public function test_get_after_delete_returns_not_found(): void
    {
        $this->skipIfMissingSchema();

        [, $propertyId] = $this->createPropertyViaApi();

        $this->deleteJson("/api/properties/{$propertyId}")
            ->assertOk();

        $this->getJson("/api/properties/{$propertyId}")
            ->assertNotFound();
    }
}
