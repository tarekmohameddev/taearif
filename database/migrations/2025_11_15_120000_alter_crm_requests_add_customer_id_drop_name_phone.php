<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable()->after('user_id')->index();
            // $table->foreign('customer_id')->references('id')->on('api_customers')->cascadeOnDelete();

            // Drop legacy columns if they exist
            if (Schema::hasColumn('crm_requests', 'customer_name')) {
                $table->dropColumn('customer_name');
            }
            if (Schema::hasColumn('crm_requests', 'customer_phone')) {
                $table->dropColumn('customer_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('crm_requests', function (Blueprint $table) {
            // Re-add columns (nullable) to allow rollback
            if (!Schema::hasColumn('crm_requests', 'customer_name')) {
                $table->string('customer_name')->nullable();
            }
            if (!Schema::hasColumn('crm_requests', 'customer_phone')) {
                $table->string('customer_phone', 32)->nullable();
            }
            if (Schema::hasColumn('crm_requests', 'customer_id')) {
                $table->dropColumn('customer_id');
            }
        });
    }
};


