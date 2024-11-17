<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGeneralInformationsToUserBasicSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_basic_settings', function (Blueprint $table) {
            $table->string('website_title')->nullable();
            $table->string('base_currency_symbol')->nullable()->default('$');
            $table->string('base_currency_symbol_position')->nullable();
            $table->string('base_currency_text')->nullable()->default('USD');
            $table->decimal('base_currency_rate', 8, 2)->nullable();
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
            //
        });
    }
}
