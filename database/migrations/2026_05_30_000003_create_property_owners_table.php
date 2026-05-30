<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_owners', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('property_id');
            $table->unsignedBigInteger('owner_id');
            $table->decimal('ownership_percentage', 5, 2)->default(100.00);
            $table->string('ownership_type', 50)->default('full');
            $table->timestamps();

            $table->foreign('property_id')
                  ->references('id')
                  ->on('user_properties')
                  ->onDelete('cascade');
            $table->foreign('owner_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');

            $table->unique(['property_id', 'owner_id'], 'uq_property_owner');
            $table->index('property_id', 'idx_property_owners_property_id');
            $table->index('owner_id', 'idx_property_owners_owner_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_owners');
    }
};
