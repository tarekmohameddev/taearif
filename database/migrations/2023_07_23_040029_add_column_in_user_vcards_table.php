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
        Schema::table('user_vcards', function (Blueprint $table) {
            $table->tinyInteger('preview_template')->default(0)->comment('1 == yes, 0== no');
            $table->string('template_img')->nullable();
            $table->integer('template_serial_number')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_vcards', function (Blueprint $table) {
            $table->dropColumn('preview_template');
            $table->dropColumn('template_img');
            $table->dropColumn('template_serial_number');
        });
    }
};
