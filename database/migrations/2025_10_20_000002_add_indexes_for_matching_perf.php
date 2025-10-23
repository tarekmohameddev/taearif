<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Defensive: ensure indexes on common filter columns
        if (Schema::hasTable('user_properties')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->index('region_id', 'idx_up_region');
                $table->index('category_id', 'idx_up_category');
                $table->index('type', 'idx_up_type');
                $table->index('purpose', 'idx_up_purpose');
                $table->index('price', 'idx_up_price');
                $table->index('area', 'idx_up_area');
                $table->index('status', 'idx_up_status');
                $table->index('property_status', 'idx_up_prop_status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('user_properties')) {
            Schema::table('user_properties', function (Blueprint $table) {
                $table->dropIndex('idx_up_region');
                $table->dropIndex('idx_up_category');
                $table->dropIndex('idx_up_type');
                $table->dropIndex('idx_up_purpose');
                $table->dropIndex('idx_up_price');
                $table->dropIndex('idx_up_area');
                $table->dropIndex('idx_up_status');
                $table->dropIndex('idx_up_prop_status');
            });
        }
    }
};




