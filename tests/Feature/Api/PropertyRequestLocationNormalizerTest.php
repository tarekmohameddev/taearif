<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\PropertyRequests\Services\PropertyRequestLocationNormalizer;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class PropertyRequestLocationNormalizerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_resolves_city_and_district_names_to_ids(): void
    {
        if (! Schema::hasTable('user_cities') || ! Schema::hasTable('user_districts')) {
            $this->markTestSkipped('user_cities / user_districts required.');
        }

        $city = UserCity::query()->create([
            'name_ar' => 'الرياض',
            'name_en' => 'Riyadh ' . Str::random(4),
            'latitude' => 24.7136,
            'longitude' => 46.6753,
        ]);

        $district = UserDistrict::query()->create([
            'name_ar' => 'النرجس',
            'name_en' => 'Al Narjis ' . Str::random(4),
            'city_id' => $city->id,
            'city_name_ar' => $city->name_ar,
            'city_name_en' => $city->name_en,
            'country_name_ar' => 'السعودية',
            'country_name_en' => 'Saudi Arabia',
        ]);

        $normalizer = app(PropertyRequestLocationNormalizer::class);
        $result = $normalizer->normalize([
            'city' => 'الرياض',
            'district' => 'النرجس',
        ], 'whatsapp');

        $this->assertSame($city->id, $result['city_id']);
        $this->assertSame($district->id, $result['districts_id']);
        $this->assertSame(24.7136, $result['latitude']);
        $this->assertSame(46.6753, $result['longitude']);
    }

    public function test_does_not_set_city_id_zero_from_empty_region(): void
    {
        $normalizer = app(PropertyRequestLocationNormalizer::class);
        $result = $normalizer->normalize([], 'public_form');

        $this->assertArrayNotHasKey('city_id', $result);
    }

    public function test_invalid_coordinates_are_nulled(): void
    {
        $normalizer = app(PropertyRequestLocationNormalizer::class);
        $result = $normalizer->normalize([
            'latitude' => 999,
            'longitude' => 46.0,
        ], 'whatsapp');

        $this->assertNull($result['latitude']);
        $this->assertNull($result['longitude']);
    }
}
