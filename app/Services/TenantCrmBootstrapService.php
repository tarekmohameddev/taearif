<?php

namespace App\Services;

use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerPriority;
use App\Models\Api\UserApiCustomerProcedure;
use App\Models\Api\UserApiCustomerStage;
use App\Models\Api\UserApiCustomerType;
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
     * Default customer types for a new tenant (single source of truth).
     *
     * @return list<array{name: string, value: string, order: int, icon: string, color: string}>
     */
    public static function defaultTypes(): array
    {
        return [
            ['name' => 'Rent',   'value' => 'Rent',   'order' => 1, 'icon' => 'home',      'color' => '#2196f3'],
            ['name' => 'Sale',   'value' => 'Sale',   'order' => 2, 'icon' => 'dollar',    'color' => '#4caf50'],
            ['name' => 'Rented', 'value' => 'Rented', 'order' => 3, 'icon' => 'check',     'color' => '#9e9e9e'],
            ['name' => 'Sold',   'value' => 'Sold',   'order' => 4, 'icon' => 'check-all', 'color' => '#9e9e9e'],
            ['name' => 'Both',   'value' => 'Both',   'order' => 5, 'icon' => 'arrows',    'color' => '#ff9800'],
        ];
    }

    /**
     * Default customer priorities for a new tenant (single source of truth).
     *
     * @return list<array{name: string, value: int, order: int, icon: string, color: string}>
     */
    public static function defaultPriorities(): array
    {
        return [
            ['name' => 'Low',    'value' => 1, 'order' => 1, 'icon' => 'arrow-down', 'color' => '#4caf50'],
            ['name' => 'Medium', 'value' => 2, 'order' => 2, 'icon' => 'minus',      'color' => '#ff9800'],
            ['name' => 'High',   'value' => 3, 'order' => 3, 'icon' => 'arrow-up',   'color' => '#f44336'],
        ];
    }

    /**
     * Default customer procedures for a new tenant (single source of truth).
     *
     * @return list<array{procedure_name: string, order: int, icon: string, color: string}>
     */
    public static function defaultProcedures(): array
    {
        return [
            ['procedure_name' => 'meeting', 'order' => 1, 'icon' => 'users', 'color' => '#2196f3'],
            ['procedure_name' => 'visit',   'order' => 2, 'icon' => 'map',   'color' => '#ff9800'],
        ];
    }

    /**
     * Ensure the tenant's CRM lookup rows exist and auto-customer settings are enabled.
     *
     * Types, priorities and procedures used to be seeded as a side effect of
     * GET /api/crm, so a tenant that never opened the CRM dashboard had none —
     * which left customer creation with no type to default to.
     */
    public function ensureForTenant(int $userId): void
    {
        $this->ensureDefaultTypes($userId);
        $this->ensureDefaultPriorities($userId);
        $this->ensureDefaultProcedures($userId);

        if (!$this->stagesTableReady() || !$this->settingsTableReady()) {
            return;
        }

        $this->ensureDefaultStages($userId);
        $this->ensureAutoCustomerSettings($userId);
    }

    /**
     * Create the default customer types the tenant is missing.
     */
    public function ensureDefaultTypes(int $userId): void
    {
        if (!$this->typesTableReady()) {
            return;
        }

        $created = false;

        foreach (self::defaultTypes() as $type) {
            $row = UserApiCustomerType::firstOrCreate(
                ['user_id' => $userId, 'value' => $type['value']],
                [
                    'name' => $type['name'],
                    'order' => $type['order'],
                    'icon' => $type['icon'],
                    'color' => $type['color'],
                    'is_active' => true,
                ]
            );

            $created = $created || $row->wasRecentlyCreated;
        }

        if ($created) {
            PropertyRequestCustomerService::clearSettingsCache($userId);
        }
    }

    /**
     * Create the default customer priorities the tenant is missing.
     */
    public function ensureDefaultPriorities(int $userId): void
    {
        if (!$this->prioritiesTableReady()) {
            return;
        }

        $created = false;

        foreach (self::defaultPriorities() as $priority) {
            $row = UserApiCustomerPriority::firstOrCreate(
                ['user_id' => $userId, 'value' => $priority['value']],
                [
                    'name' => $priority['name'],
                    'order' => $priority['order'],
                    'icon' => $priority['icon'],
                    'color' => $priority['color'],
                    'is_active' => true,
                ]
            );

            $created = $created || $row->wasRecentlyCreated;
        }

        if ($created) {
            PropertyRequestCustomerService::clearSettingsCache($userId);
        }
    }

    /**
     * Create the default customer procedures the tenant is missing.
     */
    public function ensureDefaultProcedures(int $userId): void
    {
        if (!$this->proceduresTableReady()) {
            return;
        }

        $created = false;

        foreach (self::defaultProcedures() as $procedure) {
            $row = UserApiCustomerProcedure::firstOrCreate(
                ['user_id' => $userId, 'procedure_name' => $procedure['procedure_name']],
                [
                    'order' => $procedure['order'],
                    'icon' => $procedure['icon'],
                    'color' => $procedure['color'],
                    'is_active' => true,
                ]
            );

            $created = $created || $row->wasRecentlyCreated;
        }

        if ($created) {
            PropertyRequestCustomerService::clearSettingsCache($userId);
        }
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

    private function typesTableReady(): bool
    {
        return Schema::hasTable('users_api_customers_types');
    }

    private function prioritiesTableReady(): bool
    {
        return Schema::hasTable('users_api_customers_priorities');
    }

    private function proceduresTableReady(): bool
    {
        return Schema::hasTable('users_api_customers_procedures');
    }

    private function settingsTableReady(): bool
    {
        return Schema::hasTable('property_request_auto_customer_settings');
    }
}
