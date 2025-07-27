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
            // Add a new column 'message' to the users table
            $table->text('message')->nullable()->after('phone'); // Nullable to allow existing
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
            // Drop the 'message' column if it exists
            if (Schema::hasColumn('users', 'message')) {
                $table->dropColumn('message');
            }
        });
    }
};
