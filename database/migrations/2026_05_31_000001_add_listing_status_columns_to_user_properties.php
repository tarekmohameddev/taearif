<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->string('listing_purpose', 10)->nullable()->after('purpose');
            $table->string('unit_status', 10)->nullable()->after('listing_purpose');
            $table->string('publish_status', 10)->nullable()->after('unit_status');

            $table->index(['user_id', 'listing_purpose']);
            $table->index(['user_id', 'unit_status']);
            $table->index(['user_id', 'publish_status']);
        });
    }

    public function down(): void
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'listing_purpose']);
            $table->dropIndex(['user_id', 'unit_status']);
            $table->dropIndex(['user_id', 'publish_status']);

            $table->dropColumn(['listing_purpose', 'unit_status', 'publish_status']);
        });
    }
};
