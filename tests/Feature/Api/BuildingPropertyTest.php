<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class BuildingPropertyTest extends TestCase
{
    use DatabaseTransactions;
    use EnsuresPropertyStatusColumns;

    /** @test */
    public function index_returns_paginated_units_with_status_for_building(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);

        $building = Building::create([
            'name' => 'Tower ' . Str::random(6),
            'user_id' => $user->id,
        ]);

        $available = $this->createUnit($user, $building->id, $language->id, [
            'title' => 'Unit Available',
            'property_status' => 'available',
            'status' => 1,
        ]);

        $rented = $this->createUnit($user, $building->id, $language->id, [
            'title' => 'Unit Rented',
            'purpose' => 'rent',
            'property_status' => 'rented',
            'status' => 0,
        ]);

        $otherBuilding = Building::create([
            'name' => 'Other Tower',
            'user_id' => $user->id,
        ]);
        $this->createUnit($user, $otherBuilding->id, $language->id, [
            'title' => 'Other Building Unit',
        ]);

        $response = $this->getJson('/api/buildings/' . $building->id . '/properties?per_page=10');

        $response->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.building.id', $building->id)
            ->assertJsonPath('data.pagination.total', 2)
            ->assertJsonPath('data.pagination.per_page', 10);

        $ids = collect($response->json('data.properties'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$available->id, $rented->id], $ids);

        $availableRow = collect($response->json('data.properties'))->firstWhere('id', $available->id);
        $rentedRow = collect($response->json('data.properties'))->firstWhere('id', $rented->id);

        $this->assertSame('available', $availableRow['property_status']);
        $this->assertSame('sale', $availableRow['listing_purpose']);
        $this->assertSame('available', $availableRow['unit_status']);
        $this->assertSame('published', $availableRow['publish_status']);
        $this->assertSame(1, $availableRow['status']);
        $this->assertSame('rented', $rentedRow['property_status']);
        $this->assertSame('rent', $rentedRow['listing_purpose']);
        $this->assertSame('rented', $rentedRow['unit_status']);
        $this->assertSame('draft', $rentedRow['publish_status']);
        $this->assertSame(0, $rentedRow['status']);
        $this->assertSame($building->id, $availableRow['building_id']);
    }

    /** @test */
    public function index_supports_search_filter(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);

        $building = Building::create([
            'name' => 'Search Tower',
            'user_id' => $user->id,
        ]);

        $match = $this->createUnit($user, $building->id, $language->id, [
            'title' => 'Penthouse Alpha',
        ]);
        $this->createUnit($user, $building->id, $language->id, [
            'title' => 'Ground Floor',
        ]);

        $response = $this->getJson('/api/buildings/' . $building->id . '/properties?search=Penthouse');

        $response->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.properties.0.id', $match->id);
    }

    /** @test */
    public function index_returns_404_for_unknown_building(): void
    {
        $user = $this->createTenantAndAuthenticate();

        $response = $this->getJson('/api/buildings/999999999/properties');

        $response->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    /** @test */
    public function index_returns_404_when_building_belongs_to_another_tenant(): void
    {
        $owner = $this->createTenantAndAuthenticate();
        $other = User::factory()->create([
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);

        $building = Building::create([
            'name' => 'Private Tower',
            'user_id' => $other->id,
        ]);

        $response = $this->getJson('/api/buildings/' . $building->id . '/properties');

        $response->assertNotFound();
        $this->assertSame($owner->id, $owner->fresh()->id);
    }

    /** @test */
    public function attach_links_unassigned_property_to_building(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);
        $language = $this->createArabicLanguage($user);

        $building = Building::create([
            'name' => 'Attach Tower',
            'user_id' => $user->id,
        ]);

        $property = $this->createUnit($user, null, $language->id, [
            'title' => 'Unassigned Unit',
        ]);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [
            'property_id' => $property->id,
        ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('message', 'Property attached to building successfully')
            ->assertJsonPath('data.property.id', $property->id)
            ->assertJsonPath('data.property.building_id', $building->id);

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'building_id' => $building->id,
        ]);
    }

    /** @test */
    public function attach_is_idempotent_when_property_already_on_same_building(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);
        $language = $this->createArabicLanguage($user);

        $building = Building::create([
            'name' => 'Idempotent Tower',
            'user_id' => $user->id,
        ]);

        $property = $this->createUnit($user, $building->id, $language->id);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [
            'property_id' => $property->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.property.building_id', $building->id);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [
            'property_id' => $property->id,
        ])->assertOk();
    }

    /** @test */
    public function attach_reassigns_property_from_one_building_to_another(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);
        $language = $this->createArabicLanguage($user);

        $buildingA = Building::create([
            'name' => 'Tower A',
            'user_id' => $user->id,
        ]);
        $buildingB = Building::create([
            'name' => 'Tower B',
            'user_id' => $user->id,
        ]);

        $property = $this->createUnit($user, $buildingA->id, $language->id);

        $this->postJson('/api/buildings/' . $buildingB->id . '/properties/attach', [
            'property_id' => $property->id,
        ])
            ->assertOk()
            ->assertJsonPath('data.property.building_id', $buildingB->id);

        $this->assertDatabaseHas('user_properties', [
            'id' => $property->id,
            'building_id' => $buildingB->id,
        ]);
    }

    /** @test */
    public function attach_returns_404_for_unknown_building(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);
        $language = $this->createArabicLanguage($user);

        $property = $this->createUnit($user, null, $language->id);

        $this->postJson('/api/buildings/999999999/properties/attach', [
            'property_id' => $property->id,
        ])
            ->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    /** @test */
    public function attach_returns_404_for_unknown_property(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);

        $building = Building::create([
            'name' => 'Unknown Property Tower',
            'user_id' => $user->id,
        ]);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [
            'property_id' => 999999999,
        ])
            ->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    /** @test */
    public function attach_returns_404_when_building_belongs_to_another_tenant(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);
        $language = $this->createArabicLanguage($user);

        $other = User::factory()->create([
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);

        $otherBuilding = Building::create([
            'name' => 'Other Tenant Tower',
            'user_id' => $other->id,
        ]);

        $property = $this->createUnit($user, null, $language->id);

        $this->postJson('/api/buildings/' . $otherBuilding->id . '/properties/attach', [
            'property_id' => $property->id,
        ])->assertNotFound();
    }

    /** @test */
    public function attach_returns_404_when_property_belongs_to_another_tenant(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);
        $language = $this->createArabicLanguage($user);

        $other = User::factory()->create([
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);
        $otherLanguage = $this->createArabicLanguage($other);

        $building = Building::create([
            'name' => 'My Tower',
            'user_id' => $user->id,
        ]);

        $otherProperty = $this->createUnit($other, null, $otherLanguage->id);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [
            'property_id' => $otherProperty->id,
        ])->assertNotFound();
    }

    /** @test */
    public function attach_returns_422_when_property_id_missing(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $this->grantPermissions($user, $user, ['properties.update']);

        $building = Building::create([
            'name' => 'Validation Tower',
            'user_id' => $user->id,
        ]);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [])
            ->assertStatus(422);
    }

    /** @test */
    public function attach_returns_403_without_properties_update_permission(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $employee = User::factory()->create([
            'account_type' => 'employee',
            'tenant_id' => $tenant->id,
        ]);
        Sanctum::actingAs($employee);

        $language = $this->createArabicLanguage($tenant);
        $building = Building::create([
            'name' => 'Forbidden Tower',
            'user_id' => $tenant->id,
        ]);
        $property = $this->createUnit($tenant, null, $language->id);

        $this->postJson('/api/buildings/' . $building->id . '/properties/attach', [
            'property_id' => $property->id,
        ])->assertForbidden();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function grantPermissions(User $user, User $tenant, array $permissions): void
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

            $user->givePermissionTo($permission);
        }

        $registrar->forgetCachedPermissions();
    }

    private function createTenantAndAuthenticate(): User
    {
        $user = User::factory()->create([
            'account_type' => 'tenant',
            'active' => true,
            'status' => 1,
        ]);

        $this->actingAs($user, 'sanctum');

        return $user;
    }

    private function createArabicLanguage(User $user): Language
    {
        return Language::firstOrCreate(
            ['user_id' => $user->id, 'code' => 'ar'],
            ['name' => 'Arabic', 'rtl' => 1, 'is_default' => 1]
        );
    }

    /**
     * @param array{title?: string, purpose?: string, property_status?: string, status?: int} $overrides
     */
    private function createUnit(User $user, ?int $buildingId, int $languageId, array $overrides = []): Property
    {
        $property = Property::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'building_id' => $buildingId,
            'price' => 1000000,
            'pricePerMeter' => 1200,
            'purpose' => $overrides['purpose'] ?? 'sale',
            'property_type' => 'residential',
            'area' => 500,
            'status' => $overrides['status'] ?? 1,
            'property_status' => $overrides['property_status'] ?? 'available',
            'featured' => 0,
            'completion_status' => 'complete',
        ]);

        PropertyContent::create([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'language_id' => $languageId,
            'category_id' => null,
            'country_id' => null,
            'state_id' => null,
            'city_id' => null,
            'title' => $overrides['title'] ?? 'Unit ' . Str::random(6),
            'slug' => 'unit-' . Str::lower(Str::random(10)),
            'address' => 'Riyadh',
            'description' => 'Test description',
            'meta_keyword' => null,
            'meta_description' => null,
        ]);

        return $property;
    }
}
