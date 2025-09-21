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
            $table->boolean('password_reset_enabled')->default(false)->after('subscription_expired_api');
            $table->text('password_reset_text')->nullable()->after('password_reset_enabled');
            $table->string('password_reset_template')->nullable()->after('password_reset_text');
            $table->string('password_reset_api')->nullable()->after('password_reset_template');
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
            $table->dropColumn([
                'password_reset_enabled',
                'password_reset_text',
                'password_reset_template',
                'password_reset_api'
            ]);
        });
    }
};