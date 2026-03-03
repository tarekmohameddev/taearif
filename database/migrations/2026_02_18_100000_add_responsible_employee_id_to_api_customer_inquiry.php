<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add responsible_employee_id to api_customer_inquiry so employees can be
     * assigned at the inquiry level (one employee can be assigned to many inquiries).
     */
    public function up(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customer_inquiry', 'responsible_employee_id')) {
                $table->unsignedBigInteger('responsible_employee_id')->nullable()->after('status_id');
                $table->foreign('responsible_employee_id')->references('id')->on('users')->onDelete('set null');
                $table->index('responsible_employee_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (Schema::hasColumn('api_customer_inquiry', 'responsible_employee_id')) {
                $table->dropForeign(['responsible_employee_id']);
                $table->dropIndex(['responsible_employee_id']);
                $table->dropColumn('responsible_employee_id');
            }
        });
    }
};
