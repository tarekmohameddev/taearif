<?php

namespace App\Services;

use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerStage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class TenantCrmBootstrapService
{
    /**
     * Default CRM board stages for a new tenant (single source of truth).
     *
     * @return list<array{stage_name: string, order: int}>
     */
    public static function defaultStages(): array
    {
        return [
            ['stage_name' => 'طلب معاينه', 'order' => 1],
            ['stage_name' => 'صفقة بيع او ايجار', 'order' => 2],
            ['stage_name' => 'اقفال الصفقة', 'order' => 3],
        ];
    }

    /**
     * Ensure default CRM stages exist and auto-customer settings are enabled.
     */
    public function ensureForTenant(int $userId): void
    {
        if (!$this->stagesTableReady() || !$this->settingsTableReady()) {
            return;
        }

        $this->ensureDefaultStages($userId);
        $this->ensureAutoCustomerSettings($userId);
    }

    /**
     * Run ensureForTenant without propagating exceptions (user create paths).
     */
    public function ensureForTenantSafely(int $userId): void
    {
        try {
            $this->ensureForTenant($userId);
        } catch (\Throwable $e) {
            Log::error('Tenant CRM bootstrap failed on user create', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create the 3 default CRM stages when the tenant has no active stages.
     */
    public function ensureDefaultStages(int $userId): void
    {
        if (!$this->stagesTableReady()) {
            return;
        }

        $hasActiveStages = UserApiCustomerStage::where('user_id', $userId)
            ->where('is_active', true)
            ->exists();

        if ($hasActiveStages) {
            return;
        }

        foreach (self::defaultStages() as $stage) {
            UserApiCustomerStage::create([
                'user_id' => $userId,
                'stage_name' => $stage['stage_name'],
                'order' => $stage['order'],
                'is_active' => true,
            ]);
        }
    }

    /**
     * Always enable auto-create customer with the first active stage as default.
     */
    public function ensureAutoCustomerSettings(int $userId): void
    {
        if (!$this->stagesTableReady() || !$this->settingsTableReady()) {
            return;
        }

        $firstStageId = UserApiCustomerStage::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('order', 'asc')
            ->value('id');

        if ($firstStageId === null) {
            Log::warning('TenantCrmBootstrapService: no active stage for auto-customer settings', [
                'user_id' => $userId,
            ]);
            return;
        }

        PropertyRequestAutoCustomerSetting::updateOrCreate(
            ['user_id' => $userId],
            [
                'auto_create_customer' => true,
                'default_stage_id' => $firstStageId,
            ]
        );

        PropertyRequestCustomerService::clearSettingsCache($userId);
    }

    private function stagesTableReady(): bool
    {
        return Schema::hasTable('users_api_customers_stages');
    }

    private function settingsTableReady(): bool
    {
        return Schema::hasTable('property_request_auto_customer_settings');
    }
}
