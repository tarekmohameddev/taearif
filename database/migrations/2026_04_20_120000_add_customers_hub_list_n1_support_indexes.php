<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function hasIndex(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();

        try {
            $result = DB::select(
                'SELECT COUNT(*) as count FROM information_schema.statistics
                 WHERE table_schema = ? AND table_name = ? AND index_name = ?',
                [$databaseName, $table, $indexName]
            );

            return (int) $result[0]->count > 0;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(): void
    {
        // Latest inquiry per customer: WHERE user_id AND GROUP BY customer_id → (user_id, customer_id, id)
        if (Schema::hasTable('api_customer_inquiry') && ! $this->hasIndex('api_customer_inquiry', 'aci_user_customer_id_idx')) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->index(['user_id', 'customer_id', 'id'], 'aci_user_customer_id_idx');
            });
        }

        // EXISTS / filters on appointments by tenant + type + request
        if (Schema::hasTable('property_request_appointments') && ! $this->hasIndex('property_request_appointments', 'pra_user_type_property_request_idx')) {
            Schema::table('property_request_appointments', function (Blueprint $table) {
                $table->index(['user_id', 'type', 'property_request_id'], 'pra_user_type_property_request_idx');
            });
        }

        // EXISTS on reminders: user_id + status + property_request_id (followups tab / filters)
        if (Schema::hasTable('property_request_reminders') && ! $this->hasIndex('property_request_reminders', 'prr_user_status_property_request_idx')) {
            Schema::table('property_request_reminders', function (Blueprint $table) {
                $table->index(['user_id', 'status', 'property_request_id'], 'prr_user_status_property_request_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('api_customer_inquiry') && $this->hasIndex('api_customer_inquiry', 'aci_user_customer_id_idx')) {
            Schema::table('api_customer_inquiry', function (Blueprint $table) {
                $table->dropIndex('aci_user_customer_id_idx');
            });
        }

        if (Schema::hasTable('property_request_appointments') && $this->hasIndex('property_request_appointments', 'pra_user_type_property_request_idx')) {
            Schema::table('property_request_appointments', function (Blueprint $table) {
                $table->dropIndex('pra_user_type_property_request_idx');
            });
        }

        if (Schema::hasTable('property_request_reminders') && $this->hasIndex('property_request_reminders', 'prr_user_status_property_request_idx')) {
            Schema::table('property_request_reminders', function (Blueprint $table) {
                $table->dropIndex('prr_user_status_property_request_idx');
            });
        }
    }
};
