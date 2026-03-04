<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Change source = 'website' to 'public_form' in users_property_requests
     * for backward compatibility with new source values.
     */
    public function up(): void
    {
        DB::table('users_property_requests')
            ->where('source', 'website')
            ->update(['source' => 'public_form']);
    }

    public function down(): void
    {
        DB::table('users_property_requests')
            ->where('source', 'public_form')
            ->update(['source' => 'website']);
    }
};
