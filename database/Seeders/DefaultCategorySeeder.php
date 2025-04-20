<?php

namespace Database\Seeders;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;




class DefaultCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('api_user_categories')->updateOrInsert(
            ['slug' => 'other'],
            [
                'name' => 'Other',
                'type' => 'default',
                'is_active' => 1,
                'icon' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

}
