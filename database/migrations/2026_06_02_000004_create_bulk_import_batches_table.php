<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_import_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('building_id')->nullable();
            $table->enum('source', ['table', 'excel']);
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->enum('publish_status', ['draft', 'published'])->default('draft');
            $table->unsignedInteger('total')->default(0);
            $table->unsignedInteger('succeeded')->default(0);
            $table->unsignedInteger('failed')->default(0);
            $table->json('preview_data')->nullable();
            $table->json('report')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('project_id')->references('id')->on('user_projects')->nullOnDelete();
            $table->foreign('building_id')->references('id')->on('buildings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_import_batches');
    }
};
