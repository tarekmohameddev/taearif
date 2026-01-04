<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates table to store materialized Google Analytics data
     * for faster dashboard endpoint responses.
     *
     * @return void
     */
    public function up()
    {
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
            $table->json('data'); // Stores the full API response structure
            $table->timestamps();
            
            // Composite unique index prevents duplicates
            $table->unique(['tenant_id', 'date', 'metric_type'], 'analytics_unique');
            
            // Index for date range queries
            $table->index(['tenant_id', 'date', 'metric_type'], 'analytics_lookup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('analytics_daily_summary');
    }
};
