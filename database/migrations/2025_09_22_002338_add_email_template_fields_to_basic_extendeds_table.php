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
            // Email template fields similar to WhatsApp flow templates
            $table->string('welcome_message_template')->nullable()->after('email_password_reset_template');
            $table->string('subscription_expiration_template')->nullable()->after('welcome_message_template');
            $table->string('subscription_expired_template')->nullable()->after('subscription_expiration_template');
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
                'welcome_message_template',
                'subscription_expiration_template',
                'subscription_expired_template'
            ]);
        });
    }
};
