<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users_property_requests', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('region')->default('الرياض');
            $table->string('category_id')->nullable()->comment('شقة, دور, تاون هاوس, فيلا, أرض, عمارة');
            $table->string('property_type ')->nullable()->comment('سكني, تجاري, صناعي, زراعي');
            $table->json('neighborhoods')->nullable();
            $table->integer('area_from')->nullable();
            $table->integer('area_to')->nullable();
            $table->enum('purchase_method')->nullable()->comment('نقدي, تقسيط, تمويل بنكي');
            $table->decimal('budget_from', 15, 2)->nullable();
            $table->decimal('budget_to', 15, 2)->nullable();
            $table->enum('seriousness')->nullable()->comment('مستعد فورًا, خلال شهر, خلال 3 أشهر, لاحقًا / استكشاف فقط');
            $table->enum('purchase_goal')->nullable()->comment('سكن خاص, استثمار وتأجير, بناء وبيع, مشروع تجاري');
            $table->boolean('wants_similar_offers')->default(false);
            $table->boolean('contact_on_whatsapp')->default(true);
            $table->text('notes')->nullable();
            $table->boolean('is_read')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users_property_requests');
    }
};

