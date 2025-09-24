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
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index(); // Multi-tenant key
            $table->string('request_number')->unique(); // PR-2024-001
            
            // Client Information (stored directly)
            $table->string('client_name');
            $table->string('client_email');
            $table->string('client_phone');
            $table->string('client_national_id')->nullable();
            
            // Property/Project Information
            $table->unsignedBigInteger('property_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            
            // Request Details
            $table->enum('priority', ['منخفضة', 'متوسطة', 'عالية', 'عاجل'])->default('متوسطة');
            $table->decimal('budget_amount', 15, 2)->nullable();
            $table->text('notes')->nullable();
            $table->text('additional_notes')->nullable();
            
            // Assignment and Status
            $table->unsignedBigInteger('assigned_to')->nullable(); // staff user_id
            $table->enum('overall_status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->integer('progress_percentage')->default(0);
            
            // Timestamps
            $table->timestamp('request_date')->default(now());
            $table->timestamp('expected_completion_date')->nullable();
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('property_id')->references('id')->on('user_properties')->onDelete('set null');
            $table->foreign('project_id')->references('id')->on('user_projects')->onDelete('set null');
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
            
            // Indexes
            $table->index(['overall_status', 'priority']);
            $table->index(['request_date']);
            $table->index(['assigned_to']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_requests');
    }
};
