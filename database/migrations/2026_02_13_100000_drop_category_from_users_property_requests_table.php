<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Drop the unused enum column "category" (سكني/تجاري/صناعي/زراعي) from users_property_requests.
     * The application uses category_id only.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users_property_requests', 'category')) {
            return;
        }

        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users_property_requests', 'category')) {
            return;
        }

        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->enum('category', ['سكني', 'تجاري', 'صناعي', 'زراعي'])->nullable()->after('districts_id');
        });
    }
};
