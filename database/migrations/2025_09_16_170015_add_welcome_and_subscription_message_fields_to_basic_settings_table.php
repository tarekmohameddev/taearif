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
            // Welcome Message fields
            $table->boolean('welcome_message_enabled')->default(false);
            $table->text('welcome_message_text')->nullable();
            $table->integer('welcome_message_delay')->default(5);
            $table->string('welcome_message_template')->nullable();
            
            // Subscription Expiration Message fields
            $table->boolean('subscription_expiration_enabled')->default(false);
            $table->text('subscription_expiration_text')->nullable();
            $table->integer('subscription_expiration_days_before')->default(3);
            $table->string('subscription_expiration_template')->nullable();
            $table->time('subscription_expiration_send_time')->default('09:00');
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
                'welcome_message_enabled',
                'welcome_message_text',
                'welcome_message_delay',
                'welcome_message_template',
                'subscription_expiration_enabled',
                'subscription_expiration_text',
                'subscription_expiration_days_before',
                'subscription_expiration_template',
                'subscription_expiration_send_time'
            ]);
        });
    }
};
