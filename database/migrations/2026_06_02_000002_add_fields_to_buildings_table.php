<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->string('slug', 191)->nullable()->after('name');
            $table->text('description')->nullable()->after('slug');
            $table->string('featured_image', 500)->nullable()->after('description');
            $table->string('owner_name', 191)->nullable()->after('featured_image');
            $table->string('owner_phone', 32)->nullable()->after('owner_name');
            $table->string('address', 500)->nullable()->after('owner_phone');
            $table->unsignedBigInteger('city_id')->nullable()->after('address');
            $table->unsignedBigInteger('state_id')->nullable()->after('city_id');
            $table->decimal('latitude', 10, 8)->nullable()->after('state_id');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->boolean('is_archived')->default(false)->after('longitude');

            $table->unique(['user_id', 'slug']);
            $table->index(['user_id', 'is_archived']);
        });
    }

    public function down(): void
    {
        Schema::table('buildings', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'slug']);
            $table->dropIndex(['user_id', 'is_archived']);
            $table->dropColumn([
                'slug',
                'description',
                'featured_image',
                'owner_name',
                'owner_phone',
                'address',
                'city_id',
                'state_id',
                'latitude',
                'longitude',
                'is_archived',
            ]);
        });
    }
};
