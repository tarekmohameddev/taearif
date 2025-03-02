<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration {
    public function up(): void
    {
        $jsonPath = storage_path('app/sa_regions_and_governorates.json');
        $data = json_decode(file_get_contents($jsonPath), true);

        foreach ($data['regions'] as $region) {
            $regionId = DB::table('regions')->insertGetId([
                'name_en' => $region['name_en'],
                'name_ar' => $region['name_ar'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($region['governorates'] as $governorate) {
                DB::table('governorates')->insert([
                    'region_id' => $regionId,
                    'name_en' => $governorate['name_en'],
                    'name_ar' => $governorate['name_ar'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('governorates')->truncate();
        DB::table('regions')->truncate();
    }
};
