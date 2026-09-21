<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_brand_source_assets', function (Blueprint $table) {
            $table->id();
            $table->char('source_url_hash', 64)->unique();
            $table->text('source_url');
            $table->char('source_content_hash', 64)->nullable();
            $table->char('active_asset_content_hash', 64)->nullable();
            $table->string('variant_disk')->nullable();
            $table->string('variant_path')->nullable();
            $table->text('variant_url')->nullable();
            $table->string('variant_mime', 100)->nullable();
            $table->unsignedBigInteger('variant_size')->nullable();
            $table->mediumText('inline_data')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('last_referenced_at')->nullable();
            $table->string('last_error_code')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_brand_source_assets');
    }
};
