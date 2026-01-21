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
     * Adds indexes to optimize API performance for:
     * - StepProgressController (user_steps.user_id)
     * - AuthController getUserProfile (memberships, api_domains_settings, user_basic_settings)
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

        // Index for user_steps.user_id (for firstOrCreate lookups)
        // Note: foreignId creates an index, but we'll ensure it exists with our naming
        if (Schema::hasTable('user_steps')) {
            // Check if foreign key index exists (usually named user_steps_user_id_foreign)
            $foreignKeyIndex = DB::select(
                "SELECT COUNT(*) as count FROM information_schema.statistics 
                 WHERE table_schema = ? AND table_name = 'user_steps' AND column_name = 'user_id'",
                [Schema::getConnection()->getDatabaseName()]
            );
            
            if ($foreignKeyIndex[0]->count == 0) {
                Schema::table('user_steps', function (Blueprint $table) {
                    $table->index('user_id', 'user_steps_user_id_index');
                });
            }
        }

        // Composite index for memberships (user_id, expire_date) for expiration checks
        if (Schema::hasTable('memberships')) {
            if (!$hasIndex('memberships', 'memberships_user_expire_index')) {
                Schema::table('memberships', function (Blueprint $table) {
                    $table->index(['user_id', 'expire_date'], 'memberships_user_expire_index');
                });
            }

            // Index for latest membership lookup (user_id, id DESC)
            // MySQL doesn't support DESC in indexes, but (user_id, id) works for ORDER BY id DESC
            if (!$hasIndex('memberships', 'memberships_user_id_index')) {
                Schema::table('memberships', function (Blueprint $table) {
                    $table->index(['user_id', 'id'], 'memberships_user_id_index');
                });
            }
        }

        // Composite index for api_domains_settings (user_id, status) for active domain lookup
        if (Schema::hasTable('api_domains_settings')) {
            if (!$hasIndex('api_domains_settings', 'api_domain_settings_user_status_index')) {
                Schema::table('api_domains_settings', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'api_domain_settings_user_status_index');
                });
            }
        }

        // Index for user_basic_settings.user_id
        if (Schema::hasTable('user_basic_settings')) {
            if (!$hasIndex('user_basic_settings', 'user_basic_settings_user_id_index')) {
                Schema::table('user_basic_settings', function (Blueprint $table) {
                    $table->index('user_id', 'user_basic_settings_user_id_index');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('user_steps')) {
            Schema::table('user_steps', function (Blueprint $table) {
                $table->dropIndex('user_steps_user_id_index');
            });
        }

        if (Schema::hasTable('memberships')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropIndex('memberships_user_expire_index');
                $table->dropIndex('memberships_user_id_index');
            });
        }

        if (Schema::hasTable('api_domains_settings')) {
            Schema::table('api_domains_settings', function (Blueprint $table) {
                $table->dropIndex('api_domain_settings_user_status_index');
            });
        }

        if (Schema::hasTable('user_basic_settings')) {
            Schema::table('user_basic_settings', function (Blueprint $table) {
                $table->dropIndex('user_basic_settings_user_id_index');
            });
        }
    }
};
