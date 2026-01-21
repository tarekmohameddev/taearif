<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('api_posts', function (Blueprint $table) {
            $table->foreignId('thumbnail_id')->nullable()->after('published_at')
                ->constrained('api_media')->onDelete('set null');
        });

        Schema::table('api_posts', function (Blueprint $table) {
            $table->index('thumbnail_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_posts', function (Blueprint $table) {
            $table->dropForeign(['thumbnail_id']);
            $table->dropIndex(['thumbnail_id']);
            $table->dropColumn('thumbnail_id');
        });
    }
};
