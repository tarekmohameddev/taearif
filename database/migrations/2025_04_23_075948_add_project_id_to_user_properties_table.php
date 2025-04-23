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
        Schema::table('user_properties', function (Blueprint $table) {
            if (!Schema::hasColumn('user_properties', 'project_id')) {
                $table->unsignedBigInteger('project_id')->nullable()->after('user_id');

                $table->foreign('project_id')
                      ->references('id')->on('user_projects')
                      ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('user_properties', function (Blueprint $table) {
            if (Schema::hasColumn('user_properties', 'project_id')) {
                $table->dropForeign(['project_id']);
                $table->dropColumn('project_id');
            }
        });
    }

};
