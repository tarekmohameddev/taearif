<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('api_apps')->insert([
            [
                'name' => 'SEO Booster',
                'description' => 'Improve search engine rankings',
                'price' => 9.99,
                'type' => 'marketplace',
                'rating' => 4.7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Live Chat Widget',
                'description' => 'Real-time messaging tool',
                'price' => 5.99,
                'type' => 'marketplace',
                'rating' => 4.5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Analytics Tracker',
                'description' => 'Track user interactions',
                'price' => 0.00,
                'type' => 'builtin',
                'rating' => 4.9,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}
