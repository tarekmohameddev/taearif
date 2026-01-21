<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('users_property_requests', 'purpose')) {
                $table->enum('purpose', ['rent', 'sale'])->nullable()->after('property_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'purpose')) {
                $table->dropColumn('purpose');
            }
        });
    }
};

