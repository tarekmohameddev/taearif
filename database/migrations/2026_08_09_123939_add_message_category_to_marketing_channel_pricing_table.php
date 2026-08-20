<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('marketing_channel_pricing', function (Blueprint $table) {
            $table->string('message_category', 30)->default('default')->after('channel_type');
            $table->boolean('is_billable')->default(true)->after('is_active');
            $table->string('label_ar', 100)->nullable()->after('description_ar');
        });

        // Drop the single-column unique index and replace with composite
        DB::statement('ALTER TABLE marketing_channel_pricing DROP INDEX marketing_channel_pricing_channel_type_unique');
        DB::statement('ALTER TABLE marketing_channel_pricing ADD UNIQUE INDEX mcp_channel_category_unique (channel_type, message_category)');
        DB::statement('ALTER TABLE marketing_channel_pricing ADD INDEX mcp_channel_category_active_idx (channel_type, message_category, is_active)');

        // Backfill: existing whatsapp row -> 'marketing', all others -> 'default'
        DB::table('marketing_channel_pricing')
            ->where('channel_type', 'whatsapp')
            ->update(['message_category' => 'marketing']);
    }

    public function down()
    {
        // Remove the composite indexes
        DB::statement('ALTER TABLE marketing_channel_pricing DROP INDEX mcp_channel_category_unique');
        DB::statement('ALTER TABLE marketing_channel_pricing DROP INDEX mcp_channel_category_active_idx');

        // Restore whatsapp row to default (so re-adding the unique single-column index works)
        DB::table('marketing_channel_pricing')
            ->where('channel_type', 'whatsapp')
            ->update(['message_category' => 'default']);

        // Re-add the old single-column unique index
        DB::statement('ALTER TABLE marketing_channel_pricing ADD UNIQUE INDEX marketing_channel_pricing_channel_type_unique (channel_type)');

        Schema::table('marketing_channel_pricing', function (Blueprint $table) {
            $table->dropColumn(['message_category', 'is_billable', 'label_ar']);
        });
    }
};
