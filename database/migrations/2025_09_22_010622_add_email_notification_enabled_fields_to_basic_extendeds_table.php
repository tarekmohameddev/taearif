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
        Schema::table('basic_extendeds', function (Blueprint $table) {
            // Email notification enable/disable fields
            $table->boolean('welcome_message_email_enabled')->default(true)->after('subscription_expired_template');
            $table->boolean('subscription_expiration_email_enabled')->default(true)->after('welcome_message_email_enabled');
            $table->boolean('subscription_expired_email_enabled')->default(true)->after('subscription_expiration_email_enabled');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_extendeds', function (Blueprint $table) {
            $table->dropColumn([
                'welcome_message_email_enabled',
                'subscription_expiration_email_enabled',
                'subscription_expired_email_enabled'
            ]);
        });
    }
};