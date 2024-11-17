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
        Schema::table('user_donation_categories', function (Blueprint $table) {
            $table->integer('is_featured')->nullable()->default(0)->comment('1= featured, 0= none');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_donation_categories', function (Blueprint $table) {
            $table->dropColumn('is_featured');
        });
    }
};
