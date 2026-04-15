<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        Schema::create('customers_hub_stage_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('stage_id', 50);

            $table->string('stage_name_ar', 255)->nullable();
            $table->string('stage_name_en', 255)->nullable();
            $table->string('color', 7)->nullable();
            $table->integer('order')->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'stage_id'], 'ch_stage_overrides_user_stage_unique');
            $table->index(['user_id', 'stage_id'], 'ch_stage_overrides_user_stage_index');

            $table->foreign('stage_id')
                ->references('stage_id')
                ->on('customers_hub_stages')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_hub_stage_overrides');
    }
};

