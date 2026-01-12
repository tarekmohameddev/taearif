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
        Schema::table('api_apps', function (Blueprint $table) {
            $table->integer('subscription_duration')->nullable()->default(30)->after('trial_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_apps', function (Blueprint $table) {
            $table->dropColumn('subscription_duration');
        });
    }
};
