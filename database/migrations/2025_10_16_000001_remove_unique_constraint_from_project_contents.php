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
        // Schema::table('user_project_contents', function (Blueprint $table) {
        //     // Remove unique constraint on user_id + slug combination
        //     // Slugs don't need to be unique per tenant
        //     $table->dropUnique('project_contents_user_slug_unique');
        // });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_project_contents', function (Blueprint $table) {
            // Re-add unique constraint if rolling back
            $table->unique(['user_id', 'slug'], 'project_contents_user_slug_unique');
        });
    }
};

