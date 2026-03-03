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
    public function up(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->string('referral_source', 50)->nullable()->after('source')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('users_property_requests', function (Blueprint $table) {
            $table->dropIndex(['referral_source']);
            $table->dropColumn('referral_source');
        });
    }
};

