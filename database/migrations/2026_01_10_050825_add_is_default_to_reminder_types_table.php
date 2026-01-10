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
        Schema::table('reminder_types', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_active');
            $table->index('is_default', 'idx_is_default');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('reminder_types', function (Blueprint $table) {
            $table->dropIndex('idx_is_default');
            $table->dropColumn('is_default');
        });
    }
};
