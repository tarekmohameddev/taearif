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
        Schema::table('api_customers', function (Blueprint $table) {
            $table->unsignedBigInteger('responsible_employee_id')->nullable()->after('priority_id');
            $table->foreign('responsible_employee_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_customers', function (Blueprint $table) {
            $table->dropForeign(['responsible_employee_id']);
            $table->dropColumn('responsible_employee_id');
        });
    }
};
