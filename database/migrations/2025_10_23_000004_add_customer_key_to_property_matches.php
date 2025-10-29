<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('property_matches')) {
            Schema::table('property_matches', function (Blueprint $table) {
                $table->string('customer_key', 32)->nullable()->after('user_id');
                $table->index(['user_id', 'customer_key'], 'idx_pm_user_customer');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('property_matches')) {
            Schema::table('property_matches', function (Blueprint $table) {
                $table->dropIndex('idx_pm_user_customer');
                $table->dropColumn('customer_key');
            });
        }
    }
};


