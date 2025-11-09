<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminCrmCardSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $stages = [
            [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'New',
                'slug' => 'new',
                'order' => 1,
                'color' => '#4F46E5',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Contacted',
                'slug' => 'contacted',
                'order' => 2,
                'color' => '#2563EB',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Qualified',
                'slug' => 'qualified',
                'order' => 3,
                'color' => '#0EA5E9',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Converted',
                'slug' => 'converted',
                'order' => 4,
                'color' => '#22C55E',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'uuid' => \Illuminate\Support\Str::uuid()->toString(),
                'name' => 'Lost',
                'slug' => 'lost',
                'order' => 5,
                'color' => '#EF4444',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \Illuminate\Support\Facades\DB::table('admin_crm_cards')->insert($stages);
    }
}
