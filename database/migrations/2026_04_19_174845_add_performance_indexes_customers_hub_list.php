<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // api_customer_inquiry: composite (user_id, created_at) for UNION sort by createdAt
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'aci_user_created_at_idx');
        });

        // api_customer_inquiry: composite (user_id, updated_at) for sort by updatedAt
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->index(['user_id', 'updated_at'], 'aci_user_updated_at_idx');
        });

        // api_customer_inquiry: composite (customer_id, user_id) for whereNotExists sub-lookup
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->index(['customer_id', 'user_id'], 'aci_customer_user_idx');
        });

        // users_property_requests: composite (user_id, is_active, updated_at) for tighter filtering
        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->index(['user_id', 'is_active', 'updated_at'], 'upr_user_active_updated_idx');
        });

        // user_property_contents: index on (property_id, id) for derived table GROUP BY elimination
        Schema::table('user_property_contents', function (Blueprint $table) {
            $table->index(['property_id', 'id'], 'upc_property_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropIndex('aci_user_created_at_idx');
        });

        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropIndex('aci_user_updated_at_idx');
        });

        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropIndex('aci_customer_user_idx');
        });

        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->dropIndex('upr_user_active_updated_idx');
        });

        Schema::table('user_property_contents', function (Blueprint $table) {
            $table->dropIndex('upc_property_id_idx');
        });
    }
};
