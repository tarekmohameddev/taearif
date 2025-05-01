<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UserFacadeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $facades = [
            'شمالية', 'شرقية', 'غربية', 'جنوبية',
            'شرقية غربية', 'شمالية شرقية', 'شمالية غربية', 'شمالية جنوبية',
            'جنوبية شرقية', 'جنوبية غربية',
            'شمالية شرقية غربية', 'شمالية جنوبية شرقية',
            'شمالية جنوبية غربية', 'جنوبية شرقية غربية',
            'شمالية جنوبية شرقية غربية',
        ];
        foreach ($facades as $facade) {
            DB::table('user_facades')->insert(['name' => $facade]);
        }
    }
}
