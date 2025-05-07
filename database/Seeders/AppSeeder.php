<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        // Clear the existing data in the table
        // DB::table('api_apps')->delete();
        // Insert new data into the api_apps table

        DB::table('api_apps')->updateOrInsert([
            [
                'name' => 'SEO Booster',
                'description' => 'Improve search engine rankings',
                'price' => 9.99,
                'type' => 'marketplace',
                'rating' => 4.7,
            ],
            [
                'name' => 'Live Chat Widget',
                'description' => 'Real-time messaging tool',
                'price' => 5.99,
                'type' => 'marketplace',
                'rating' => 4.5,
            ],
            [
                'name' => 'Analytics Tracker',
                'description' => 'Track user interactions',
                'price' => 0.00,
                'type' => 'builtin',
                'rating' => 4.9,
            ]

        ]);
    }
}
