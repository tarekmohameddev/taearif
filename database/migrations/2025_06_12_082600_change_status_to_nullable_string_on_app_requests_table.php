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
        Schema::table('app_requests', function (Blueprint $table) {
            $table->string('status', 50)
            ->nullable()
            ->default(null)
            ->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('app_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected', 'expired'])
            ->default('pending')
            ->change();
        });
    }
};
