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
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            $result = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                [$databaseName, $table, $indexName]
            );
            return $result[0]->count > 0;
        };

        // Add composite indexes on user_properties for common filter combinations
        if (!$hasIndex('user_properties', 'user_properties_user_purpose_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'purpose'], 'user_properties_user_purpose_index');
            });
        }

        if (!$hasIndex('user_properties', 'user_properties_user_status_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'status'], 'user_properties_user_status_index');
            });
        }

        if (!$hasIndex('user_properties', 'user_properties_user_price_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'price'], 'user_properties_user_price_index');
            });
        }

        if (!$hasIndex('user_properties', 'user_properties_user_area_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'area'], 'user_properties_user_area_index');
            });
        }

        // Add composite indexes on api_customers for common queries
        if (!$hasIndex('api_customers', 'api_customers_user_created_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index(['user_id', 'created_at'], 'api_customers_user_created_index');
            });
        }

        if (!$hasIndex('api_customers', 'api_customers_user_stage_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index(['user_id', 'stage_id'], 'api_customers_user_stage_index');
            });
        }

        // Add index on api_customer_inquiry for customer lookups
        if (!$hasIndex('api_customer_inquiry', 'api_customer_inquiry_customer_created_index')) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->index(['customer_id', 'created_at'], 'api_customer_inquiry_customer_created_index');
            });
        }

        // Add indexes on api_customer_property_interested for efficient lookups
        if (!$hasIndex('api_customer_property_interested', 'api_customer_property_interested_customer_index')) {
            Schema::table('api_customer_property_interested', function (Blueprint $table) {
                $table->index('customer_id', 'api_customer_property_interested_customer_index');
            });
        }

        if (!$hasIndex('api_customer_property_interested', 'api_customer_property_interested_customer_category_index')) {
            Schema::table('api_customer_property_interested', function (Blueprint $table) {
                $table->index(['customer_id', 'category_id'], 'api_customer_property_interested_customer_category_index');
            });
        }

        if (!$hasIndex('api_customer_property_interested', 'api_customer_property_interested_customer_property_index')) {
            Schema::table('api_customer_property_interested', function (Blueprint $table) {
                $table->index(['customer_id', 'property_id'], 'api_customer_property_interested_customer_property_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropIndex('user_properties_user_purpose_index');
            $table->dropIndex('user_properties_user_status_index');
            $table->dropIndex('user_properties_user_price_index');
            $table->dropIndex('user_properties_user_area_index');
        });

        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropIndex('api_customers_user_created_index');
            $table->dropIndex('api_customers_user_stage_index');
        });

        Schema::table('api_customer_inquiry', function (Blueprint $table) {
            $table->dropIndex('api_customer_inquiry_customer_created_index');
        });

        Schema::table('api_customer_property_interested', function (Blueprint $table) {
            $table->dropIndex('api_customer_property_interested_customer_index');
            $table->dropIndex('api_customer_property_interested_customer_category_index');
            $table->dropIndex('api_customer_property_interested_customer_property_index');
        });
    }
};
