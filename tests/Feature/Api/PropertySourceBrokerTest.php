<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Membership;
use App\Models\Package;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Services\MembershipCacheService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PropertySourceBrokerTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ensurePropertyStatusColumns();

        foreach (['users', 'user_properties', 'user_languages', 'memberships', 'packages'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! Schema::hasColumn('user_properties', 'source_broker_type')) {
            $this->markTestSkipped('source_broker columns not migrated.');
        }
    }

    public function test_store_internal_broker_persists_employee_and_clears_external_fields(): void
    {
        $tenant = $this->actingAsTenantWithPermissions(['properties.create', 'properties.view_broker']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'username' => 'broker.employee',
            'first_name' => 'Sara',
            'last_name' => 'Broker',
        ]);

        $response = $this->postJson('/api/properties', $this->storePayload([
            'source_broker_type' => 'internal',
            'source_broker_id' => $employee->id,
        ]));

        $response->assertCreated();

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'source_broker_type' => 'internal',
            'source_broker_id' => $employee->id,
            'source_broker_name' => null,
            'source_broker_phone' => null,
        ]);
    }

    public function test_store_external_broker_persists_name_and_phone(): void
    {
        $tenant = $this->actingAsTenantWithPermissions(['properties.create']);

        $response = $this->postJson('/api/properties', $this->storePayload([
            'source_broker_type' => 'external',
            'source_broker_name' => 'أحمد الوسيط',
            'source_broker_phone' => '+966501234567',
        ]));

        $response->assertCreated();

        $propertyId = (int) $response->json('user_property.id');
        $this->assertDatabaseHas('user_properties', [
            'id' => $propertyId,
            'source_broker_type' => 'external',
            'source_broker_id' => null,
            'source_broker_name' => 'أحمد الوسيط',
            'source_broker_phone' => '+966501234567',
        ]);
    }

    public function test_store_internal_without_id_returns_422(): void
    {
        $this->actingAsTenantWithPermissions(['properties.create']);

        $this->postJson('/api/properties', $this->storePayload([
            'source_broker_type' => 'internal',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source_broker_id']);
    }

    public function test_store_external_without_name_returns_422(): void
    {
        $this->actingAsTenantWithPermissions(['properties.create']);

        $this->postJson('/api/properties', $this->storePayload([
            'source_broker_type' => 'external',
            'source_broker_phone' => '+966501234567',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source_broker_name']);
    }

    public function test_store_rejects_source_broker_id_from_other_tenant(): void
    {
        $tenant = $this->actingAsTenantWithPermissions(['properties.create']);
        $otherTenant = User::factory()->create(['account_type' => 'tenant']);
        $otherEmployee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $otherTenant->id,
        ]);

        $this->postJson('/api/properties', $this->storePayload([
            'source_broker_type' => 'internal',
            'source_broker_id' => $otherEmployee->id,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['source_broker_id']);
    }

    public function test_show_includes_source_broker_when_user_has_view_broker_permission(): void
    {
        $tenant = $this->actingAsTenantWithPermissions(['properties.view', 'properties.view_broker']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
            'username' => 'sara.employee',
            'first_name' => 'Sara',
            'last_name' => 'Ahmed',
        ]);

        $property = $this->createProperty($tenant->id, [
            'source_broker_type' => 'internal',
            'source_broker_id' => $employee->id,
        ]);

        $this->getJson("/api/properties/{$property->id}")
            ->assertOk()
            ->assertJsonPath('data.property.source_broker.type', 'internal')
            ->assertJsonPath('data.property.source_broker.id', $employee->id)
            ->assertJsonPath('data.property.source_broker.name', 'Sara Ahmed');
    }

    public function test_show_omits_source_broker_without_view_broker_permission(): void
    {
        if (! Schema::hasTable('api_permissions')) {
            $this->markTestSkipped('api_permissions table not available.');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($employee, $tenant, ['properties.view']);
        Sanctum::actingAs($employee);

        $property = $this->createProperty($tenant->id, [
            'source_broker_type' => 'external',
            'source_broker_name' => 'Hidden Broker',
            'source_broker_phone' => '+966500000000',
        ]);

        $response = $this->getJson("/api/properties/{$property->id}")
            ->assertOk();

        $this->assertArrayNotHasKey('source_broker', $response->json('data.property') ?? []);
    }

    public function test_update_switches_internal_to_external_and_clears_employee_id(): void
    {
        $tenant = $this->actingAsTenantWithPermissions(['properties.update']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);

        $property = $this->createProperty($tenant->id, [
            'source_broker_type' => 'internal',
            'source_broker_id' => $employee->id,
        ]);

        $this->postJson("/api/properties/{$property->id}", $this->updatePayload([
            'source_broker_type' => 'external',
            'source_broker_name' => 'External Broker',
            'source_broker_phone' => '+966509999999',
        ]))
            ->assertOk();

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'source_broker_type' => 'external',
            'source_broker_id' => null,
            'source_broker_name' => 'External Broker',
            'source_broker_phone' => '+966509999999',
        ]);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function actingAsTenantWithPermissions(array $permissions): User
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $this->seedTenantContext($tenant);
        $this->grantPermissions($tenant, $tenant, $permissions);
        Sanctum::actingAs($tenant);

        return $tenant;
    }

    private function seedTenantContext(User $tenant): void
    {
        $package = Package::firstOrCreate(
            ['title' => 'Source Broker Test Package'],
            [
                'slug' => 'source-broker-test-package',
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
        $membership->transaction_id = 'source-broker-' . uniqid();
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
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function storePayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Broker Test Unit',
            'address' => 'Riyadh',
            'description' => 'Unit with source broker.',
            'featured_image' => 'properties/featured/test.jpg',
            'property_type' => 'residential',
            'price' => 500000,
            'area' => 120,
            'purpose' => 'sale',
            'status' => 1,
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ], $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function updatePayload(array $overrides = []): array
    {
        return array_merge($this->storePayload(), $overrides);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createProperty(int $userId, array $overrides = []): Property
    {
        $property = Property::create(array_merge([
            'user_id' => $userId,
            'price' => 100000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'test.jpg',
            'property_type' => 'residential',
        ], $overrides));

        PropertyContent::create([
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Test ' . $property->id,
            'slug' => 'test-' . $property->id,
            'address' => 'Address',
            'description' => 'Description',
        ]);

        return $property->fresh();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
    {
        if (! Schema::hasTable('api_permissions')) {
            return;
        }

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
}
