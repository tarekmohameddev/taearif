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
        Schema::table('isthara', function (Blueprint $table) {
            $table->boolean('is_read')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('isthara', function (Blueprint $table) {
            // Drop the column if it exists
            if (Schema::hasColumn('isthara', 'is_read')) {
                $table->dropColumn('is_read');
            }
        });
    }
};
