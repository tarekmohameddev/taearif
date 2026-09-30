<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->string('property_code', 100)->nullable()->index()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropIndex(['property_code']);
            $table->dropColumn('property_code');
        });
    }
};
