<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wa_ai_excluded_phones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('wa_number_id');
            $table->string('phone', 30);
            $table->timestamps();

            $table->unique(['wa_number_id', 'phone']);
            $table->index('wa_number_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wa_ai_excluded_phones');
    }
};
