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
        Schema::create('rm_reminders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->enum('type', ['payment_due', 'payment_overdue', 'contract_expiring']);
            $table->string('entity_type', 40);
            $table->unsignedBigInteger('entity_id');
            $table->unsignedBigInteger('rental_id')->index();
            $table->date('due_on')->index();
            $table->string('message', 255);
            $table->enum('status', ['pending', 'sent', 'dismissed', 'snoozed'])->default('pending')->index();
            $table->date('snooze_until')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rm_reminders');
    }
};
