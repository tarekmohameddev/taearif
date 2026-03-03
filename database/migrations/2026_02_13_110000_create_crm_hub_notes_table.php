<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_hub_notes', function (Blueprint $table) {
            $table->id();
            $table->morphs('noteable');
            $table->unsignedBigInteger('employee_id');
            $table->text('note');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_hub_notes');
    }
};
