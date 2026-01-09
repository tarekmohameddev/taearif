<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Set 'modern' as default free theme if it exists
        DB::table('api_themes_settings')
            ->where('theme_id', 'modern')
            ->update([
                'is_free' => true,
                'is_enabled' => true,
                'price' => null,
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
            ->where('theme_id', 'modern')
            ->update([
                'is_free' => false,
            ]);
    }
};
