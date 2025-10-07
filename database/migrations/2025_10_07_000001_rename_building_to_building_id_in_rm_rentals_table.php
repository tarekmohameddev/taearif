<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rm_rentals', function (Blueprint $table) {
            // Check if 'building' column exists before dropping
            if (Schema::hasColumn('rm_rentals', 'building')) {
                $table->dropColumn('building');
            }
            
            // Add building_id as integer with foreign key
            if (!Schema::hasColumn('rm_rentals', 'building_id')) {
                $table->unsignedBigInteger('building_id')->nullable()->after('project_id');
                $table->foreign('building_id')->references('id')->on('buildings')->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rm_rentals', function (Blueprint $table) {
            // Drop foreign key and building_id column
            if (Schema::hasColumn('rm_rentals', 'building_id')) {
                $table->dropForeign(['building_id']);
                $table->dropColumn('building_id');
            }
            
            // Add back building as string
            if (!Schema::hasColumn('rm_rentals', 'building')) {
                $table->string('building', 100)->nullable()->after('project_id');
            }
        });
    }
};

