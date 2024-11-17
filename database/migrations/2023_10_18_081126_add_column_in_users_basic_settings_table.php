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
        Schema::table('user_basic_settings', function (Blueprint $table) {
            $table->tinyInteger('cookie_alert_status')->default(0);
            $table->text('cookie_alert_text')->nullable();
            $table->string('cookie_alert_button_text')->nullable();
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
            $table->dropColumn('cookie_alert_status');
            $table->dropColumn('cookie_alert_text');
            $table->dropColumn('cookie_alert_button_text');
        });
    }
};
