<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 60 demo buildings for user kkkkk (user_id = 1430).
 *
 * Inserts into:
 * - buildings
 * - building_meters (water + electricity)
 *
 * Run with:
 *   php artisan db:seed --class=KsaBuildingsForKkkkSeeder
 */
class KsaBuildingsForKkkkSeeder extends Seeder
{
    private const USER_ID = 1430;

    private array $images = [
        'https://images.unsplash.com/photo-1486406146926-c627a92ad4ab?w=800&q=80',
        'https://images.unsplash.com/photo-1449247709967-d4461a6a6103?w=800&q=80',
        'https://images.unsplash.com/photo-1529421306624-624f5fba9b5b?w=800&q=80',
        'https://images.unsplash.com/photo-1460317442991-0ec209397118?w=800&q=80',
        'https://images.unsplash.com/photo-1501183638710-841dd1904471?w=800&q=80',
        'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&q=80',
        'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=800&q=80',
        'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?w=800&q=80',
        'https://images.unsplash.com/photo-1497366216548-37526070297c?w=800&q=80',
        'https://images.unsplash.com/photo-1497366754035-f200586c4bd4?w=800&q=80',
    ];

    public function run(): void
    {
        $existingNames = DB::table('buildings')
            ->where('user_id', self::USER_ID)
            ->pluck('name')
            ->map(fn ($n) => trim((string) $n))
            ->all();

        $inserted = 0;
        $skipped  = 0;

        foreach ($this->blueprints() as $i => $b) {
            if (in_array(trim($b['name']), $existingNames, true)) {
                $this->command->warn("⟳ skip (already exists): {$b['name']}");
                $skipped++;
                continue;
            }

            $image = $this->images[$i % count($this->images)];

            $buildingId = DB::table('buildings')->insertGetId([
                'name'       => $b['name'],
                'image'      => $image,
                'deed_number'=> $b['deed_number'],
                'deed_image' => $b['deed_image'],
                'user_id'    => self::USER_ID,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 2 meters: water + electricity
            DB::table('building_meters')->insert([
                [
                    'building_id' => $buildingId,
                    'meter_type'  => 'water',
                    'meter_number'=> $b['water_meter'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
                [
                    'building_id' => $buildingId,
                    'meter_type'  => 'electricity',
                    'meter_number'=> $b['electricity_meter'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ],
            ]);

            $inserted++;
            $this->command->info("✓ #{$inserted} {$b['name']}");
        }

        $this->command->info("\nDone. Inserted {$inserted}, skipped {$skipped} (already existed) — buildings for user kkkkk (id=" . self::USER_ID . ").");
    }

    /**
     * @return list<array{name:string,deed_number:?string,deed_image:?string,water_meter:string,electricity_meter:string}>
     */
    private function blueprints(): array
    {
        $rows = [];

        $ksaCities = ['الرياض', 'جدة', 'الخبر', 'الدمام', 'مكة', 'المدينة', 'الطائف', 'أبها', 'حائل', 'جازان'];
        $types = ['برج', 'مجمع', 'عمارة', 'سكن', 'تاور', 'Residence', 'Plaza'];

        for ($n = 1; $n <= 60; $n++) {
            $city = $ksaCities[($n - 1) % count($ksaCities)];
            $type = $types[($n - 1) % count($types)];

            $name = "{$type} {$city} رقم {$n}";

            $rows[] = [
                'name'              => $name,
                'deed_number'       => (string) (600000 + $n * 73),
                'deed_image'        => null,
                'water_meter'       => 'W-' . str_pad((string) (100000 + $n * 11), 6, '0', STR_PAD_LEFT),
                'electricity_meter' => 'E-' . str_pad((string) (200000 + $n * 17), 6, '0', STR_PAD_LEFT),
            ];
        }

        return $rows;
    }
}

