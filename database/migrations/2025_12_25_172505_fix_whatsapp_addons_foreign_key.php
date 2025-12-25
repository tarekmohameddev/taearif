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
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_number_id']);
            $table->foreign('whatsapp_number_id')->references('id')->on('whatsapp_users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_number_id']);
            $table->foreign('whatsapp_number_id')->references('id')->on('whatsapp_users');
        });
    }
};
