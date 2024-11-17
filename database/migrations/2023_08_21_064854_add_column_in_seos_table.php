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
        Schema::table('seos', function (Blueprint $table) {
            $table->text('website_template_keywords')->nullable();
            $table->text('website_template_description')->nullable();
            $table->text('vcard_template_keywords')->nullable();
            $table->text('vcard_template_description')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seos', function (Blueprint $table) {
            $table->dropColumn('website_template_keywords');
            $table->dropColumn('website_template_description');
            $table->dropColumn('vcard_template_keywords');
            $table->dropColumn('vcard_template_description');
        });
    }
};
