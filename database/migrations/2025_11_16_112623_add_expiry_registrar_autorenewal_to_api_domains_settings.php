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
        Schema::table('api_domains_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('api_domains_settings', 'registrar')) {
                $table->string('registrar', 100)->nullable()->after('custom_name');
            }
            if (!Schema::hasColumn('api_domains_settings', 'expires_at')) {
                $table->date('expires_at')->nullable()->after('added_date');
            }
            if (!Schema::hasColumn('api_domains_settings', 'auto_renewal')) {
                $table->boolean('auto_renewal')->default(false)->after('ssl');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('api_domains_settings', function (Blueprint $table) {
            if (Schema::hasColumn('api_domains_settings', 'registrar')) {
                $table->dropColumn('registrar');
            }
            if (Schema::hasColumn('api_domains_settings', 'expires_at')) {
                $table->dropColumn('expires_at');
            }
            if (Schema::hasColumn('api_domains_settings', 'auto_renewal')) {
                $table->dropColumn('auto_renewal');
            }
        });
    }
};
