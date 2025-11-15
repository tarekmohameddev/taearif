<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->unsignedBigInteger('card_request_id')->nullable()->index()->after('card_customer_id');
            // Optionally add FK if crm_requests table exists and you want enforced integrity:
            // $table->foreign('card_request_id')->references('id')->on('crm_requests')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('crm_cards', function (Blueprint $table) {
            $table->dropColumn('card_request_id');
        });
    }
};


