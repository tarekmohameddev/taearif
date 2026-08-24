<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_data_import_batches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_id');
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->string('file_path');
            $table->string('original_filename')->nullable();
            $table->boolean('update_existing')->default(false);
            $table->enum('status', ['pending', 'processing', 'done', 'failed'])->default('pending');
            $table->json('result')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->foreign('owner_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('admin_id')->references('id')->on('admins')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_data_import_batches');
    }
};
