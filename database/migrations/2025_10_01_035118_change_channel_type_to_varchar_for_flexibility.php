<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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
        // Change marketing_channels table
        DB::statement('ALTER TABLE marketing_channels MODIFY COLUMN type VARCHAR(50)');
        
        // Change marketing_channel_pricing table
        DB::statement('ALTER TABLE marketing_channel_pricing DROP INDEX marketing_channel_pricing_channel_type_unique');
        DB::statement('ALTER TABLE marketing_channel_pricing MODIFY COLUMN channel_type VARCHAR(50)');
        DB::statement('ALTER TABLE marketing_channel_pricing ADD UNIQUE INDEX marketing_channel_pricing_channel_type_unique (channel_type)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert marketing_channels table
        DB::statement("ALTER TABLE marketing_channels MODIFY COLUMN type ENUM('whatsapp', 'facebook', 'telegram', 'instagram', 'sms')");
        
        // Revert marketing_channel_pricing table
        DB::statement('ALTER TABLE marketing_channel_pricing DROP INDEX marketing_channel_pricing_channel_type_unique');
        DB::statement("ALTER TABLE marketing_channel_pricing MODIFY COLUMN channel_type ENUM('whatsapp', 'facebook', 'telegram', 'instagram', 'sms')");
        DB::statement('ALTER TABLE marketing_channel_pricing ADD UNIQUE INDEX marketing_channel_pricing_channel_type_unique (channel_type)');
    }
};
