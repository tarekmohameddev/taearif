<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'neighborhood_id')) {
                $table->renameColumn('neighborhood_id', 'districts_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            if (Schema::hasColumn('users_property_requests', 'districts_id')) {
                $table->renameColumn('districts_id', 'neighborhood_id');
            }
        });
    }
};

