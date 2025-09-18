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
        // Check if email_templates table exists and has data
        if (Schema::hasTable('email_templates')) {
            // Get all records with empty or null names
            $recordsWithEmptyNames = DB::table('email_templates')
                ->where(function($query) {
                    $query->whereNull('name')
                          ->orWhere('name', '')
                          ->orWhere('name', ' ');
                })
                ->get();

            // Update each record with a unique name
            foreach ($recordsWithEmptyNames as $index => $record) {
                $uniqueName = 'email_template_' . $record->id . '_' . time() . '_' . $index;
                DB::table('email_templates')
                    ->where('id', $record->id)
                    ->update(['name' => $uniqueName]);
            }

            // Also handle any other potential duplicates by making them unique
            $duplicateNames = DB::table('email_templates')
                ->select('name')
                ->groupBy('name')
                ->havingRaw('COUNT(*) > 1')
                ->get();

            foreach ($duplicateNames as $duplicate) {
                $records = DB::table('email_templates')
                    ->where('name', $duplicate->name)
                    ->orderBy('id')
                    ->get();

                // Keep the first record as is, update the rest
                for ($i = 1; $i < count($records); $i++) {
                    $uniqueName = $duplicate->name . '_' . $records[$i]->id . '_' . time();
                    DB::table('email_templates')
                        ->where('id', $records[$i]->id)
                        ->update(['name' => $uniqueName]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // This migration only fixes data, no schema changes to reverse
    }
};
