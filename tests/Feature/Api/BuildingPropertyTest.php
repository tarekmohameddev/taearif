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
    private function createUnit(User $user, int $buildingId, int $languageId, array $overrides = []): Property
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
