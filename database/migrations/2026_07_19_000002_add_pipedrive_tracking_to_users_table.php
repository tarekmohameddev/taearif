<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('pipedrive_person_id')->nullable()->after('phone_verified_at');
            $table->unsignedBigInteger('pipedrive_deal_id')->nullable()->after('pipedrive_person_id');
            $table->timestamp('pipedrive_synced_at')->nullable()->after('pipedrive_deal_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'pipedrive_person_id',
                'pipedrive_deal_id',
                'pipedrive_synced_at',
            ]);
        });
    }
};
