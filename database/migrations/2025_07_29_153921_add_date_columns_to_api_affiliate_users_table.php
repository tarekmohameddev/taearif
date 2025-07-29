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
        Schema::table('api_affiliate_users', function (Blueprint $table) {
            $table->date('start_date_value')->nullable();
            $table->date('to_date_value')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_affiliate_users', function (Blueprint $table) {
            $table->dropColumn('start_date_value');
            $table->dropColumn('to_date_value');
        });
    }
};
