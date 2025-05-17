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
        Schema::table('api_menu_items', function (Blueprint $table) {
            $table->string('text')->nullable();
            $table->string('type')->nullable();
            $table->string('target')->default('_self');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_menu_items', function (Blueprint $table) {

            if (Schema::hasColumn('api_menu_items', 'text')) {
                $table->dropColumn('text');
            }
            if (Schema::hasColumn('api_menu_items', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('api_menu_items', 'target')) {
                $table->dropColumn('target');
            }
        });
    }
};
