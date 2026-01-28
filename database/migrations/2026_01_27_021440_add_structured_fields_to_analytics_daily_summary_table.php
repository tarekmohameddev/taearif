<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Add structured fields to analytics_daily_summary table for GA4 analytics pipeline
     *
     * @return void
     */
    public function up()
    {
        Schema::table('analytics_daily_summary', function (Blueprint $table) {
            // Check if columns don't exist before adding (safe for re-running migrations)
            if (!Schema::hasColumn('analytics_daily_summary', 'total_page_views')) {
                $table->unsignedBigInteger('total_page_views')->default(0)->after('date');
            }
            if (!Schema::hasColumn('analytics_daily_summary', 'total_sessions')) {
                $table->unsignedBigInteger('total_sessions')->default(0)->after('total_page_views');
            }
            if (!Schema::hasColumn('analytics_daily_summary', 'total_users')) {
                $table->unsignedBigInteger('total_users')->default(0)->after('total_sessions');
            }
            if (!Schema::hasColumn('analytics_daily_summary', 'unique_pages')) {
                $table->unsignedInteger('unique_pages')->default(0)->after('total_users');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('analytics_daily_summary', function (Blueprint $table) {
            if (Schema::hasColumn('analytics_daily_summary', 'total_page_views')) {
                $table->dropColumn('total_page_views');
            }
            if (Schema::hasColumn('analytics_daily_summary', 'total_sessions')) {
                $table->dropColumn('total_sessions');
            }
            if (Schema::hasColumn('analytics_daily_summary', 'total_users')) {
                $table->dropColumn('total_users');
            }
            if (Schema::hasColumn('analytics_daily_summary', 'unique_pages')) {
                $table->dropColumn('unique_pages');
            }
        });
    }
};
