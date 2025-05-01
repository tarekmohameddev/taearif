<?php

namespace Database\Seeders;

use Database\Seeders\UserFacadeSeeder;

// use Illuminate\Database\Seeders;
use Illuminate\Database\Seeder;
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
        // $this->call([
        //     ApiContentSectionSeeder::class
        // ]);
    $this->call(UserFacadeSeeder::class);

    }
}
