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
        Schema::table('user_seos', function (Blueprint $table) {
            $table->string('meta_keyword_properties')->nullable();
            $table->text('meta_description_properties')->nullable();
            $table->string('meta_keyword_projects')->nullable();
            $table->text('meta_description_projects')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_seos', function (Blueprint $table) {
            $table->dropColumn(
                'meta_keyword_properties',
                'meta_description_properties',
                'meta_keyword_projects',
                'meta_description_projects'
            );
        });
    }
};
