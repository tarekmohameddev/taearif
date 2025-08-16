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
            $table->foreignId('procedure_id')
                ->nullable()
                ->after('stage_id')
                ->constrained('users_api_customers_procedures')
                ->nullOnDelete();
            $table->index(['user_id','procedure_id']);
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
            $table->dropConstrainedForeignId('procedure_id');
        });
    }
};
