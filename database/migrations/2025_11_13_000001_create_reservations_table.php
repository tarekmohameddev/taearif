<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop legacy reservations table if it exists (to avoid schema conflicts)
        Schema::dropIfExists('reservations');

        Schema::create('reservations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('property_id')->index();
            $table->string('type', 10)->index(); // rent | buy
            $table->string('status', 20)->default('pending')->index(); // pending | accepted | rejected
            $table->string('customer_name', 100);
            $table->string('customer_phone', 40);
            $table->date('desired_date')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('deposit_amount', 12, 2)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('property_id')->references('id')->on('user_properties')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};


