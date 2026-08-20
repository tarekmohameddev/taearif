<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->enum('direction', ['inbound', 'outbound'])
                ->default('inbound')
                ->after('conversation_id');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'direction']);
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex(['conversation_id', 'direction']);
            $table->dropColumn('direction');
        });
    }
};
