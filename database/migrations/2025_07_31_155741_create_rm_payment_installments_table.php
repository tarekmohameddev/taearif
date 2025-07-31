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
        Schema::create('rm_payment_installments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('rental_id')->index();
            $table->unsignedBigInteger('contract_id')->index();

            $table->integer('sequence_no');
            $table->date('due_date')->index();
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['pending', 'paid', 'partial', 'overdue', 'void'])->default('pending')->index();
            $table->decimal('paid_amount', 12, 2)->default(0);
            $table->dateTime('paid_at')->nullable();
            $table->string('reference', 100)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'sequence_no']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rm_payment_installments');
    }
};
