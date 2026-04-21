<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE: these indexes are intentionally added with try/catch to keep the migration
        // safe across environments where an index may already exist.

        if (Schema::hasTable('users_property_requests')) {
            try {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->index(['user_id', 'is_active', 'customer_id'], 'upr_user_active_customer_idx');
                    $table->index(['user_id', 'is_active', 'phone'], 'upr_user_active_phone_idx');
                    $table->index(['user_id', 'is_active', 'status_id'], 'upr_user_active_status_idx');
                    $table->index(['user_id', 'is_active', 'customers_hub_stage_id'], 'upr_user_active_hub_stage_idx');
                });
            } catch (\Throwable $e) {
                // ignore (index may already exist)
            }
        }

        if (Schema::hasTable('api_customers')) {
            try {
                Schema::table('api_customers', function (Blueprint $table) {
                    $table->index(['user_id', 'phone_number'], 'ac_user_phone_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('reminders')) {
            try {
                Schema::table('reminders', function (Blueprint $table) {
                    $table->index(['user_id', 'deleted_at', 'datetime'], 'rem_user_deleted_datetime_idx');
                    $table->index(['user_id', 'customer_id'], 'rem_user_customer_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('property_request_appointments')) {
            try {
                Schema::table('property_request_appointments', function (Blueprint $table) {
                    $table->index(['user_id', 'property_request_id', 'datetime'], 'pra_user_request_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('property_request_reminders')) {
            try {
                Schema::table('property_request_reminders', function (Blueprint $table) {
                    $table->index(['user_id', 'property_request_id', 'datetime'], 'prr_user_request_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('inquiry_appointments')) {
            try {
                Schema::table('inquiry_appointments', function (Blueprint $table) {
                    $table->index(['user_id', 'inquiry_id', 'datetime'], 'ia_user_inquiry_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('inquiry_reminders')) {
            try {
                Schema::table('inquiry_reminders', function (Blueprint $table) {
                    $table->index(['user_id', 'inquiry_id', 'datetime'], 'ir_user_inquiry_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users_property_requests')) {
            try {
                Schema::table('users_property_requests', function (Blueprint $table) {
                    $table->dropIndex('upr_user_active_customer_idx');
                    $table->dropIndex('upr_user_active_phone_idx');
                    $table->dropIndex('upr_user_active_status_idx');
                    $table->dropIndex('upr_user_active_hub_stage_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('api_customers')) {
            try {
                Schema::table('api_customers', function (Blueprint $table) {
                    $table->dropIndex('ac_user_phone_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('reminders')) {
            try {
                Schema::table('reminders', function (Blueprint $table) {
                    $table->dropIndex('rem_user_deleted_datetime_idx');
                    $table->dropIndex('rem_user_customer_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('property_request_appointments')) {
            try {
                Schema::table('property_request_appointments', function (Blueprint $table) {
                    $table->dropIndex('pra_user_request_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('property_request_reminders')) {
            try {
                Schema::table('property_request_reminders', function (Blueprint $table) {
                    $table->dropIndex('prr_user_request_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('inquiry_appointments')) {
            try {
                Schema::table('inquiry_appointments', function (Blueprint $table) {
                    $table->dropIndex('ia_user_inquiry_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }

        if (Schema::hasTable('inquiry_reminders')) {
            try {
                Schema::table('inquiry_reminders', function (Blueprint $table) {
                    $table->dropIndex('ir_user_inquiry_datetime_idx');
                });
            } catch (\Throwable $e) {
                // ignore
            }
        }
    }
};

