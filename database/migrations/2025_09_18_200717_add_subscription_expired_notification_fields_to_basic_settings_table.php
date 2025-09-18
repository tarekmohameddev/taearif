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
            // On Expiration Notification fields
            $table->boolean('subscription_expired_enabled')->default(false);
            $table->text('subscription_expired_text')->nullable();
            $table->string('subscription_expired_template')->nullable();
            $table->time('subscription_expired_send_time')->default('09:00');
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
                'subscription_expired_enabled',
                'subscription_expired_text',
                'subscription_expired_template',
                'subscription_expired_send_time'
            ]);
        });
    }
};