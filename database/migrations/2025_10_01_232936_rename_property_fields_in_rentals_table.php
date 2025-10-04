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
            $table->renameColumn('property_id', 'unit_id');
            $table->renameColumn('property_number', 'building');
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
            $table->renameColumn('unit_id', 'property_id');
            $table->renameColumn('building', 'property_number');
        });
    }
};
