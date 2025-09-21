<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->string('welcome_message_api')->nullable()->after('welcome_message_template');
            $table->string('subscription_expiration_api')->nullable()->after('subscription_expiration_send_time');
            $table->string('subscription_expired_api')->nullable()->after('subscription_expired_send_time');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn([
                'welcome_message_api',
                'subscription_expiration_api', 
                'subscription_expired_api'
            ]);
        });
    }
};