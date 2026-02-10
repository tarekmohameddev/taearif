<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds snooze support for Customers Hub bulk snooze action.
     */
    public function up(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->timestamp('snoozed_until')->nullable()->after('datetime');
            $table->timestamp('snoozed_at')->nullable()->after('snoozed_until');
            $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_at');
        });

        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->timestamp('snoozed_until')->nullable()->after('datetime');
            $table->timestamp('snoozed_at')->nullable()->after('snoozed_until');
            $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_at');
        });

        Schema::table('users_api_customers_appointments', function (Blueprint $table) {
            $table->timestamp('snoozed_until')->nullable()->after('datetime');
            $table->timestamp('snoozed_at')->nullable()->after('snoozed_until');
            $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropColumn(['snoozed_until', 'snoozed_at', 'snoozed_by']);
        });
        Schema::table('users_api_customers_reminders', function (Blueprint $table) {
            $table->dropColumn(['snoozed_until', 'snoozed_at', 'snoozed_by']);
        });
        Schema::table('users_api_customers_appointments', function (Blueprint $table) {
            $table->dropColumn(['snoozed_until', 'snoozed_at', 'snoozed_by']);
        });
    }
};
