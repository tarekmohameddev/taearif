<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            return;
        }

        Schema::table('property_request_statuses', function (Blueprint $table) {
            if (!Schema::hasColumn('property_request_statuses', 'user_id')) {
                $table->foreignId('user_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('users')
                    ->onDelete('cascade');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            return;
        }

        Schema::table('property_request_statuses', function (Blueprint $table) {
            if (Schema::hasColumn('property_request_statuses', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
};
