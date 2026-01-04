<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_user_id']);
        });

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->renameColumn('whatsapp_user_id', 'whatsapp_number_id');
        });

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->foreign('whatsapp_number_id')
                ->references('id')
                ->on('whatsapp_users');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->dropForeign(['whatsapp_number_id']);
        });

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->renameColumn('whatsapp_number_id', 'whatsapp_user_id');
        });

        Schema::table('whatsapp_addons', function (Blueprint $table) {
            $table->foreign('whatsapp_user_id')
                ->references('id')
                ->on('whatsapp_users');
        });
    }
};

