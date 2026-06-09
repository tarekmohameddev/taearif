<?php

namespace Tests\Feature\Api\V1\TenantWebsite;

use App\Models\Building;
use App\Models\BuildingMeter;
use App\Models\User;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\Concerns\EnsuresPropertyStatusColumns;
use Tests\TestCase;

class PublicBuildingDataLeakTest extends TestCase
{
    use EnsuresPropertyStatusColumns;
    use DatabaseTransactions;

    /** @var list<string> */
    private const BUILDING_SENSITIVE_KEYS = [
        'owner_name',
        'owner_phone',
        'deed_number',
        'deed_image',
        'deed_image_url',
        'user_id',
        'project_id',
        'is_archived',
        'meters',
        'water_meter_numbers',
        'electricity_meter_numbers',
        'building_meters',
        'meter_type',
        'meter_number',
    ];

    /** @var list<string> */
    private const PROPERTY_SENSITIVE_KEYS = [
        'deed_number',
        'owner_name',
        'owner_phone',
        'water_meter_number',
        'electricity_meter_number',
        'source_broker_name',
        'source_broker_phone',
        'source_broker_type',
        'source_broker_id',
        'owner_number',
        'advertising_license',
        'created_by',
        'deed_image',
        'deed_image_url',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('buildings')) {
            $this->markTestSkipped('buildings table not available');
        }

        if (Schema::hasTable('user_properties')) {
            $this->ensurePropertyStatusColumns();
        }
    }

    public function test_building_list_excludes_sensitive_fields(): void
    {
        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $building = $this->createSensitiveBuilding($tenant->id);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/buildings");
        $response->assertOk();

        $this->assertNoSensitiveKeys(json_encode($response->json()), self::BUILDING_SENSITIVE_KEYS, 'building list');
        $response->assertJsonPath('data.0.slug', $building->slug);
        $response->assertJsonPath('data.0.name', 'Sensitive Tower');
    }

    public function test_building_show_excludes_sensitive_fields_in_building_and_units(): void
    {
        if (! Schema::hasTable('user_properties')) {
            $this->markTestSkipped('user_properties table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $building = $this->createSensitiveBuilding($tenant->id);

        $property = $this->createPublishedProperty($tenant->id, $building->id, [
            'water_meter_number' => 'WM-UNIT-111',
            'electricity_meter_number' => 'EM-UNIT-222',
            'deed_number' => 'PROP-DEED-999',
            'owner_number' => 'OWN-888',
            'source_broker_name' => 'Hidden Broker',
        ]);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/buildings/{$building->slug}");
        $response->assertOk();

        $this->assertNoSensitiveKeys(json_encode($response->json('building')), self::BUILDING_SENSITIVE_KEYS, 'building show');
        $this->assertNoSensitiveKeys(json_encode($response->json('units')), self::PROPERTY_SENSITIVE_KEYS, 'building units');

        $response->assertJsonPath('building.slug', $building->slug);
        $response->assertJsonPath('units.data.0.id', $property->id);
        $response->assertJsonPath('units.data.0.unit_status', 'available');
    }

    public function test_property_show_building_embed_is_minimal_and_excludes_sensitive_fields(): void
    {
        if (! Schema::hasTable('user_properties')) {
            $this->markTestSkipped('user_properties table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $building = $this->createSensitiveBuilding($tenant->id);
        $property = $this->createPublishedProperty($tenant->id, $building->id);
        $slug = $property->contents->first()->slug;

        $this->assertSame($building->id, $property->fresh()->building_id);

        $response = $this->getJson("/api/v1/tenant-website/{$tenant->username}/properties/{$slug}");
        $response->assertOk()
            ->assertJsonPath('property.building.id', $building->id)
            ->assertJsonPath('property.building.name', 'Sensitive Tower')
            ->assertJsonPath('property.building.slug', $building->slug);

        $buildingPayload = $response->json('property.building');
        $this->assertSame(['id', 'name', 'slug'], array_keys($buildingPayload));
        $this->assertNoSensitiveKeys(json_encode($buildingPayload), self::BUILDING_SENSITIVE_KEYS, 'property building embed');
    }

    public function test_building_with_seeded_meters_still_returns_clean_json(): void
    {
        if (! Schema::hasTable('building_meters')) {
            $this->markTestSkipped('building_meters table not available');
        }

        $tenant = User::factory()->create(['account_type' => 'tenant']);
        $building = $this->createSensitiveBuilding($tenant->id);

        BuildingMeter::create([
            'building_id' => $building->id,
            'meter_type' => BuildingMeter::TYPE_WATER,
            'meter_number' => 'WATER-METER-SECRET',
        ]);
        BuildingMeter::create([
            'building_id' => $building->id,
            'meter_type' => BuildingMeter::TYPE_ELECTRICITY,
            'meter_number' => 'ELEC-METER-SECRET',
        ]);

        $listResponse = $this->getJson("/api/v1/tenant-website/{$tenant->username}/buildings");
        $listResponse->assertOk();
        $this->assertNoSensitiveKeys(json_encode($listResponse->json()), self::BUILDING_SENSITIVE_KEYS, 'building list with meters');
        $this->assertStringNotContainsString('WATER-METER-SECRET', json_encode($listResponse->json()));
        $this->assertStringNotContainsString('ELEC-METER-SECRET', json_encode($listResponse->json()));

        $showResponse = $this->getJson("/api/v1/tenant-website/{$tenant->username}/buildings/{$building->slug}");
        $showResponse->assertOk();
        $this->assertNoSensitiveKeys(json_encode($showResponse->json()), self::BUILDING_SENSITIVE_KEYS, 'building show with meters');
        $this->assertStringNotContainsString('WATER-METER-SECRET', json_encode($showResponse->json()));
        $this->assertStringNotContainsString('ELEC-METER-SECRET', json_encode($showResponse->json()));
    }

    private function createSensitiveBuilding(int $userId): Building
    {
        return Building::create([
            'user_id' => $userId,
            'name' => 'Sensitive Tower',
            'description' => 'Public marketing description',
            'address' => '123 Main St',
            'owner_name' => 'Secret Owner',
            'owner_phone' => '+966500000001',
            'deed_number' => 'DEED-SECRET-456',
            'deed_image' => 'deeds/secret-deed.jpg',
            'latitude' => 24.7136,
            'longitude' => 46.6753,
            'is_archived' => false,
        ]);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function createPublishedProperty(int $userId, int $buildingId, array $extra = []): Property
    {
        $property = Property::create(array_merge([
            'user_id' => $userId,
            'building_id' => $buildingId,
            'price' => 250000,
            'purpose' => 'sale',
            'listing_purpose' => 'sale',
            'unit_status' => 'available',
            'publish_status' => 'published',
            'status' => 1,
            'featured_image' => 'test.jpg',
            'property_type' => 'apartment',
        ], $extra));

        PropertyContent::create([
            'user_id' => $userId,
            'property_id' => $property->id,
            'language_id' => 1,
            'title' => 'Unit in Sensitive Tower',
            'slug' => 'unit-sensitive-' . $property->id,
            'address' => '123 Main St',
            'description' => 'Published unit',
        ]);

        return $property->fresh(['contents']);
    }

    /**
     * @param  list<string>  $keys
     */
    private function assertNoSensitiveKeys(string $json, array $keys, string $context): void
    {
        foreach ($keys as $key) {
            $this->assertStringNotContainsString(
                '"' . $key . '"',
                $json,
                "Sensitive key [{$key}] leaked in {$context} response"
            );
        }
    }
}
