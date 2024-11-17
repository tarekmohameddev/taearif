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
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->tinyInteger('vcard_section')->default(1);
            $table->string('vcard_section_title')->nullable();
            $table->string('vcard_section_subtitle')->nullable();
            $table->string('partners_section_title')->nullable();
            $table->string('partners_section_subtitle')->nullable();
            $table->text('pricing_text')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn('vcard_section');
            $table->dropColumn('partners_section_title');
            $table->dropColumn('partners_section_subtitle');
            $table->dropColumn('pricing_text');
            $table->dropColumn('vcard_section_title');
            $table->dropColumn('vcard_section_subtitle');
        });
    }
};
