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
        Schema::table('api_customer_property_interested', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('property_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_property_interested', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('property_id')->nullable(false)->change();
        });
    }
};
