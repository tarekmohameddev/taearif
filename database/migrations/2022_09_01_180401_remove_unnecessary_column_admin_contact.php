<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUnnecessaryColumnAdminContact extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('basic_settings', function ($table) {
            $table->dropColumn(['contact_form_title', 'contact_info_title', 'contact_text']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_settings', function ($table) {
            $table->string('contact_form_title');
            $table->string('contact_info_title');
            $table->string('contact_text');
        });
    }
}
