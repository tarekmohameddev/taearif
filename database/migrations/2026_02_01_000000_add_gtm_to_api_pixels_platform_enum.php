<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add 'gtm' to the api_pixels.platform enum.
     *
     * @return void
     */
    public function up()
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE api_pixels MODIFY COLUMN platform ENUM('facebook', 'tiktok', 'snapchat', 'gtm') NOT NULL");
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE api_pixels DROP CONSTRAINT IF EXISTS api_pixels_platform_check');
            DB::statement("ALTER TABLE api_pixels ADD CONSTRAINT api_pixels_platform_check CHECK (platform::text = ANY (ARRAY['facebook', 'tiktok', 'snapchat', 'gtm']::text[]))");
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE api_pixels MODIFY COLUMN platform ENUM('facebook', 'tiktok', 'snapchat') NOT NULL");
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE api_pixels DROP CONSTRAINT IF EXISTS api_pixels_platform_check');
            DB::statement("ALTER TABLE api_pixels ADD CONSTRAINT api_pixels_platform_check CHECK (platform::text = ANY (ARRAY['facebook', 'tiktok', 'snapchat']::text[]))");
        }
    }
};
