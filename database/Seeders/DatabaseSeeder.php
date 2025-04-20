<?php

namespace Database\Seeders;
use Illuminate\Database\Seeders;
use Database\Seeders\ApiUserCategorySeeder;
use Database\Seeders\DefaultCategorySeeder;
use Database\Seeders\ApiContentSectionSeeder;

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
        ApiContentSectionSeeder::class
    ]);
    }
}
