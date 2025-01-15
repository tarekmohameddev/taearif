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
        Schema::table('visitors', function (Blueprint $table) {
            $table->string('country_code', 2)->nullable();
            $table->string('region_name')->nullable();
            $table->string('city')->nullable();
            $table->string('ip', 45)->nullable(); // IPv6 support
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('visitors', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'region_name', 'city', 'ip']);
        });
    }
};
