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
        Schema::table('user_steps', function (Blueprint $table) {
            // Adding new columns for projects and properties
            $table->boolean('projects')->default(false)->after('user_id');
            $table->boolean('properties')->default(false)->after('projects');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_steps', function (Blueprint $table) {
            // Dropping the columns if they exist
            $table->dropColumn('projects');
            $table->dropColumn('properties');
        });
    }
};
