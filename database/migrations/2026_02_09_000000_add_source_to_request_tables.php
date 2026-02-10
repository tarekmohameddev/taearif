<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add nullable source column to request-related tables.
     * Values: manual, website, whatsapp, affiliate.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('user_id');
        });

        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('user_id');
        });

        Schema::table('users_api_customers_appointments', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('user_id');
        });

        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('users_api_customers_appointments', function (Blueprint $table) {
            $table->dropColumn('source');
        });

        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
