<?php

namespace Database\seeds;
use Illuminate\Database\seeds;
use Database\seeds\ApiUserCategorySeeder;
use Database\seeds\DefaultCategorySeeder;
use Database\seeds\ApiContentSectionSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // $this->call(UsersTableSeeder::class);
         $this->call([
        ApiUserCategorySeeder::class,
        ApiContentSectionSeeder::class,
        DefaultCategorySeeder::class,
    ]);
    }
}
