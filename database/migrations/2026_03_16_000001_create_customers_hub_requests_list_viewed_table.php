<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Stores "last viewed at" per viewer (authenticated user) for the requests list.
     * One row per viewer (tenant owner or employee); used to compute isUpdated per action.
     */
    public function up(): void
    {
        Schema::create('customers_hub_requests_list_viewed', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique()->comment('Viewer: authenticated user who viewed the list');
            $table->timestamp('viewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_hub_requests_list_viewed');
    }
};
