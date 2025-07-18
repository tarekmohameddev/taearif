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
        Schema::table('api_installations', function (Blueprint $table) {
            //
            $table->string('invoice_id')->nullable()->after('status');
            $table->string('recurring_id')->nullable()->after('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_installations', function (Blueprint $table) {
            //
            $table->dropColumn(['invoice_id', 'recurring_id']);
        });
    }
};
