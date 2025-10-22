<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Sync project_id from properties to rentals
        DB::statement("
            UPDATE rm_rentals
            SET project_id = (
                SELECT project_id
                FROM user_properties
                WHERE user_properties.id = rm_rentals.unit_id
            )
            WHERE project_id IS NULL
            AND unit_id IS NOT NULL
            AND EXISTS (
                SELECT 1
                FROM user_properties
                WHERE user_properties.id = rm_rentals.unit_id
                AND user_properties.project_id IS NOT NULL
            )
        ");

        // Sync building_id from properties to rentals
        DB::statement("
            UPDATE rm_rentals
            SET building_id = (
                SELECT building_id
                FROM user_properties
                WHERE user_properties.id = rm_rentals.unit_id
            )
            WHERE building_id IS NULL
            AND unit_id IS NOT NULL
            AND EXISTS (
                SELECT 1
                FROM user_properties
                WHERE user_properties.id = rm_rentals.unit_id
                AND user_properties.building_id IS NOT NULL
            )
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is not reversible as we don't know
        // which rentals originally had null values
        // If you need to reverse, you'll need to restore from backup
    }
};
