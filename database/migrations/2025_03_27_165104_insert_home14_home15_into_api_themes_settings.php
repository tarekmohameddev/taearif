<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('api_themes_settings')->insert([
            [
                'theme_id' => 'home14',
                'name' => 'Real Estate Two',
                'description' => 'Second variation of the real estate theme with modern layout.',
                'thumbnail' => 'themes/home14/thumb.png',
                'category' => 'real_estate',
                'active' => false,
                'popular' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'theme_id' => 'home15',
                'name' => 'Real Estate Three',
                'description' => 'Third layout option for real estate businesses.',
                'thumbnail' => 'themes/home15/thumb.png',
                'category' => 'real_estate',
                'active' => false,
                'popular' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('api_themes_settings')
            ->whereIn('theme_id', ['home14', 'home15'])
            ->delete();
    }
};
