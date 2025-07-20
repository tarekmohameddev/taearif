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
        Schema::table('api_customers', function (Blueprint $table) {
            $table->text('note')->nullable()->after('email');
            $table->string('customer_type', 50)->nullable()->after('note');
            $table->unsignedBigInteger('city_id')->nullable()->after('customer_type');
            $table->unsignedBigInteger('district_id')->nullable()->after('city_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropColumn(['note', 'customer_type', 'city_id', 'district_id']);
        });
    }
};
