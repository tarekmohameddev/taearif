<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customers_hub_stages', function (Blueprint $table) {
            $table->id();
            $table->string('stage_id', 50)->unique();
            $table->string('stage_name_ar', 255);
            $table->string('stage_name_en', 255);
            $table->string('color', 7);
            $table->integer('order');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('order');
            $table->index('is_active');
        });

        DB::table('customers_hub_stages')->insert([
            [
                'stage_id' => 'new_lead',
                'stage_name_ar' => 'عميل جديد',
                'stage_name_en' => 'New Lead',
                'color' => '#3b82f6',
                'order' => 1,
                'description' => 'Initial inquiry from any source',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'stage_id' => 'qualified',
                'stage_name_ar' => 'مؤهل',
                'stage_name_en' => 'Qualified',
                'color' => '#8b5cf6',
                'order' => 2,
                'description' => 'Budget, timeline, and preferences confirmed',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'stage_id' => 'negotiation',
                'stage_name_ar' => 'تفاوض',
                'stage_name_en' => 'Negotiation',
                'color' => '#f59e0b',
                'order' => 3,
                'description' => 'Price and terms discussion',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'stage_id' => 'closing',
                'stage_name_ar' => 'إتمام الصفقة',
                'stage_name_en' => 'Closing',
                'color' => '#22c55e',
                'order' => 4,
                'description' => 'Final transaction completion',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers_hub_stages');
    }
};
