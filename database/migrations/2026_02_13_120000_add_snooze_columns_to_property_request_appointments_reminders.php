<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add snooze support for request-level appointments/reminders (Hub bulk snooze).
     */
    public function up(): void
    {
        Schema::table('property_request_appointments', function (Blueprint $table) {
            $table->timestamp('snoozed_until')->nullable()->after('datetime');
            $table->timestamp('snoozed_at')->nullable()->after('snoozed_until');
            $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_at');
        });

        Schema::table('property_request_reminders', function (Blueprint $table) {
            $table->timestamp('snoozed_until')->nullable()->after('datetime');
            $table->timestamp('snoozed_at')->nullable()->after('snoozed_until');
            $table->unsignedBigInteger('snoozed_by')->nullable()->after('snoozed_at');
        });
    }

    public function down(): void
    {
        Schema::table('property_request_appointments', function (Blueprint $table) {
            $table->dropColumn(['snoozed_until', 'snoozed_at', 'snoozed_by']);
        });
        Schema::table('property_request_reminders', function (Blueprint $table) {
            $table->dropColumn(['snoozed_until', 'snoozed_at', 'snoozed_by']);
        });
    }
};
