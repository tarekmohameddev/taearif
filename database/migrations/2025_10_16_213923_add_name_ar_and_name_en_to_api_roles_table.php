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
        Schema::table('api_roles', function (Blueprint $table) {
            if (!Schema::hasColumn('api_roles', 'name_ar')) {
                $table->string('name_ar')->nullable()->after('name')->comment('Arabic display name');
            }
            if (!Schema::hasColumn('api_roles', 'name_en')) {
                $table->string('name_en')->nullable()->after('name_ar')->comment('English display name');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_roles', function (Blueprint $table) {
            $table->dropColumn(['name_ar', 'name_en']);
        });
    }
};
