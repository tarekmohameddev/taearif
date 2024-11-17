<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPluginColsToUserBasicSettings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_basic_settings', function (Blueprint $table) {
            $table->tinyInteger('whatsapp_status')->default(0);
            $table->string('whatsapp_number', 30)->nullable();
            $table->string('whatsapp_header_title', 255)->nullable();
            $table->tinyInteger('whatsapp_popup_status')->default(0);
            $table->text('whatsapp_popup_message')->nullable();
            $table->tinyInteger('disqus_status')->default(0);
            $table->string('disqus_short_name', 30)->nullable();
            $table->tinyInteger('analytics_status')->default(0);
            $table->string('measurement_id', 100)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_basic_settings', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_status','whatsapp_number','whatsapp_header_title','whatsapp_popup_status','whatsapp_popup_message','disqus_status','disqus_short_name','analytics_status','measurement_id']);
        });
    }
}
