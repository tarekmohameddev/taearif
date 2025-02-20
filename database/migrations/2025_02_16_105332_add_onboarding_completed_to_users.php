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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('onboarding_completed')->default(false);
            $table->string('industry_type')->nullable();
            $table->text('short_description')->nullable();
            $table->string('logo')->nullable();
            $table->string('icon')->nullable();
            $table->string('primary_color')->default('#000000');
            // $table->boolean('is_onboarded')->default(false);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed');
            $table->dropColumn('industry_type');
            $table->dropColumn('short_description');
            $table->dropColumn('logo');
            $table->dropColumn('icon');
            $table->dropColumn('primary_color');
        });
    }
};
