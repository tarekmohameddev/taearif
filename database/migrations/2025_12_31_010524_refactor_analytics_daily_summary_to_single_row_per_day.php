<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Refactors analytics_daily_summary table from multiple rows per day
     * (one per metric_type) to a single row per tenant/day with all metrics
     * consolidated in a JSON data column.
     *
     * @return void
     */
    public function up()
    {
        // Drop the old table structure
        Schema::dropIfExists('analytics_daily_summary');
        
        // Create new table structure with single row per tenant/day
        Schema::create('analytics_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 255)->index();
            $table->date('date')->index();
            $table->json('data')->nullable(); // All metrics consolidated here
            $table->timestamps();
            
            // Unique constraint ensures one row per tenant per day
            $table->unique(['tenant_id', 'date'], 'analytics_tenant_date_unique');
            
            // Index for date range queries
            $table->index(['tenant_id', 'date'], 'analytics_lookup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * Restores the old table structure with metric_type column.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytics_daily_summary');
        
        Schema::create('analytics_daily_summary', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 255)->index();
            $table->date('date')->index();
            $table->enum('metric_type', [
                'visitors',
                'devices',
                'traffic_sources',
                'summary',
                'top_pages'
            ]);
            $table->json('data');
            $table->timestamps();
            
            $table->unique(['tenant_id', 'date', 'metric_type'], 'analytics_unique');
            $table->index(['tenant_id', 'date', 'metric_type'], 'analytics_lookup');
        });
    }
};
