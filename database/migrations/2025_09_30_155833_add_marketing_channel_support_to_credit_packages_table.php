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
        Schema::table('credit_packages', function (Blueprint $table) {
            // Add marketing channel specific fields
            $table->boolean('supports_marketing_channels')->default(true)->after('is_active');
            $table->text('marketing_features')->nullable()->after('features'); // Marketing-specific features
            $table->integer('marketing_priority')->default(0)->after('sort_order'); // Priority for marketing usage
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('credit_packages', function (Blueprint $table) {
            $table->dropColumn(['supports_marketing_channels', 'marketing_features', 'marketing_priority']);
        });
    }
};