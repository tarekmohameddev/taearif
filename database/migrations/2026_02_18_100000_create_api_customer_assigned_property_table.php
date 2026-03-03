<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Pivot table: many-to-many between api_customers and user_properties (assigned listings).
     */
    public function up(): void
    {
        Schema::create('api_customer_assigned_property', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('property_id');
            $table->timestamps();

            $table->foreign('customer_id')
                ->references('id')
                ->on('api_customers')
                ->onDelete('cascade');
            $table->foreign('property_id')
                ->references('id')
                ->on('user_properties')
                ->onDelete('cascade');

            $table->unique(['customer_id', 'property_id'], 'acap_customer_property_unique');
            $table->index('customer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_customer_assigned_property');
    }
};
