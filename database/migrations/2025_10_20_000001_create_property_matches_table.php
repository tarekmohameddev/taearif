<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('property_matches', function (Blueprint $table) {
            $table->id();
            $table->enum('request_type', ['web', 'whatsapp']);
            $table->unsignedBigInteger('request_id');
            $table->unsignedBigInteger('property_id');

            $table->unsignedTinyInteger('match_score')->default(0);
            $table->unsignedTinyInteger('database_score')->default(0);
            $table->unsignedTinyInteger('ai_score')->default(0);

            $table->text('match_explanation')->nullable();
            $table->json('matched_criteria')->nullable();

            $table->boolean('is_reviewed')->default(false);
            $table->boolean('is_contacted')->default(false);

            $table->timestamps();

            $table->unique(['request_type', 'request_id', 'property_id'], 'uniq_req_prop');
            $table->index(['request_type', 'request_id'], 'idx_req');
            $table->index('property_id', 'idx_prop');
            $table->index('match_score', 'idx_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_matches');
    }
};




