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
        Schema::create('owner_rental_property', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_rental_id')->constrained('owner_rentals')->onDelete('cascade');
            $table->foreignId('property_id')->constrained('user_properties')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['owner_rental_id', 'property_id']);

            // Indexes
            $table->index('owner_rental_id');
            $table->index('property_id');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('owner_rental_property');
    }
};

