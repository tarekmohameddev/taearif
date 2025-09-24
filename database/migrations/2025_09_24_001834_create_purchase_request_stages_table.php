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
        Schema::create('purchase_request_stages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->enum('stage_name', ['الحجز', 'العقد', 'الإنجاز', 'الاستلام']);
            $table->enum('status', ['الانتظار', 'قيد التنفيذ', 'مكتمل'])->default('الانتظار');
            $table->integer('stage_order'); // 1, 2, 3, 4
            $table->text('notes')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable(); // user who updated this stage
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            
            // Indexes and constraints
            $table->unique(['purchase_request_id', 'stage_name']);
            $table->index(['purchase_request_id', 'stage_order']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_request_stages');
    }
};
