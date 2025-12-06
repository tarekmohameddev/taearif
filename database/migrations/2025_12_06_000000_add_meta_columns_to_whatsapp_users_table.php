<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->text('access_token')->nullable()->after('token');
            $table->timestamp('token_expires_at')->nullable()->after('access_token');
            $table->string('waba_id')->nullable()->after('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropColumn(['access_token', 'token_expires_at', 'waba_id']);
        });
    }
};


