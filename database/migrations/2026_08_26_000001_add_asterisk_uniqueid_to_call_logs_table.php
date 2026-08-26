<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            // Asterisk Uniqueid of the originated (A-leg) channel, e.g. 1787700790.33
            $table->string('asterisk_uniqueid', 64)->nullable()->after('asterisk_channel');
            $table->index('asterisk_uniqueid');
        });
    }

    public function down(): void
    {
        Schema::table('call_logs', function (Blueprint $table) {
            $table->dropIndex(['asterisk_uniqueid']);
            $table->dropColumn('asterisk_uniqueid');
        });
    }
};
