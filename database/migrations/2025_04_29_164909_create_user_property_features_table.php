<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::create('user_property_features', function (Blueprint $table) {
            $table->id();
            $table->integer('floor_number')->nullable(); // رقم الطابق
            $table->integer('floors')->nullable(); // عدد الطوابق
            $table->integer('water_rooms')->nullable(); // عدد دورات المياه
            $table->integer('rooms')->nullable(); // عدد الغرف
            $table->integer('driver_room')->nullable(); // غرفة السائق
            $table->integer('maid_room')->nullable(); // غرفة الخادمة
            $table->integer('dining_room')->nullable(); // غرفة الطعام
            $table->integer('living_room')->nullable(); // الصالة
            $table->integer('swimming_pool')->nullable(); // المسبح
            $table->integer('basement')->nullable(); // القبو
            $table->integer('storage')->nullable(); // المخزن
            $table->integer('majlis')->nullable(); // المجلس
            $table->integer('balcony')->nullable(); // الشرفة
            $table->integer('kitchen')->nullable(); // المطبخ
            $table->integer('garden')->nullable(); // الحديقة
            $table->integer('annex')->nullable(); // الملحق
            $table->boolean('elevator')->default(false); // المصعد
            $table->boolean('dedicated_parking')->default(false); // موقف سيارة مخصص
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_property_features');
    }
};
