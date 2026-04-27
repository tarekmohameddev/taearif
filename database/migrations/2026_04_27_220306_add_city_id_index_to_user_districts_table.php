<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        if (! Schema::hasTable('user_districts') || ! Schema::hasColumn('user_districts', 'city_id')) {
            return;
        }

        $indexExists = false;

        try {
            $indexes = DB::select("SHOW INDEX FROM user_districts WHERE Column_name = 'city_id'");
            $indexExists = count($indexes) > 0;
        } catch (\Throwable $e) {
            // If SHOW INDEX is not supported (non-MySQL), let Schema builder try.
        }

        if ($indexExists) {
            return;
        }

        Schema::table('user_districts', function (Blueprint $table) {
            $table->index('city_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (! Schema::hasTable('user_districts')) {
            return;
        }

        $indexName = 'user_districts_city_id_index';

        $indexExists = false;

        try {
            $indexes = DB::select("SHOW INDEX FROM user_districts WHERE Key_name = '{$indexName}'");
            $indexExists = count($indexes) > 0;
        } catch (\Throwable $e) {
            // Best-effort: try drop if it exists (DB will error if not).
            $indexExists = true;
        }

        if (! $indexExists) {
            return;
        }

        Schema::table('user_districts', function (Blueprint $table) use ($indexName) {
            $table->dropIndex($indexName);
        });
    }
};
