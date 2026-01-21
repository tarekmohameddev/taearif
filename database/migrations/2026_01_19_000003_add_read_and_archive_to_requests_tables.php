<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('users_property_requests', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_read');
                $table->index(['user_id', 'is_archived'], 'upr_user_archived_idx');
            }
        });

        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (!Schema::hasColumn('api_customer_inquiry', 'is_read')) {
                $table->boolean('is_read')->default(false)->after('detected_entities_json');
                $table->index(['user_id', 'is_read'], 'aci_user_read_idx');
            }
            if (!Schema::hasColumn('api_customer_inquiry', 'is_archived')) {
                $table->boolean('is_archived')->default(false)->after('is_read');
                $table->index(['user_id', 'is_archived'], 'aci_user_archived_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'is_archived')) {
                $table->dropIndex('upr_user_archived_idx');
                $table->dropColumn('is_archived');
            }
        });

        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            if (Schema::hasColumn('api_customer_inquiry', 'is_archived')) {
                $table->dropIndex('aci_user_archived_idx');
                $table->dropColumn('is_archived');
            }
            if (Schema::hasColumn('api_customer_inquiry', 'is_read')) {
                $table->dropIndex('aci_user_read_idx');
                $table->dropColumn('is_read');
            }
        });
    }
};

