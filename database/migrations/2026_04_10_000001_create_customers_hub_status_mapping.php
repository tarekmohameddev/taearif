<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers_hub_status_mapping')) {
            Schema::create('customers_hub_status_mapping', function (Blueprint $table) {
                $table->id();
                $table->string('property_request_status_slug', 50)->unique();
                $table->enum('customers_hub_status', ['pending', 'in_progress', 'completed', 'dismissed']);
                $table->timestamps();
            });
        }

        $existingSlugs = DB::table('customers_hub_status_mapping')->pluck('property_request_status_slug')->toArray();

        $rows = [
            ['property_request_status_slug' => 'suspended', 'customers_hub_status' => 'pending'],
            ['property_request_status_slug' => 'in_progress', 'customers_hub_status' => 'in_progress'],
            ['property_request_status_slug' => 'waiting', 'customers_hub_status' => 'in_progress'],
            ['property_request_status_slug' => 'completed', 'customers_hub_status' => 'completed'],
            ['property_request_status_slug' => 'cancelled', 'customers_hub_status' => 'dismissed'],
        ];

        foreach ($rows as $row) {
            if (!in_array($row['property_request_status_slug'], $existingSlugs, true)) {
                DB::table('customers_hub_status_mapping')->insert([
                    'property_request_status_slug' => $row['property_request_status_slug'],
                    'customers_hub_status' => $row['customers_hub_status'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('customers_hub_status_mapping');
    }
};

