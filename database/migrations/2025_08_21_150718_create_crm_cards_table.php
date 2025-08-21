<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('crm_cards', function (Blueprint $table) {
            $table->id();
            // tenant (user)
            $table->unsignedBigInteger('user_id')->index();
            $table->unsignedBigInteger('card_customer_id')->index();
            $table->text('card_content')->nullable();
            $table->enum('card_procedure', ['reminder', 'note', 'interaction', 'appointment'])->index();
            // flexible attachments / associations
            $table->unsignedBigInteger('card_project')->nullable();
            $table->unsignedBigInteger('card_property')->nullable();
            // when this card is scheduled/occurred
            $table->timestamp('card_date')->nullable()->index();
            $table->timestamps();
            $table->softDeletes();
            // FKs (adjust targets to your real tables)
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('card_customer_id')->references('id')->on('api_customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_cards');
    }
};
