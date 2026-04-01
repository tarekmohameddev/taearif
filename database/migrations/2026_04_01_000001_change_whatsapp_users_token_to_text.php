<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->text('token')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->string('token')->nullable()->change();
        });
    }
};

