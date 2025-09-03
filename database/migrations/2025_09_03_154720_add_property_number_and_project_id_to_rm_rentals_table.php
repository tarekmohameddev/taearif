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
        Schema::table('rm_rentals', function (Blueprint $table) {
            $table->string('property_number')->nullable()->after('tenant_full_name');
            $table->unsignedBigInteger('project_id')->nullable()->after('property_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rm_rentals', function (Blueprint $table) {
            $table->dropColumn(['property_number', 'project_id']);
        });
    }
};
