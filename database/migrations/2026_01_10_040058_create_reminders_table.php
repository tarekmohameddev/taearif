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
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('api_customers')->onDelete('cascade');
            $table->foreignId('reminder_type_id')->constrained('reminder_types')->onDelete('restrict');
            $table->string('title', 255)->comment('Reminder title (custom or from type)');
            $table->text('description')->nullable();
            $table->dateTime('datetime')->comment('Reminder date and time');
            $table->tinyInteger('priority')->default(1)->comment('0=Low, 1=Medium, 2=High');
            $table->enum('status', ['pending', 'completed', 'overdue', 'cancelled'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('user_id', 'idx_user_id');
            $table->index('customer_id', 'idx_customer_id');
            $table->index('reminder_type_id', 'idx_reminder_type_id');
            $table->index('datetime', 'idx_datetime');
            $table->index('status', 'idx_status');
            $table->index('deleted_at', 'idx_deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reminders');
    }
};
