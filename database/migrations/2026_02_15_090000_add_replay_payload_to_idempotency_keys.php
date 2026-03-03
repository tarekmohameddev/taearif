<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            return;
        }

        Schema::table('idempotency_keys', function (Blueprint $table) {
            if (!Schema::hasColumn('idempotency_keys', 'reference_type')) {
                $table->string('reference_type')->nullable()->after('message_id');
            }
            if (!Schema::hasColumn('idempotency_keys', 'reference_id')) {
                $table->string('reference_id')->nullable()->after('reference_type');
            }
            if (!Schema::hasColumn('idempotency_keys', 'response_payload')) {
                $table->json('response_payload')->nullable()->after('reference_id');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('idempotency_keys')) {
            return;
        }

        Schema::table('idempotency_keys', function (Blueprint $table) {
            if (Schema::hasColumn('idempotency_keys', 'response_payload')) {
                $table->dropColumn('response_payload');
            }
            if (Schema::hasColumn('idempotency_keys', 'reference_id')) {
                $table->dropColumn('reference_id');
            }
            if (Schema::hasColumn('idempotency_keys', 'reference_type')) {
                $table->dropColumn('reference_type');
            }
        });
    }
};

