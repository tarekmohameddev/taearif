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
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Package name (e.g., "Basic Package")
            $table->string('name_ar')->nullable(); // Arabic name
            $table->text('description')->nullable(); // Package description
            $table->text('description_ar')->nullable(); // Arabic description
            $table->integer('credits'); // Number of credits in package
            $table->decimal('price', 10, 2); // Price in SAR
            $table->string('currency', 3)->default('SAR'); // Currency code
            $table->decimal('discount_percentage', 5, 2)->nullable(); // Discount percentage (e.g., 20.00 for 20%)
            $table->boolean('is_popular')->default(false); // Most popular package
            $table->boolean('is_active')->default(true); // Package availability
            $table->integer('sort_order')->default(0); // Display order
            $table->json('features')->nullable(); // Additional features (JSON array)
            $table->timestamps();

            // Indexes for performance
            $table->index(['is_active', 'sort_order']);
            $table->index(['is_popular']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_packages');
    }
};
