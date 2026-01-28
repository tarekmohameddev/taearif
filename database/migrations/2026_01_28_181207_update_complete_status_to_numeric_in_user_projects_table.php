<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // First, update existing string values to numeric values
        // Use raw SQL with CASE statement to handle all variations at once
        DB::statement("
            UPDATE user_projects
            SET complete_status = CASE
                WHEN LOWER(complete_status) IN ('in progress', 'inprogress') THEN '0'
                WHEN LOWER(complete_status) LIKE '%complete%' THEN '1'
                WHEN complete_status IS NULL OR complete_status = '' THEN '0'
                WHEN complete_status IN ('0', '1', '2') THEN complete_status
                ELSE '0'
            END
        ");

        // Now change the column type from string to tinyInteger using raw SQL
        // Using raw SQL because Doctrine DBAL doesn't support tinyInteger with change()
        $driverName = DB::connection()->getDriverName();

        if ($driverName === 'mysql') {
            DB::statement('ALTER TABLE `user_projects` MODIFY COLUMN `complete_status` TINYINT NOT NULL DEFAULT 0');
        } elseif ($driverName === 'pgsql') {
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status TYPE SMALLINT USING CAST(complete_status AS SMALLINT)');
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status SET DEFAULT 0');
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status SET NOT NULL');
        } else {
            // For SQLite or other databases
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status INTEGER DEFAULT 0 NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Convert numeric values back to strings before changing column type
        DB::table('user_projects')
            ->where('complete_status', 0)
            ->orWhere('complete_status', '0')
            ->update(['complete_status' => 'In Progress']);

        DB::table('user_projects')
            ->where('complete_status', 1)
            ->orWhere('complete_status', '1')
            ->update(['complete_status' => 'Completed']);

        // Change column type back to string using raw SQL
        $driverName = DB::connection()->getDriverName();

        if ($driverName === 'mysql') {
            DB::statement('ALTER TABLE `user_projects` MODIFY COLUMN `complete_status` VARCHAR(255) NULL');
        } elseif ($driverName === 'pgsql') {
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status TYPE VARCHAR(255) USING CAST(complete_status AS VARCHAR(255))');
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status DROP NOT NULL');
        } else {
            // For SQLite or other databases
            DB::statement('ALTER TABLE user_projects ALTER COLUMN complete_status VARCHAR(255)');
        }
    }
};
