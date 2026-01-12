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
        Schema::create('reminder_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name', 255)->comment('Type name (e.g., "Call", "Meeting")');
            $table->string('name_ar', 255)->nullable()->comment('Arabic name');
            $table->text('description')->nullable();
            $table->string('color', 50)->default('#6366f1')->comment('Hex color for UI');
            $table->string('icon', 100)->default('Bell')->comment('Icon name for UI');
            $table->integer('order')->default(0)->comment('Display order');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id', 'idx_user_id');
            $table->index('is_active', 'idx_is_active');
            $table->index('deleted_at', 'idx_deleted_at');
            
            // Unique constraint: name must be unique per user
            $table->unique(['user_id', 'name'], 'reminder_types_user_name_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminder_types');
    }
};
