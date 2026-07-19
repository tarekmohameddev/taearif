<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->boolean('pipedrive_sync_enabled')->default(false)->after('secret_path');
            $table->string('pipedrive_api_token')->nullable()->after('pipedrive_sync_enabled');
            $table->string('pipedrive_base_url')->nullable()->after('pipedrive_api_token');
            $table->unsignedInteger('pipedrive_pipeline_id')->nullable()->after('pipedrive_base_url');
            $table->unsignedInteger('pipedrive_stage_id')->nullable()->after('pipedrive_pipeline_id');
            $table->string('pipedrive_deal_title_prefix')->nullable()->after('pipedrive_stage_id');
        });
    }

    public function down(): void
    {
        Schema::table('basic_settings', function (Blueprint $table) {
            $table->dropColumn([
                'pipedrive_sync_enabled',
                'pipedrive_api_token',
                'pipedrive_base_url',
                'pipedrive_pipeline_id',
                'pipedrive_stage_id',
                'pipedrive_deal_title_prefix',
            ]);
        });
    }
};
