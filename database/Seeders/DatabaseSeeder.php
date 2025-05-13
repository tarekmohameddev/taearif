<?php

namespace Database\Seeders;

// use Illuminate\Database\Seeders;
use Illuminate\Database\Seeder;
use Database\Seeders\UserFacadeSeeder;
use Database\Seeders\ApiUserCategorySeeder;
use Database\Seeders\DefaultCategorySeeder;
use Database\Seeders\ApiContentSectionSeeder;
use Database\Seeders\AppSeeder;
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
        // $this->call(UserFacadeSeeder::class);
        // $this->call(ApiContentSectionSeeder::class);
        $this->call(AppSeeder::class);

    }
}
