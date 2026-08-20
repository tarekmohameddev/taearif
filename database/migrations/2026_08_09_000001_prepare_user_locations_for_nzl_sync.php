<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Prepares user_cities / user_districts for the one-time nzl-backend sync.
 *
 * 1. region_id becomes nullable: 68 Saudi cities exist only inside the districts
 *    payload (as denormalized city_name_*) and carry no region from nzl. The region
 *    code embedded in the district id is not reliable (it matches nzl's own
 *    region_id for just 37 of 397 known cities), so NULL is recorded instead.
 *
 * 2. AUTO_INCREMENT is pushed clear of nzl's id space. Both tables use $table->id(),
 *    so after the sync the counter would sit at 11302271197 -- precisely the id nzl
 *    hands to its next district. Any locally created row would collide. Reserving a
 *    dedicated range makes the origin of a location readable from its id alone:
 *
 *        nzl-owned      : cities <= 23696,   districts 1xxxxxxxxxx
 *        taearif-native : cities >= 900001,  districts >= 90000000001
 */
return new class extends Migration
{
    const CITY_ID_BASE = 900001;
    const DISTRICT_ID_BASE = 90000000001;

    public function up()
    {
        Schema::table('user_cities', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable()->change();
        });

        $this->bumpAutoIncrement('user_cities', self::CITY_ID_BASE);
        $this->bumpAutoIncrement('user_districts', self::DISTRICT_ID_BASE);
    }

    public function down()
    {
        // The AUTO_INCREMENT bump is intentionally not reverted: lowering it back into
        // nzl's range would hand out colliding ids for any row created since.
        DB::table('user_cities')->whereNull('region_id')->update(['region_id' => 0]);

        Schema::table('user_cities', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')->nullable(false)->change();
        });
    }

    /**
     * MySQL ignores an AUTO_INCREMENT value at or below the current maximum id,
     * so this is safe to run before or after the sync inserts its rows.
     */
    private function bumpAutoIncrement($table, $value)
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$value}");
    }
};
