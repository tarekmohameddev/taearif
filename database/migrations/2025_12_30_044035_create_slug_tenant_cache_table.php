<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates table to cache slug-to-tenant mappings
     * to eliminate N+1 database queries in GA data processing.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('slug_tenant_cache', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 500)->unique()->index();
            $table->enum('slug_type', ['property', 'project']);
            $table->string('tenant_id', 255)->index();
            $table->timestamp('cached_at')->index();
            $table->timestamps();
            
            // Index for tenant lookups
            $table->index(['tenant_id', 'slug_type'], 'slug_tenant_lookup');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('slug_tenant_cache');
    }
};
