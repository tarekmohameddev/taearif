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
        Schema::table('basic_settings', function (Blueprint $table) {
            // Master toggle for all WhatsApp notifications
            $table->boolean('whatsapp_notifications_enabled')->default(true)->nullable();
        });

        Schema::table('basic_extendeds', function (Blueprint $table) {
            // Master toggle for all Email notifications
            $table->boolean('email_notifications_enabled')->default(true)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn('whatsapp_notifications_enabled');
        });

        Schema::table('basic_extendeds', function (Blueprint $table) {
            $table->dropColumn('email_notifications_enabled');
        });
    }
};
