<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_daily_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('tenant_owner_id')->constrained('users')->cascadeOnDelete();
            $table->date('visited_on');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->unsignedInteger('visits_count')->default(1);
            $table->timestamps();

            $table->unique(['user_id', 'visited_on']);
            $table->index('visited_on');
            $table->index(['tenant_owner_id', 'visited_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_daily_visits');
    }
};
