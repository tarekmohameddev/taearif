<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatsappUsersTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('whatsapp_users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('number')->nullable(); 
            $table->string('name')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['active', 'inactive', 'blocked'])->default('active');
            $table->enum('request_status', ['active', 'pending', 'rejected'])->default('pending');
            $table->string('token')->nullable(); 
            $table->string('phone_id')->nullable();
            $table->string('business_id')->nullable();
            $table->timestamps();

            // Add foreign key if needed
            // $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_users');
    }
}
