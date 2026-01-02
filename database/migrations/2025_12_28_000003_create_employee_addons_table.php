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
        Schema::create('employee_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // Tenant owner
            $table->foreignId('plan_id')->constrained('employee_addon_plans')->onDelete('cascade');
            $table->unsignedInteger('qty')->default(1);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('payment_ref')->unique();
            $table->string('gateway_transaction_id')->nullable();
            $table->timestamp('expire_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_addons');
    }
};

