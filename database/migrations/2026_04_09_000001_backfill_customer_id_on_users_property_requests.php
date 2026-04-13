<?php

use App\Support\PhoneNormalizer;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        // Step 1: exact phone match
        DB::statement("
            UPDATE users_property_requests upr
            INNER JOIN api_customers ac
                ON  ac.user_id      = upr.user_id
                AND ac.phone_number = upr.phone
                AND ac.deleted_at   IS NULL
            SET upr.customer_id = ac.id,
                upr.updated_at  = NOW()
            WHERE upr.customer_id IS NULL
              AND upr.is_active = 1
              AND upr.phone IS NOT NULL
        ");

        // Step 2: normalized phone match + create missing customers
        $now = Carbon::now();
        $defaultsByUser = [];

        // Global customers_hub_stage (same across tenants)
        $defaultStage = DB::table('customers_hub_stages')
            ->where('is_active', true)
            ->orderBy('id', 'asc')
            ->value('stage_id');

        DB::table('users_property_requests')
            ->whereNull('customer_id')
            ->where('is_active', 1)
            ->whereNotNull('phone')
            ->orderBy('id')
            ->select([
                'id',
                'user_id',
                'full_name',
                'phone',
                'customers_hub_stage_id',
                'responsible_employee_id',
                'created_at',
            ])
            ->chunk(100, function ($rows) use ($now, $defaultStage, &$defaultsByUser) {
                foreach ($rows as $upr) {
                    $normalizedPhone = PhoneNormalizer::normalize($upr->phone);
                    if (!$normalizedPhone) {
                        continue; // no usable phone — skip
                    }

                    // Duplicate check by (user_id, normalized phone)
                    $existingId = DB::table('api_customers')
                        ->where('user_id', $upr->user_id)
                        ->where('phone_number', $normalizedPhone)
                        ->whereNull('deleted_at')
                        ->value('id');

                    if ($existingId) {
                        // Customer exists — just link it
                        DB::table('users_property_requests')
                            ->where('id', $upr->id)
                            ->whereNull('customer_id')
                            ->update(['customer_id' => $existingId, 'updated_at' => $now]);
                        continue;
                    }

                    // Load per-tenant defaults once and cache
                    if (!isset($defaultsByUser[$upr->user_id])) {
                        $defaultsByUser[$upr->user_id] = [
                            'type_id' => DB::table('users_api_customers_types')
                                ->where('user_id', $upr->user_id)
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->value('id'),
                            'priority_id' => DB::table('users_api_customers_priorities')
                                ->where('user_id', $upr->user_id)
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->value('id'),
                            'procedure_id' => DB::table('users_api_customers_procedures')
                                ->where('user_id', $upr->user_id)
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->value('id'),
                            'stage_id' => DB::table('users_api_customers_stages')
                                ->where('user_id', $upr->user_id)
                                ->where('is_active', true)
                                ->orderBy('order')
                                ->value('id'),
                            'customers_hub_stage_id' => $defaultStage,
                        ];
                    }
                    $defaults = $defaultsByUser[$upr->user_id];

                    try {
                        // Per your request: do NOT import city_id / district_id from users_property_requests.
                        $customerId = DB::table('api_customers')->insertGetId([
                            'user_id' => $upr->user_id,
                            'name' => $upr->full_name ?: 'عميل',
                            'phone_number' => $normalizedPhone,
                            'email' => null,
                            'password' => bcrypt('12345678'),

                            // Legacy pipeline fields
                            'stage_id' => $defaults['stage_id'],
                            'type_id' => $defaults['type_id'],
                            'priority_id' => $defaults['priority_id'],
                            'procedure_id' => $defaults['procedure_id'],

                            // CustomersHub pipeline fields
                            'customers_hub_stage_id' => $upr->customers_hub_stage_id ?? $defaults['customers_hub_stage_id'],
                            'customers_hub_stage_changed_at' => $now,

                            'responsible_employee_id' => $upr->responsible_employee_id,
                            'created_by_type' => 'system',
                            'created_by_id' => null,
                            'source' => 'property_request',
                            'source_id' => $upr->id,
                            'created_at' => $upr->created_at, // preserve history
                            'updated_at' => $now,
                        ]);

                        DB::table('users_property_requests')
                            ->where('id', $upr->id)
                            ->whereNull('customer_id')
                            ->update(['customer_id' => $customerId, 'updated_at' => $now]);
                    } catch (\Throwable $e) {
                        // Log and continue — one bad row must not abort the entire migration
                        Log::warning('backfill_customer_id: skipped upr#' . $upr->id . ' — ' . $e->getMessage());
                    }
                }
            });
    }

    public function down(): void
    {
        // Intentionally no-op: cannot safely revert customer creation without data loss risk.
    }
};

