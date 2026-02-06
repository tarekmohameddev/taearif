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
        Schema::create('customer_assignment_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->comment('Tenant owner ID');
            $table->unsignedBigInteger('employee_id')->comment('Employee user ID');
            $table->boolean('is_active')->default(true);
            $table->json('rules')->comment('Array of rule conditions');
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'employee_id']);
            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_assignment_rules');
    }
};
