<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('property_matches')) {
            Schema::table('property_matches', function (Blueprint $table) {
                $table->unsignedBigInteger('user_id')->after('id');
                // Adjust unique constraint to include user_id
                $table->dropUnique('uniq_req_prop');
                $table->unique(['user_id', 'request_type', 'request_id', 'property_id'], 'uniq_user_req_prop');
                $table->index('user_id', 'idx_pm_user');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('property_matches')) {
            Schema::table('property_matches', function (Blueprint $table) {
                $table->dropIndex('uniq_user_req_prop');
                $table->unique(['request_type', 'request_id', 'property_id'], 'uniq_req_prop');
                $table->dropIndex('idx_pm_user');
                $table->dropColumn('user_id');
            });
        }
    }
};




