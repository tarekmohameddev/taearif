<?php

namespace App\Services;

use App\Models\ApiCustomer;
use App\Models\Api\UserApiCustomerStage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CrmCustomerStageService
{
    /**
     * Central place to change a customer's CRM stage.
     *
     * This updates the customer's stage and is the right hook
     * to add any extra side effects (e.g. syncing other CRM boards).
     */
    public function changeStage(ApiCustomer $customer, UserApiCustomerStage $stage): ApiCustomer
    {
        DB::transaction(function () use ($customer, $stage) {
            $customer->stage_id = $stage->id;
            $customer->save();

            // Keep linked CRM requests in sync (if table exists)
            try {
                if (Schema::hasTable('crm_requests')) {
                    DB::table('crm_requests')
                        ->where('customer_id', $customer->id)
                        ->when($customer->user_id, fn($q) => $q->where('user_id', $customer->user_id))
                        ->update([
                            'stage_id'   => $stage->id,
                            'updated_at' => now(),
                        ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to sync CRM request stage with customer', [
                    'customer_id' => $customer->id,
                    'stage_id'    => $stage->id,
                    'error'       => $e->getMessage(),
                ]);
            }
        });

        Cache::forget("customers:summary:{$customer->user_id}");

        return $customer->refresh();
    }
}

