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
        Schema::create('pageview_analytics', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id', 255);
            $table->string('page_slug', 255)->default(''); // Legacy field for backward compatibility
            $table->string('dynamic_slug', 255)->nullable();
            $table->string('full_path', 500)->nullable(); // Legacy field
            $table->string('page_path', 500); // From GA4 pagePath dimension - NOT NULL enforced
            $table->string('page_title', 500)->nullable(); // From GA4 pageTitle dimension
            $table->enum('page_type', ['page', 'post', 'project', 'property'])->nullable();
            $table->unsignedBigInteger('views_count')->default(1);
            $table->unsignedBigInteger('sessions_count')->default(0); // From GA4 sessions metric
            $table->unsignedBigInteger('users_count')->default(0); // From GA4 totalUsers metric
            $table->date('date_bucket');
            $table->timestamps();

            // Primary unique constraint for GA4 data (tenant + page_path + date)
            // This handles NULL-free uniqueness properly
            $table->unique(['tenant_id', 'page_path', 'date_bucket'], 'unique_tenant_path_date');
            
            // Legacy unique constraint for backward compatibility (kept for existing data)
            $table->unique(['tenant_id', 'page_slug', 'dynamic_slug', 'date_bucket'], 'unique_tenant_page_date');
            
            // Composite indexes for common query patterns
            // Note: Unique constraints above already serve as indexes for their columns
            $table->index(['tenant_id', 'date_bucket'], 'idx_tenant_date');
            $table->index(['tenant_id', 'page_type', 'date_bucket'], 'idx_tenant_type_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pageview_analytics');
    }
};
