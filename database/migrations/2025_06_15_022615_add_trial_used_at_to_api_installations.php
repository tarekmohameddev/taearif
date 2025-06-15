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
            $table->timestamp('trial_used_at')->nullable()->after('activated_at');
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
            $table->dropColumn('trial_used_at');
        });
    }
};
