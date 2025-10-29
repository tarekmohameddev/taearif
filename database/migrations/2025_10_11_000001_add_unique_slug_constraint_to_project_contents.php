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
        //     // Add unique constraint on user_id + slug combination
        //     // This ensures each tenant has unique slugs
        //     $table->unique(['user_id', 'slug'], 'project_contents_user_slug_unique');

        //     // Add index for better query performance
        //     $table->index('slug', 'project_contents_slug_idx');
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
            $table->dropUnique('project_contents_user_slug_unique');
            $table->dropIndex('project_contents_slug_idx');
        });
    }
};

