<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customers_hub_stages')) {
            return;
        }

        if (! Schema::hasColumn('customers_hub_stages', 'user_id') || ! Schema::hasColumn('customers_hub_stages', 'is_system')) {
            return;
        }

        $legacyStageIds = ['qualified', 'negotiation'];

        $templates = DB::table('customers_hub_stages')
            ->whereNull('user_id')
            ->whereIn('stage_id', $legacyStageIds)
            ->get(['stage_id', 'stage_name_ar', 'stage_name_en', 'color', 'order', 'description', 'is_active'])
            ->keyBy('stage_id');

        if ($templates->isEmpty()) {
            return;
        }

        $tenantIds = collect()
            ->merge(DB::table('api_customers')->whereIn('customers_hub_stage_id', $legacyStageIds)->pluck('user_id'))
            ->merge(DB::table('users_property_requests')->whereIn('customers_hub_stage_id', $legacyStageIds)->pluck('user_id'))
            ->merge(DB::table('api_customer_inquiry')->whereIn('stage_id', $legacyStageIds)->pluck('user_id'))
            ->filter()
            ->unique()
            ->values();

        foreach ($tenantIds as $tenantUserId) {
            $tenantUserId = (int) $tenantUserId;
            if ($tenantUserId <= 0) {
                continue;
            }

            foreach ($legacyStageIds as $legacyStageId) {
                $template = $templates->get($legacyStageId);
                if (! $template) {
                    continue;
                }

                $used = false;
                $used = $used || DB::table('api_customers')
                    ->where('user_id', $tenantUserId)
                    ->where('customers_hub_stage_id', $legacyStageId)
                    ->exists();
                $used = $used || DB::table('users_property_requests')
                    ->where('user_id', $tenantUserId)
                    ->where('customers_hub_stage_id', $legacyStageId)
                    ->exists();
                $used = $used || DB::table('api_customer_inquiry')
                    ->where('user_id', $tenantUserId)
                    ->where('stage_id', $legacyStageId)
                    ->exists();

                if (! $used) {
                    continue;
                }

                $newStageId = 'ch_' . Str::lower((string) Str::ulid());

                // Create tenant-owned stage row.
                DB::table('customers_hub_stages')->insert([
                    'user_id' => $tenantUserId,
                    'is_system' => false,
                    'stage_id' => $newStageId,
                    'stage_name_ar' => $template->stage_name_ar,
                    'stage_name_en' => $template->stage_name_en,
                    'color' => $template->color,
                    'order' => (int) $template->order,
                    'description' => $template->description,
                    'is_active' => (bool) ($template->is_active ?? true),
                    'is_global' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Update references for this tenant.
                if (Schema::hasTable('api_customers') && Schema::hasColumn('api_customers', 'customers_hub_stage_id')) {
                    DB::table('api_customers')
                        ->where('user_id', $tenantUserId)
                        ->where('customers_hub_stage_id', $legacyStageId)
                        ->update(['customers_hub_stage_id' => $newStageId, 'updated_at' => now()]);
                }
                if (Schema::hasTable('users_property_requests') && Schema::hasColumn('users_property_requests', 'customers_hub_stage_id')) {
                    DB::table('users_property_requests')
                        ->where('user_id', $tenantUserId)
                        ->where('customers_hub_stage_id', $legacyStageId)
                        ->update(['customers_hub_stage_id' => $newStageId, 'updated_at' => now()]);
                }
                if (Schema::hasTable('api_customer_inquiry') && Schema::hasColumn('api_customer_inquiry', 'stage_id')) {
                    DB::table('api_customer_inquiry')
                        ->where('user_id', $tenantUserId)
                        ->where('stage_id', $legacyStageId)
                        ->update(['stage_id' => $newStageId, 'updated_at' => now()]);
                }
            }
        }

        // Hide legacy global rows going forward (they're now tenant-owned where needed).
        DB::table('customers_hub_stages')
            ->whereNull('user_id')
            ->whereIn('stage_id', $legacyStageIds)
            ->update([
                'is_active' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Non-reversible safely (we cannot know which tenant-owned stage_id maps back to each legacy stage).
    }
};

