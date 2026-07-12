<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('city_id')->nullable()->after('longitude');
            $table->unsignedBigInteger('state_id')->nullable()->after('city_id');

            $table->index('city_id');
            $table->index('state_id');
        });
    }

    public function down(): void
    {
        Schema::table('user_projects', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropIndex(['state_id']);
            $table->dropColumn(['city_id', 'state_id']);
        });
    }
};
