<?php

namespace App\Domain\CustomersHub\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * CustomersHubStagesPresenter
 *
 * Provides "effective" stage fields for a tenant:
 * - system stages come from customers_hub_stages, optionally overridden per tenant in customers_hub_stage_overrides
 * - tenant custom stages are stored in customers_hub_stages.user_id = tenantOwnerId
 */
class CustomersHubStagesPresenter
{
    /**
     * Base query for stages visible to this tenant, with effective fields.
     *
     * Returned columns (aliases):
     * - id, stage_id, stage_name_ar, stage_name_en, color, order, description, is_active, is_system, user_id
     */
    public function stagesQueryForTenant(int $tenantUserId, bool $activeOnly = false): Builder
    {
        $q = DB::table('customers_hub_stages as s')
            ->leftJoin('customers_hub_stage_overrides as o', function ($join) use ($tenantUserId) {
                $join->on('o.stage_id', '=', 's.stage_id')
                    ->where('o.user_id', '=', DB::raw((int) $tenantUserId));
            })
            ->where(function ($w) use ($tenantUserId) {
                $w->where('s.is_system', true)
                    ->orWhere('s.user_id', $tenantUserId);
            });

        if ($activeOnly) {
            $q->where('s.is_active', true);
        }

        return $q->select([
            's.id',
            's.stage_id',
            DB::raw("COALESCE(o.stage_name_ar, s.stage_name_ar) as stage_name_ar"),
            DB::raw("COALESCE(o.stage_name_en, s.stage_name_en) as stage_name_en"),
            DB::raw("COALESCE(o.color, s.color) as color"),
            DB::raw("COALESCE(o.`order`, s.`order`) as `order`"),
            's.description',
            's.is_active',
            's.is_system',
            's.user_id',
            's.created_at',
            's.updated_at',
        ]);
    }

    public function listStages(int $tenantUserId, bool $activeOnly = false): Collection
    {
        return $this->stagesQueryForTenant($tenantUserId, $activeOnly)
            ->orderBy('order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Resolve numeric `customers_hub_stages.id` OR string `stage_id` to a stage_id string.
     * Returns null when not found or not visible to tenant (or inactive when activeOnly).
     */
    public function resolveStageIdString(int $tenantUserId, int|string $newStageId, bool $activeOnly = true): ?string
    {
        $q = DB::table('customers_hub_stages as s')
            ->where(function ($w) use ($tenantUserId) {
                $w->where('s.is_system', true)
                    ->orWhere('s.user_id', $tenantUserId);
            });

        if ($activeOnly) {
            $q->where('s.is_active', true);
        }

        if (is_int($newStageId) || (is_string($newStageId) && ctype_digit($newStageId))) {
            $sid = (clone $q)->where('s.id', (int) $newStageId)->value('s.stage_id');
            return $sid !== null ? (string) $sid : null;
        }

        $exists = (clone $q)->where('s.stage_id', (string) $newStageId)->exists();
        return $exists ? (string) $newStageId : null;
    }

    /**
     * Get effective stage payload for move responses (id, stage_id, name_ar, name_en).
     */
    public function getEffectiveStageForTenant(int $tenantUserId, int|string $stageIdOrId, bool $activeOnly = true): ?object
    {
        $q = $this->stagesQueryForTenant($tenantUserId, $activeOnly);

        if (is_int($stageIdOrId) || (is_string($stageIdOrId) && ctype_digit($stageIdOrId))) {
            $row = $q->where('s.id', (int) $stageIdOrId)->first(['s.id', 's.stage_id', 'stage_name_ar', 'stage_name_en']);
        } else {
            $row = $q->where('s.stage_id', (string) $stageIdOrId)->first(['s.id', 's.stage_id', 'stage_name_ar', 'stage_name_en']);
        }

        if (! $row) {
            return null;
        }

        return (object) [
            'id' => (int) $row->id,
            'stage_id' => $row->stage_id,
            'name_ar' => $row->stage_name_ar,
            'name_en' => $row->stage_name_en ?? $row->stage_name_ar,
        ];
    }

    /**
     * Check if a stage_id exists for this tenant (system or tenant-owned), optionally active-only.
     */
    public function stageExistsForTenant(int $tenantUserId, string $stageId, bool $activeOnly = true): bool
    {
        $q = DB::table('customers_hub_stages as s')
            ->where('s.stage_id', $stageId)
            ->where(function ($w) use ($tenantUserId) {
                $w->where('s.is_system', true)->orWhere('s.user_id', $tenantUserId);
            });

        if ($activeOnly) {
            $q->where('s.is_active', true);
        }

        return $q->exists();
    }
}

