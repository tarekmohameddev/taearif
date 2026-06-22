<?php

namespace Database\Seeders;

use App\Models\Building;
use App\Models\User\Language;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\RealestateManagement\PropertyContent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds demo units (properties) linked to kkkkk buildings (user_id = 1430).
 *
 * Default: 6 units on building id 63 (برج الرياض رقم 1).
 *
 * Run with:
 *   php artisan db:seed --class=KsaBuildingUnitsForKkkkSeeder
 */
class KsaBuildingUnitsForKkkkSeeder extends Seeder
{
    private const USER_ID = 1430;

    private const BUILDING_ID = 63;

    public function run(): void
    {
        $building = Building::query()
            ->where('user_id', self::USER_ID)
            ->where('id', self::BUILDING_ID)
            ->first();

        if (! $building) {
            $this->command->error('Building ' . self::BUILDING_ID . ' not found for user ' . self::USER_ID . '. Run KsaBuildingsForKkkkSeeder first.');

            return;
        }

        $language = Language::firstOrCreate(
            ['user_id' => self::USER_ID, 'code' => 'ar'],
            ['name' => 'Arabic', 'rtl' => 1, 'is_default' => 1]
        );

        $units = [
            [
                'title' => 'شقة 101 - برج الرياض رقم 1',
                'purpose' => 'rent',
                'property_status' => 'rented',
                'price' => 600000,
                'area' => 120,
                'beds' => 3,
                'bath' => 2,
            ],
            [
                'title' => 'شقة 102 - برج الرياض رقم 1',
                'purpose' => 'rent',
                'property_status' => 'available',
                'price' => 450000,
                'area' => 95,
                'beds' => 2,
                'bath' => 2,
            ],
            [
                'title' => 'شقة 201 - برج الرياض رقم 1',
                'purpose' => 'rent',
                'property_status' => 'available',
                'price' => 573500,
                'area' => 556,
                'beds' => 4,
                'bath' => 3,
            ],
            [
                'title' => 'شقة 301 - برج الرياض رقم 1',
                'purpose' => 'sale',
                'property_status' => 'available',
                'price' => 1850000,
                'area' => 210,
                'beds' => 4,
                'bath' => 3,
            ],
            [
                'title' => 'شقة 302 - برج الرياض رقم 1',
                'purpose' => 'sale',
                'property_status' => 'sold',
                'price' => 2100000,
                'area' => 245,
                'beds' => 5,
                'bath' => 4,
            ],
            [
                'title' => 'استوديو 401 - برج الرياض رقم 1',
                'purpose' => 'rent',
                'property_status' => 'available',
                'price' => 280000,
                'area' => 65,
                'beds' => 1,
                'bath' => 1,
            ],
        ];

        $inserted = 0;
        $skipped = 0;

        foreach ($units as $index => $unit) {
            $exists = PropertyContent::query()
                ->where('user_id', self::USER_ID)
                ->where('title', $unit['title'])
                ->exists();

            if ($exists) {
                $this->command->warn("⟳ skip (already exists): {$unit['title']}");
                $skipped++;
                continue;
            }

            $area = (float) $unit['area'];
            $price = (float) $unit['price'];

            $property = Property::create([
                'user_id' => self::USER_ID,
                'created_by' => self::USER_ID,
                'building_id' => $building->id,
                'price' => $price,
                'pricePerMeter' => $area > 0 ? round($price / $area) : $price,
                'purpose' => $unit['purpose'],
                'property_type' => 'residential',
                'area' => $area,
                'beds' => $unit['beds'],
                'bath' => $unit['bath'],
                'status' => 1,
                'property_status' => $unit['property_status'],
                'featured' => 0,
                'completion_status' => 'complete',
            ]);

            PropertyContent::create([
                'user_id' => self::USER_ID,
                'property_id' => $property->id,
                'language_id' => $language->id,
                'title' => $unit['title'],
                'slug' => 'building-' . $building->id . '-unit-' . ($index + 1) . '-' . Str::lower(Str::random(6)),
                'address' => 'الرياض - برج الرياض رقم 1',
                'description' => 'وحدة تجريبية مرتبطة بالمبنى للاختبار.',
            ]);

            $inserted++;
            $this->command->info("✓ #{$inserted} [building {$building->id}] {$unit['title']}");
        }

        $this->command->info("\nDone. Inserted {$inserted}, skipped {$skipped} — building {$building->id} ({$building->name}).");
    }
}
