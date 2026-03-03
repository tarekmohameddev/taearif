<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add pipeline stage (status_id) to api_customer_inquiry so inquiries
     * can use the same property_request_statuses as property requests.
     */
    public function up(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customer_inquiry', 'status_id')) {
                $table->unsignedBigInteger('status_id')->nullable()->after('user_id');
                $table->foreign('status_id')->references('id')->on('property_request_statuses')->onDelete('set null');
                $table->index('status_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (Schema::hasColumn('api_customer_inquiry', 'status_id')) {
                $table->dropForeign(['status_id']);
                $table->dropIndex(['status_id']);
                $table->dropColumn('status_id');
            }
        });
    }
};
