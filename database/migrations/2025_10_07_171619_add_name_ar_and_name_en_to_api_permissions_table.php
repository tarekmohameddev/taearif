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
        Schema::table('api_permissions', function (Blueprint $table) {
            $table->string('name_ar')->nullable()->after('name')->comment('Arabic display name');
            $table->string('name_en')->nullable()->after('name_ar')->comment('English display name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_permissions', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en']);
        });
    }
};
