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
     * Adds critical composite indexes identified in performance analysis to optimize API responses.
     * All indexes are checked for existence before creation to prevent errors.
     *
     * @return void
     */
    public function up()
    {
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return $result[0]->count > 0;
            } catch (\Exception $e) {
                // If table doesn't exist or error, return false
                return false;
            }
        };

        // ===== api_customers table - Critical indexes for CRM filtering =====
        
        // Index for responsible_employee_id filtering (used in CustomerController)
        if (!$hasIndex('api_customers', 'api_customers_responsible_employee_index')) {
            Schema::table('api_customers', function (Blueprint $table) {
                $table->index('responsible_employee_id', 'api_customers_responsible_employee_index');
            });
        }

        // ===== api_customer_property_interested table - Composite indexes =====
        
        // Composite index for customer + category lookups (used in CustomerController batch loading)
        if (!$hasIndex('api_customer_property_interested', 'api_cpi_customer_category_index')) {
            Schema::table('api_customer_property_interested', function (Blueprint $table) {
                $table->index(['customer_id', 'category_id'], 'api_cpi_customer_category_index');
            });
        }

        // Composite index for customer + property lookups
        if (!$hasIndex('api_customer_property_interested', 'api_cpi_customer_property_index')) {
            Schema::table('api_customer_property_interested', function (Blueprint $table) {
                $table->index(['customer_id', 'property_id'], 'api_cpi_customer_property_index');
            });
        }

        // Composite index for user + customer lookups (used in filtering)
        if (!$hasIndex('api_customer_property_interested', 'api_cpi_user_customer_index')) {
            Schema::table('api_customer_property_interested', function (Blueprint $table) {
                $table->index(['user_id', 'customer_id'], 'api_cpi_user_customer_index');
            });
        }

        // ===== user_properties table - Critical indexes for PropertyController =====
        
        // Composite index for featured property ordering (used in PropertyController reorder)
        if (!$hasIndex('user_properties', 'user_properties_user_featured_reorder_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'featured', 'reorder_featured'], 'user_properties_user_featured_reorder_index');
            });
        }

        // Composite index for general property ordering
        if (!$hasIndex('user_properties', 'user_properties_user_reorder_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'reorder'], 'user_properties_user_reorder_index');
            });
        }

        // Composite index for category filtering (if frequently used)
        if (!$hasIndex('user_properties', 'user_properties_user_category_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'category_id'], 'user_properties_user_category_index');
            });
        }

        // Composite index for project filtering
        if (!$hasIndex('user_properties', 'user_properties_user_project_index')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index(['user_id', 'project_id'], 'user_properties_user_project_index');
            });
        }

        // ===== user_property_contents table - For GA path matching =====
        
        // Index on slug for Google Analytics path matching (used in PropertyController GA queries)
        if (!$hasIndex('user_property_contents', 'user_property_contents_slug_index')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index('slug', 'user_property_contents_slug_index');
            });
        }

        // Composite index for property + language lookups
        if (!$hasIndex('user_property_contents', 'user_property_contents_property_language_index')) {
            Schema::table('user_property_contents', function (Blueprint $table) {
                $table->index(['property_id', 'language_id'], 'user_property_contents_property_language_index');
            });
        }

        // ===== user_project_contents table - For GA path matching =====
        
        // Index on slug for Google Analytics path matching (used in ProjectController GA queries)
        if (!$hasIndex('user_project_contents', 'user_project_contents_slug_index')) {
            Schema::table('user_project_contents', function (Blueprint $table) {
                $table->index('slug', 'user_project_contents_slug_index');
            });
        }

        // Composite index for project + language lookups
        if (!$hasIndex('user_project_contents', 'user_project_contents_project_language_index')) {
            Schema::table('user_project_contents', function (Blueprint $table) {
                $table->index(['project_id', 'language_id'], 'user_project_contents_project_language_index');
            });
        }

        // ===== analytics_daily_summary table - Optimize date range queries =====
        // Note: tenant_id + date index already exists, but we can add composite for metric_type filtering
        
        if (!$hasIndex('analytics_daily_summary', 'analytics_tenant_date_metric_index')) {
            Schema::table('analytics_daily_summary', function (Blueprint $table) {
                $table->index(['tenant_id', 'date', 'metric_type'], 'analytics_tenant_date_metric_index');
            });
        }

        // ===== api_customer_inquiry table - Additional indexes =====
        
        // Index on customer_id if not exists (for batch loading in CustomerController)
        if (!$hasIndex('api_customer_inquiry', 'api_customer_inquiry_customer_id_index')) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->index('customer_id', 'api_customer_inquiry_customer_id_index');
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
        // Helper method to check if index exists
        $hasIndex = function ($table, $indexName) {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );
                return $result[0]->count > 0;
            } catch (\Exception $e) {
                return false;
            }
        };

        // Drop indexes in reverse order
        $indexes = [
            ['api_customer_inquiry', 'api_customer_inquiry_customer_id_index'],
            ['analytics_daily_summary', 'analytics_tenant_date_metric_index'],
            ['user_project_contents', 'user_project_contents_project_language_index'],
            ['user_project_contents', 'user_project_contents_slug_index'],
            ['user_property_contents', 'user_property_contents_property_language_index'],
            ['user_property_contents', 'user_property_contents_slug_index'],
            ['user_properties', 'user_properties_user_project_index'],
            ['user_properties', 'user_properties_user_category_index'],
            ['user_properties', 'user_properties_user_reorder_index'],
            ['user_properties', 'user_properties_user_featured_reorder_index'],
            ['api_customer_property_interested', 'api_cpi_user_customer_index'],
            ['api_customer_property_interested', 'api_cpi_customer_property_index'],
            ['api_customer_property_interested', 'api_cpi_customer_category_index'],
            ['api_customers', 'api_customers_responsible_employee_index'],
        ];

        foreach ($indexes as [$table, $indexName]) {
            if ($hasIndex($table, $indexName)) {
                Schema::table($table, function (Blueprint $table) use ($indexName) {
                    $table->dropIndex($indexName);
                });
            }
        }
    }
};
