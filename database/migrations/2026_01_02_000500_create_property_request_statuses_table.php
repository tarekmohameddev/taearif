<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('property_request_statuses')) {
            Schema::create('property_request_statuses', function (Blueprint $table) {
                $table->id();
                $table->string('name_ar', 100);
                $table->string('name_en', 100)->nullable();
                $table->string('slug', 100)->unique();
                $table->unsignedTinyInteger('display_order')->default(1);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Check existing slugs to avoid duplicates
        $existingSlugs = DB::table('property_request_statuses')->pluck('slug')->toArray();

        $statusesToInsert = [
            [
                'name_ar' => 'جديد',
                'name_en' => 'New',
                'slug' => 'new',
                'display_order' => 1,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'متابعة',
                'name_en' => 'Follow Up',
                'slug' => 'follow_up',
                'display_order' => 2,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'تم العثور على عقار',
                'name_en' => 'Property Found',
                'slug' => 'property_found',
                'display_order' => 3,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'تم التعاقد',
                'name_en' => 'Contract Signed',
                'slug' => 'contract_signed',
                'display_order' => 4,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name_ar' => 'ملغي',
                'name_en' => 'Cancelled',
                'slug' => 'cancelled',
                'display_order' => 5,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($statusesToInsert as $status) {
            if (!in_array($status['slug'], $existingSlugs)) {
                DB::table('property_request_statuses')->insert($status);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('property_request_statuses');
    }
};

