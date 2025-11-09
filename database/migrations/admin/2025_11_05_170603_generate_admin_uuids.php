<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Generate UUIDs for all admins that don't have one
        $admins = DB::table('admins')->get();

        foreach ($admins as $admin) {
            DB::table('admins')
                ->where('id', $admin->id)
                ->update(['uuid' => (string) Str::uuid()]);
        }

        // Add unique constraint
        Schema::table('admins', function ($table) {
            $table->unique('uuid');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('admins', function ($table) {
            $table->dropUnique(['uuid']);
        });
    }
};

