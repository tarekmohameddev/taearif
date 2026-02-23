<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_credits', function (Blueprint $table) {
            $table->unsignedInteger('reserved_credits')->default(0)->after('used_credits');
        });
    }

    public function down(): void
    {
        Schema::table('user_credits', function (Blueprint $table) {
            $table->dropColumn('reserved_credits');
        });
    }
};
