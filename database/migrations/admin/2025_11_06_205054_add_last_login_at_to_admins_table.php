<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('admins') && !Schema::hasColumn('admins', 'last_login_at')) {
            Schema::table('admins', function (Blueprint $table) {
                $columns = Schema::getColumnListing('admins');
                $hasRememberToken = in_array('remember_token', $columns, true);

                if ($hasRememberToken) {
                    $table->timestamp('last_login_at')->nullable()->after('remember_token');
                } else {
                    $table->timestamp('last_login_at')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('admins', 'last_login_at')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn('last_login_at');
            });
        }
    }
};

