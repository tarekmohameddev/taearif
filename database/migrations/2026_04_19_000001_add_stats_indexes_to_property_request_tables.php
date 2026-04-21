<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $hasIndex = function (string $table, string $indexName): bool {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );

                return (int) $result[0]->count > 0;
            } catch (\Throwable $e) {
                return false;
            }
        };

        if (Schema::hasTable('users_property_requests')) {
            if (!$hasIndex('users_property_requests', 'upr_stats_created')) {
                if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'created_at')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index(['user_id', 'created_at'], 'upr_stats_created');
                    });
                }
            }
            if (!$hasIndex('users_property_requests', 'upr_stats_updated')) {
                if (Schema::hasColumn('users_property_requests', 'user_id') && Schema::hasColumn('users_property_requests', 'updated_at')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index(['user_id', 'updated_at'], 'upr_stats_updated');
                    });
                }
            }
            if (!$hasIndex('users_property_requests', 'upr_stats_status_updated')) {
                if (Schema::hasColumn('users_property_requests', 'status_id') && Schema::hasColumn('users_property_requests', 'updated_at')) {
                    Schema::table('users_property_requests', function (Blueprint $table) {
                        $table->index(['status_id', 'updated_at'], 'upr_stats_status_updated');
                    });
                }
            }
        }

        if (Schema::hasTable('property_request_appointments')) {
            if (!$hasIndex('property_request_appointments', 'pra_stats_status_dt')) {
                if (Schema::hasColumn('property_request_appointments', 'status') && Schema::hasColumn('property_request_appointments', 'datetime')) {
                    Schema::table('property_request_appointments', function (Blueprint $table) {
                        $table->index(['status', 'datetime'], 'pra_stats_status_dt');
                    });
                }
            }
        }

        if (Schema::hasTable('property_request_reminders')) {
            if (!$hasIndex('property_request_reminders', 'prr_stats_status_dt')) {
                if (Schema::hasColumn('property_request_reminders', 'status') && Schema::hasColumn('property_request_reminders', 'datetime')) {
                    Schema::table('property_request_reminders', function (Blueprint $table) {
                        $table->index(['status', 'datetime'], 'prr_stats_status_dt');
                    });
                }
            }
        }
    }

    public function down(): void
    {
        $hasIndex = function (string $table, string $indexName): bool {
            $connection = Schema::getConnection();
            $databaseName = $connection->getDatabaseName();
            try {
                $result = DB::select(
                    "SELECT COUNT(*) as count FROM information_schema.statistics 
                     WHERE table_schema = ? AND table_name = ? AND index_name = ?",
                    [$databaseName, $table, $indexName]
                );

                return (int) $result[0]->count > 0;
            } catch (\Throwable $e) {
                return false;
            }
        };

        if (Schema::hasTable('users_property_requests')) {
            foreach (['upr_stats_created', 'upr_stats_updated', 'upr_stats_status_updated'] as $name) {
                if ($hasIndex('users_property_requests', $name)) {
                    Schema::table('users_property_requests', function (Blueprint $table) use ($name) {
                        $table->dropIndex($name);
                    });
                }
            }
        }

        if (Schema::hasTable('property_request_appointments') && $hasIndex('property_request_appointments', 'pra_stats_status_dt')) {
            Schema::table('property_request_appointments', function (Blueprint $table) {
                $table->dropIndex('pra_stats_status_dt');
            });
        }

        if (Schema::hasTable('property_request_reminders') && $hasIndex('property_request_reminders', 'prr_stats_status_dt')) {
            Schema::table('property_request_reminders', function (Blueprint $table) {
                $table->dropIndex('prr_stats_status_dt');
            });
        }
    }
};
