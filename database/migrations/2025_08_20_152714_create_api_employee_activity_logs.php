<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
      Schema::create('api_employee_activity_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('actor_type');
        $table->unsignedBigInteger('actor_id')->nullable();
        $table->string('action');
        $table->string('target_type')->nullable();
        $table->unsignedBigInteger('target_id')->nullable();
        $table->json('old_values')->nullable();
        $table->json('new_values')->nullable();
        $table->string('ip')->nullable();
        $table->text('user_agent')->nullable();
        $table->timestamps();

        $table->index(['user_id','action','target_type','target_id'],'emp_actlog_idx');
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
      });
    }
    public function down(): void {
      Schema::dropIfExists('api_employee_activity_logs');
    }
  };
