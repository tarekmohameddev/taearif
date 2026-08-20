<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use App\Support\LocationLookupCache;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Covers the public /api/cities and /api/districts lookups after the nzl sync.
 *
 * Ids here sit in the taearif-native range reserved by
 * 2026_08_09_000001_prepare_user_locations_for_nzl_sync so they cannot clash
 * with real nzl-owned rows in the database the suite runs against.
 */
class LocationLookupApiTest extends TestCase
{
    use DatabaseTransactions;

    const TEST_CITY_ID = 900777;
    const TEST_CITY_WITH_DISTRICTS_ID = 900778;
    const TEST_DISTRICT_ID = 90000000777;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['user_cities', 'user_districts'] as $table) {
            if (! Schema::hasTable($table)) {
                $this->markTestSkipped("Missing DB table: {$table}.");
            }
        }

        if (! $this->regionIdIsNullable()) {
            $this->markTestSkipped('user_cities.region_id is still NOT NULL. Run migrations.');
        }

        // Keys are versioned, so this isolates each test from cached payloads.
        LocationLookupCache::flush();
    }

    protected function tearDown(): void
    {
        LocationLookupCache::flush();

        parent::tearDown();
    }

    private function regionIdIsNullable(): bool
    {
        $column = collect(Schema::getConnection()->select('SHOW COLUMNS FROM user_cities'))
            ->firstWhere('Field', 'region_id');

        return $column !== null && $column->Null === 'YES';
    }

    private function makeCity(int $id, string $nameAr = 'مدينة اختبار'): UserCity
    {
        return UserCity::create([
            'id' => $id,
            'name_ar' => $nameAr,
            'name_en' => 'Test City',
            'country_id' => 1,
            'region_id' => null,
        ]);
    }

    private function makeDistrict(int $id, int $cityId): UserDistrict
    {
        return UserDistrict::create([
            'id' => $id,
            'name_ar' => 'حي اختبار',
            'name_en' => 'Test District',
            'city_id' => $cityId,
            'city_name_ar' => 'مدينة اختبار',
            'city_name_en' => 'Test City',
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
        ]);
    }

    /** @test */
    public function cities_endpoint_exposes_only_id_and_names(): void
    {
        $this->makeCity(self::TEST_CITY_ID);

        $response = $this->getJson('/api/cities');

        $response->assertOk()->assertJsonStructure(['data' => [['id', 'name_ar', 'name_en']]]);

        $city = collect($response->json('data'))->firstWhere('id', self::TEST_CITY_ID);

        $this->assertNotNull($city);
        $this->assertSame(['id', 'name_ar', 'name_en'], array_keys($city));
    }

    /**
     * The endpoint used to derive its list from user_districts, so a city with no
     * districts was invisible. Seven real Saudi cities are in that position.
     *
     * @test
     */
    public function cities_endpoint_includes_a_city_that_has_no_districts(): void
    {
        $this->makeCity(self::TEST_CITY_ID, 'مدينة بلا أحياء');

        $this->assertSame(0, UserDistrict::where('city_id', self::TEST_CITY_ID)->count());

        $ids = collect($this->getJson('/api/cities')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains(self::TEST_CITY_ID));
    }

    /**
     * Previously the city_id rule was `exists:user_districts,city_id`, so asking for
     * the districts of a district-less city returned 422 instead of an empty list.
     *
     * @test
     */
    public function districts_endpoint_returns_empty_list_for_a_city_without_districts(): void
    {
        $this->makeCity(self::TEST_CITY_ID);

        $this->getJson('/api/districts?city_id=' . self::TEST_CITY_ID)
            ->assertOk()
            ->assertJson(['data' => []]);
    }

    /** @test */
    public function districts_endpoint_filters_by_city(): void
    {
        $this->makeCity(self::TEST_CITY_WITH_DISTRICTS_ID);
        $this->makeDistrict(self::TEST_DISTRICT_ID, self::TEST_CITY_WITH_DISTRICTS_ID);

        $data = $this->getJson('/api/districts?city_id=' . self::TEST_CITY_WITH_DISTRICTS_ID)
            ->assertOk()
            ->json('data');

        $this->assertCount(1, $data);
        $this->assertSame(self::TEST_DISTRICT_ID, $data[0]['id']);
        $this->assertSame(self::TEST_CITY_WITH_DISTRICTS_ID, $data[0]['city_id']);
    }

    /** @test */
    public function cities_endpoint_still_accepts_the_legacy_country_id_parameter(): void
    {
        $this->makeCity(self::TEST_CITY_ID);

        $ids = collect($this->getJson('/api/cities?country_id=1')->assertOk()->json('data'))->pluck('id');

        $this->assertTrue($ids->contains(self::TEST_CITY_ID));
    }

    /** @test */
    public function districts_endpoint_rejects_a_non_numeric_city_id(): void
    {
        $this->getJson('/api/districts?city_id=abc')->assertStatus(422);
    }

    /**
     * The payloads are cached for a day, so the sync command must invalidate them.
     *
     * @test
     */
    public function cached_cities_payload_is_invalidated_by_a_cache_flush(): void
    {
        $before = collect($this->getJson('/api/cities')->assertOk()->json('data'))->pluck('id');
        $this->assertFalse($before->contains(self::TEST_CITY_ID));

        $this->makeCity(self::TEST_CITY_ID);

        $stale = collect($this->getJson('/api/cities')->assertOk()->json('data'))->pluck('id');
        $this->assertFalse($stale->contains(self::TEST_CITY_ID), 'Expected the cached payload to still be served.');

        LocationLookupCache::flush();

        $fresh = collect($this->getJson('/api/cities')->assertOk()->json('data'))->pluck('id');
        $this->assertTrue($fresh->contains(self::TEST_CITY_ID), 'Expected the flush to expose the new city.');
    }
}
