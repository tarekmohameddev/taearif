<?php

namespace Database\seeds;

use Illuminate\Database\Console\seeders\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Region;
use App\Models\Governorate;
use Illuminate\Support\Facades\File;

class RegionGovernorateSeeder extends Seeder
{
    public function run(): void
    {
        // Load JSON data
        $json = File::get(database_path('sa_regions_and_governorates.json'));
        $data = json_decode($json, true);

        foreach ($data['regions'] as $regionData) {
            $region = Region::create([
                'id' => $regionData['region_id'],
                'name_en' => $regionData['name_en'],
                'name_ar' => $regionData['name_ar']
            ]);

            foreach ($regionData['governorates'] as $govData) {
                Governorate::create([
                    'id' => $govData['gov_id'],
                    'region_id' => $region->id,
                    'name_en' => $govData['name_en'],
                    'name_ar' => $govData['name_ar']
                ]);
            }
        }
    }
}

