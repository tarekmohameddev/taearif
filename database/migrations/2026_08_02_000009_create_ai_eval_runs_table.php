<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_eval_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_id', 64)->unique();
            $table->string('git_commit', 40)->nullable();
            $table->json('scores');
            $table->json('per_turn_results')->nullable();
            $table->unsignedSmallInteger('total_turns')->default(0);
            $table->unsignedSmallInteger('passed_turns')->default(0);
            $table->boolean('passed')->default(false);
            $table->text('regression_diff')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_eval_runs');
    }
};
