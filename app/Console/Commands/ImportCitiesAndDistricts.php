<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User\UserCity;
use App\Models\User\UserDistrict;
use Illuminate\Support\Facades\Http;



class ImportCitiesAndDistricts extends Command
{
    protected $signature = 'import:cities-districts';
    protected $description = 'Import cities and districts from external API';

    public function handle()
    {
        $this->importCities();
        $this->importDistricts();
    }

    private function importCities()
    {
        $response = Http::get('https://nzl-backend.com/api/cities?country_id=1');

        if ($response->successful()) {
            foreach ($response['data'] as $cityData) {
                UserCity::updateOrCreate(
                    ['id' => $cityData['id']],
                    [
                        'name_ar' => $cityData['name_ar'],
                        'name_en' => $cityData['name_en'],
                        'country_id' => $cityData['country_id'],
                        'region_id' => $cityData['region_id'],
                        'latitude' => $cityData['latitude'],
                        'longitude' => $cityData['longitude'],
                    ]
                );
            }

            $this->info("Cities imported successfully.");
        } else {
            $this->error("Failed to fetch cities.");
        }
    }

    private function importDistricts()
    {
        foreach (range(1, 6) as $cityId) {
            $response = Http::get("https://nzl-backend.com/api/districts?city_id={$cityId}");

            if ($response->successful()) {
                foreach ($response['data'] as $districtData) {
                    UserDistrict::updateOrCreate(
                        ['id' => $districtData['id']],
                        [
                            'name_ar' => $districtData['name_ar'],
                            'name_en' => $districtData['name_en'],
                            'city_id' => $districtData['city_id'],
                            'city_name_ar' => $districtData['city_name_ar'],
                            'city_name_en' => $districtData['city_name_en'],
                            'country_name_ar' => $districtData['country_name_ar'],
                            'country_name_en' => $districtData['country_name_en'],
                        ]
                    );
                }

                $this->info("Districts for city_id {$cityId} imported.");
            } else {
                $this->error("Failed to fetch districts for city_id {$cityId}.");
            }
        }
    }
}
