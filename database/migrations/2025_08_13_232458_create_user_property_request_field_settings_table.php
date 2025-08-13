<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_property_request_field_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id')->index();          // التينانت
            $table->string('field_key', 64);                         // اسم الحقل (key)
            $table->boolean('is_visible')->default(true);            // 1/0
            $table->boolean('is_required')->default(false);          // 1/0
            $table->smallInteger('sort_order')->nullable();          // ترتيب اختياري
            $table->string('label_ar')->nullable();                  // تسمية مخصّصة (اختياري)
            $table->string('label_en')->nullable();                  // تسمية مخصّصة (اختياري)
            $table->json('meta')->nullable();                        // أي إعدادات إضافية
            $table->timestamps();

            $table->unique(['user_id','field_key']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_property_request_field_settings');
    }
};
