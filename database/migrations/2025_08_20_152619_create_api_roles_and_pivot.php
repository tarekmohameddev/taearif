<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
      Schema::create('api_roles', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('name');
        $table->json('permissions')->nullable();
        $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->unique(['user_id','name']);
      });

      Schema::create('api_employee_role', function (Blueprint $table) {
        $table->unsignedBigInteger('employee_id');
        $table->unsignedBigInteger('role_id');
        $table->timestamps();

        $table->primary(['employee_id','role_id']);
        $table->foreign('employee_id')->references('id')->on('api_employees')->onDelete('cascade');
        $table->foreign('role_id')->references('id')->on('api_roles')->onDelete('cascade');
      });
    }
    public function down(): void {
      Schema::dropIfExists('api_employee_role');
      Schema::dropIfExists('api_roles');
    }
  };
