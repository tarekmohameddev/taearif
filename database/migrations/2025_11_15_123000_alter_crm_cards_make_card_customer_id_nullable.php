<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_cards', function (Blueprint $table) {
            // Requires doctrine/dbal for change(); if not installed, run a raw SQL in your environment
            $table->unsignedBigInteger('card_customer_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('card_customer_id')->nullable(false)->change();
        });
    }
};


