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
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->string('account_type')->default('tenant')->after('tenant_id');
            $table->boolean('active')->default(true)->after('account_type');
            $table->timestamp('last_login_at')->nullable()->after('active');

            $table->foreign('tenant_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['tenant_id', 'account_type']);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropColumn(['tenant_id','account_type','active','last_login_at']);
        });
    }
};
