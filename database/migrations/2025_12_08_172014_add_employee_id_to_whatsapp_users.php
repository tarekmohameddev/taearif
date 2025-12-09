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
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->unsignedBigInteger('employee_id')->nullable()->after('user_id');
            $table->index('employee_id');
            $table->foreign('employee_id')
            ->references('id')
            ->on('users')
            ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('whatsapp_users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropIndex(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }
};
