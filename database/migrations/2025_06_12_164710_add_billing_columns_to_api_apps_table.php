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
        Schema::table('api_apps', function (Blueprint $table) {
            $table->enum('billing_type', ['free', 'one_time', 'subscription'])->default('free')->after('name');
            $table->smallInteger('trial_days')->nullable()->after('billing_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_apps', function (Blueprint $table) {
            $table->dropColumn(['billing_type', 'trial_days']);
        });
    }
};
