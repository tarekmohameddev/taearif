<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rm_expenses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rental_id');
            $table->string('expense_name', 255); // اسم المصروف
            $table->string('image_path')->nullable(); // الصورة
            $table->enum('amount_type', ['percentage', 'fixed']); // نسبة او مبلغ ثابت
            $table->decimal('amount_value', 10, 2); // قيمة المبلغ
            $table->enum('cost_center', ['tenant', 'owner']); // مركز التكلفة
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('rental_id')->references('id')->on('rm_rentals')->onDelete('cascade');

            // Indexes
            $table->index(['user_id', 'rental_id']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rm_expenses');
    }
};