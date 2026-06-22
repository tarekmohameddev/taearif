<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->string('source_broker_type', 10)->nullable()->after('publish_status');
            $table->unsignedBigInteger('source_broker_id')->nullable()->after('source_broker_type');
            $table->string('source_broker_name', 191)->nullable()->after('source_broker_id');
            $table->string('source_broker_phone', 32)->nullable()->after('source_broker_name');

            $table->foreign('source_broker_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('user_properties', function (Blueprint $table) {
            $table->dropForeign(['source_broker_id']);
            $table->dropColumn([
                'source_broker_type',
                'source_broker_id',
                'source_broker_name',
                'source_broker_phone',
            ]);
        });
    }
};
