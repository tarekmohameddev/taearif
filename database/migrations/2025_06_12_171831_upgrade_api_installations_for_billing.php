<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('api_installations', function (Blueprint $table) {
            $table->timestamp('activated_at')->nullable()->after('status');
            $table->timestamp('trial_ends_at')->nullable()->after('activated_at');
            $table->timestamp('current_period_end')->nullable()->after('trial_ends_at');
            $table->string('payment_subscription_id')->nullable()->after('current_period_end');
        });

        DB::statement("
            ALTER TABLE api_installations
            MODIFY status ENUM(
                'pending',
                'installed',
                'uninstalled',
                'trialing',
                'past_due',
                'ended',
                'cancelled'
            ) NOT NULL DEFAULT 'pending'
        ");
        Schema::table('api_installations', function (Blueprint $table) {
            $table->unique(['user_id', 'app_id']);  // one install per tenant per app
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Roll back new columns and enum changes
        Schema::table('api_installations', function (Blueprint $table) {
            $table->dropColumn([
                'activated_at',
                'trial_ends_at',
                'current_period_end',
                'payment_subscription_id',
            ]);
        });

        DB::statement("
            ALTER TABLE api_installations
            MODIFY status ENUM('pending','installed','uninstalled')
            NOT NULL DEFAULT 'pending'
        ");

        Schema::table('api_installations', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'app_id']);
            $table->dropIndex(['status']);
        });

    }
};
