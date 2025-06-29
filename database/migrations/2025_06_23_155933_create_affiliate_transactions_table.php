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
        Schema::create('affiliate_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('api_affiliate_users')->onDelete('cascade');
            $table->enum('type', ['pending','collected'])->index();
            $table->string('image')->nullable();
            $table->decimal('amount', 10, 2);
            $table->string('note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('affiliate_transactions');
    }
};
