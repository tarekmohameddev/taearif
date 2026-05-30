<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rm_maintenance_tickets', function (Blueprint $table) {
            // Make rental_id nullable to support building-level maintenance
            // (common-area tickets like elevator, entrance lights — no active rental)
            $table->unsignedBigInteger('rental_id')->nullable()->change();

            $table->unsignedBigInteger('unit_id')
                  ->nullable()
                  ->after('rental_id');
            $table->unsignedBigInteger('building_id')
                  ->nullable()
                  ->after('unit_id');

            $table->foreign('unit_id')
                  ->references('id')
                  ->on('user_properties')
                  ->onDelete('set null');
            $table->foreign('building_id')
                  ->references('id')
                  ->on('buildings')
                  ->onDelete('set null');

            $table->index('unit_id', 'idx_maintenance_unit_id');
            $table->index('building_id', 'idx_maintenance_building_id');
        });
    }

    public function down(): void
    {
        Schema::table('rm_maintenance_tickets', function (Blueprint $table) {
            $table->dropForeign(['unit_id']);
            $table->dropForeign(['building_id']);
            $table->dropIndex('idx_maintenance_unit_id');
            $table->dropIndex('idx_maintenance_building_id');
            $table->dropColumn(['unit_id', 'building_id']);
            $table->unsignedBigInteger('rental_id')->nullable(false)->change();
        });
    }
};
