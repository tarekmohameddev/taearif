<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Building;
use App\Models\User;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Country;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use App\Models\User\RealestateManagement\State;
use App\Models\User\UserDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class BuildingLocationResolutionTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function index_returns_district_snapshot_city_and_state_in_arabic(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);
        $country = $this->createCountry($user, $language->id);
        $district = $this->createDistrict();

        $building = $this->createBuildingWithPropertyAndContent($user, $language->id, [
            'country_id' => $country->id,
            'state_id' => $district->id,
        ]);

        $response = $this->getJson('/api/buildings');

        $response->assertOk();
        $property = $response->json('data.data.0.properties.0');

        $this->assertSame((string) $building->id, (string) $response->json('data.data.0.id'));
        $this->assertSame($district->city_name_ar, $property['city']);
        $this->assertSame($district->name_ar, $property['state']);
        $this->assertSame($country->name, $property['country']);
    }

    /** @test */
    public function show_returns_district_snapshot_city_and_state_in_arabic(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);
        $country = $this->createCountry($user, $language->id);
        $district = $this->createDistrict();

        $building = $this->createBuildingWithPropertyAndContent($user, $language->id, [
            'country_id' => $country->id,
            'state_id' => $district->id,
        ]);

        $response = $this->getJson('/api/buildings/' . $building->id);

        $response->assertOk();
        $property = $response->json('data.properties.0');

        $this->assertSame($district->city_name_ar, $property['city']);
        $this->assertSame($district->name_ar, $property['state']);
        $this->assertSame($country->name, $property['country']);
    }

    /** @test */
    public function legacy_state_fallback_works_when_no_district_match(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);
        $stateId = $this->nextUnusedStateId();
        $state = $this->createStateWithExplicitId($user, $language->id, $stateId);

        $building = $this->createBuildingWithPropertyAndContent($user, $language->id, [
            'state_id' => $state->id,
            'country_id' => null,
        ]);

        $response = $this->getJson('/api/buildings/' . $building->id);

        $response->assertOk();
        $property = $response->json('data.properties.0');

        $this->assertSame('N/A', $property['city']);
        $this->assertSame($state->name, $property['state']);
        $this->assertSame('N/A', $property['country']);
    }

    /** @test */
    public function missing_location_relations_return_na_without_error(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);

        $building = $this->createBuildingWithPropertyAndContent($user, $language->id, [
            'state_id' => null,
            'country_id' => null,
            'city_id' => null,
        ]);

        $response = $this->getJson('/api/buildings/' . $building->id);

        $response->assertOk();
        $property = $response->json('data.properties.0');

        $this->assertSame('N/A', $property['city']);
        $this->assertSame('N/A', $property['state']);
        $this->assertSame('N/A', $property['country']);
    }

    /** @test */
    public function response_contract_unchanged_for_index_and_show(): void
    {
        $user = $this->createTenantAndAuthenticate();
        $language = $this->createArabicLanguage($user);
        $district = $this->createDistrict();
        $country = $this->createCountry($user, $language->id);
        $building = $this->createBuildingWithPropertyAndContent($user, $language->id, [
            'state_id' => $district->id,
            'country_id' => $country->id,
        ]);

        $indexResponse = $this->getJson('/api/buildings');
        $indexResponse->assertOk();
        $indexProperty = $indexResponse->json('data.data.0.properties.0');
        $this->assertLocationContract($indexProperty);

        $showResponse = $this->getJson('/api/buildings/' . $building->id);
        $showResponse->assertOk();
        $showProperty = $showResponse->json('data.properties.0');
        $this->assertLocationContract($showProperty);
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

    private function createCountry(User $user, int $languageId): Country
    {
        return Country::create([
            'user_id' => $user->id,
            'language_id' => $languageId,
            'name' => 'Saudi Arabia',
            'serial_number' => 1,
        ]);
    }

    private function createDistrict(): UserDistrict
    {
        return UserDistrict::create([
            'name_ar' => 'حي الياسمين',
            'name_en' => 'Al Yasmin',
            'city_id' => 7777,
            'city_name_ar' => 'الرياض',
            'city_name_en' => 'Riyadh',
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
        ]);
    }

    private function createStateWithExplicitId(User $user, int $languageId, int $id): State
    {
        State::query()->insert([
            'id' => $id,
            'user_id' => $user->id,
            'language_id' => $languageId,
            'country_id' => null,
            'name' => 'Legacy State',
            'slug' => 'legacy-state-' . $id,
            'serial_number' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return State::findOrFail($id);
    }

    private function createBuildingWithPropertyAndContent(User $user, int $languageId, array $contentOverrides = []): Building
    {
        $building = Building::create([
            'name' => 'Tower ' . Str::random(6),
            'user_id' => $user->id,
        ]);

        $property = Property::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'building_id' => $building->id,
            'price' => 1000000,
            'pricePerMeter' => 1200,
            'purpose' => 'sale',
            'type' => 'residential',
            'area' => 500,
            'status' => 1,
            'property_status' => 'available',
            'featured' => 0,
            'completion_status' => 'complete',
        ]);

        PropertyContent::create(array_merge([
            'user_id' => $user->id,
            'property_id' => $property->id,
            'language_id' => $languageId,
            'category_id' => null,
            'country_id' => null,
            'state_id' => null,
            'city_id' => null,
            'title' => 'Unit ' . Str::random(6),
            'slug' => 'unit-' . Str::lower(Str::random(10)),
            'address' => 'Riyadh',
            'description' => 'Test description',
            'meta_keyword' => null,
            'meta_description' => null,
        ], $contentOverrides));

        return $building;
    }

    private function nextUnusedStateId(): int
    {
        $id = 900000;
        while (UserDistrict::where('id', $id)->exists() || State::where('id', $id)->exists()) {
            $id++;
        }

        return $id;
    }

    private function assertLocationContract(array $property): void
    {
        $this->assertArrayHasKey('city', $property);
        $this->assertArrayHasKey('state', $property);
        $this->assertArrayHasKey('country', $property);

        $this->assertIsString($property['city']);
        $this->assertIsString($property['state']);
        $this->assertIsString($property['country']);
    }
}
