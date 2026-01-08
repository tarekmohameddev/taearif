<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('api_themes_settings', function (Blueprint $table) {
            $table->boolean('is_free')->default(false)->after('popular');
            $table->boolean('is_enabled')->default(true)->after('is_free');
            $table->decimal('price', 10, 2)->nullable()->after('is_enabled');
            $table->string('currency', 3)->default('SAR')->after('price');
        });

        // Make category nullable
        Schema::table('api_themes_settings', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_themes_settings', function (Blueprint $table) {
            $table->dropColumn(['is_free', 'is_enabled', 'price', 'currency']);
        });

        // Revert category to not nullable (if needed)
        Schema::table('api_themes_settings', function (Blueprint $table) {
            $table->string('category')->nullable(false)->change();
        });
    }
};
