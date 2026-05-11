<?php

namespace App\Domain\CustomersHub\Services;

use App\Models\CustomersHub\CustomersHubStage;
use App\Models\CustomersHub\CustomersHubStageOverride;
use App\Domain\CustomersHub\Exceptions\StageInUseException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CustomersHubStagesService
{
    /**
     * Get all stages, optionally filtered by active and ordered.
     * Non-global stages are only included when $userId is set and that user has at least one
     * property request with customers_hub_stage_id matching that stage.
     */
    public function getAll(bool $activeOnly = false, string $orderBy = 'order', ?int $userId = null): array
    {
        if ($userId === null) {
            $userId = 0;
        }

        $allowedOrder = in_array($orderBy, ['order', 'created_at'], true) ? $orderBy : 'order';
        $cacheKey = "ch:stages:{$userId}:" . ($activeOnly ? '1' : '0') . ":{$allowedOrder}";

        return \Illuminate\Support\Facades\Cache::remember($cacheKey, 600, function () use ($userId, $activeOnly, $allowedOrder) {
            $presenter = app(CustomersHubStagesPresenter::class);
            $rows = $presenter->stagesQueryForTenant($userId, $activeOnly);
            $rows->orderBy($allowedOrder)->orderBy('id');

            $filtered = $rows->get();

            return [
                'stages' => $filtered->map(fn ($s) => $this->stageToArray($s))->values()->all(),
                'total' => $filtered->count(),
            ];
        });
    }

    /**
     * Create a new stage.
     *
     * @throws \Illuminate\Validation\ValidationException when stage_id already exists
     */
    public function create(array $data): CustomersHubStage
    {
        // stage_id is server-generated for tenant custom stages
        $tenantUserId = isset($data['tenant_user_id']) ? (int) $data['tenant_user_id'] : null;
        if ($tenantUserId === null || $tenantUserId <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tenant_user_id' => ['Tenant user ID is required'],
            ]);
        }

        $stageId = $data['stage_id'] ?? null;
        if (!$stageId) {
            $stageId = 'ch_' . Str::lower((string) Str::ulid());
        }
        if (CustomersHubStage::where('stage_id', $stageId)->exists()) {
            // extremely unlikely when generated; retry once
            $stageId = 'ch_' . Str::lower((string) Str::ulid());
        }
        if (CustomersHubStage::where('stage_id', $stageId)->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stage_id' => ['Could not generate unique stage_id'],
            ]);
        }

        $stage = CustomersHubStage::create([
            'stage_id' => $stageId,
            'stage_name_ar' => $data['stage_name_ar'],
            'stage_name_en' => $data['stage_name_en'],
            'color' => $data['color'],
            'order' => (int) $data['order'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'is_global' => true,
            'user_id' => $tenantUserId,
            'is_system' => false,
        ]);

        $this->forgetStagesCache($tenantUserId);

        return $stage;
    }

    /**
     * Update stage by stage_id (string).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when not found
     */
    public function update(string $stageId, array $data): CustomersHubStage
    {
        $tenantUserId = isset($data['tenant_user_id']) ? (int) $data['tenant_user_id'] : null;
        if ($tenantUserId === null || $tenantUserId <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'tenant_user_id' => ['Tenant user ID is required'],
            ]);
        }

        $stage = CustomersHubStage::where('stage_id', $stageId)->firstOrFail();

        // System stage: update tenant override only (do not mutate base row)
        if ((bool) ($stage->is_system ?? false) === true) {
            $allowed = ['stage_name_ar', 'stage_name_en', 'color', 'order'];
            $payload = [];
            foreach ($allowed as $key) {
                if (array_key_exists($key, $data)) {
                    $payload[$key] = $key === 'order' ? (int) $data[$key] : $data[$key];
                }
            }
            if (!empty($payload)) {
                CustomersHubStageOverride::updateOrCreate(
                    ['user_id' => $tenantUserId, 'stage_id' => $stageId],
                    $payload
                );
            }

            // Return effective stage as a model-shaped instance (for controller response formatting).
            $presenter = app(CustomersHubStagesPresenter::class);
            $effective = $presenter->stagesQueryForTenant($tenantUserId, false)
                ->where('s.stage_id', $stageId)
                ->first();
            if ($effective) {
                // Hydrate into a model instance for consistent controller payload
                $stage->stage_name_ar = $effective->stage_name_ar;
                $stage->stage_name_en = $effective->stage_name_en;
                $stage->color = $effective->color;
                $stage->order = (int) $effective->order;
            }

            $this->forgetStagesCache($tenantUserId);

            return $stage;
        }

        // Tenant stage: enforce ownership
        if ((int) ($stage->user_id ?? 0) !== $tenantUserId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stage_id' => ['You do not have permission to edit this stage.'],
            ]);
        }

        $allowed = ['stage_name_ar', 'stage_name_en', 'color', 'order', 'description', 'is_active'];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                if ($key === 'order') {
                    $stage->order = (int) $data[$key];
                } elseif ($key === 'is_active') {
                    $stage->$key = (bool) $data[$key];
                } else {
                    $stage->$key = $data[$key];
                }
            }
        }

        $stage->save();

        $this->forgetStagesCache($tenantUserId);

        return $stage;
    }

    /**
     * Delete stage by stage_id. Fails if any customer uses this stage.
     *
     * @return void
     * @throws \RuntimeException when stage is in use (with requests_count in message/data)
     */
    public function delete(string $stageId, int $tenantUserId): void
    {
        $stage = CustomersHubStage::where('stage_id', $stageId)->firstOrFail();

        if ((bool) ($stage->is_system ?? false) === true) {
            throw new StageInUseException('Cannot delete system stage', 0);
        }

        if ((int) ($stage->user_id ?? 0) !== $tenantUserId) {
            throw new \RuntimeException('Cannot delete stage: not owned by tenant');
        }

        $count = (int) DB::table('api_customers')
            ->where('user_id', $tenantUserId)
            ->where('customers_hub_stage_id', $stageId)
            ->count();
        $count += (int) DB::table('users_property_requests')
            ->where('user_id', $tenantUserId)
            ->where('customers_hub_stage_id', $stageId)
            ->count();
        $count += (int) DB::table('api_customer_inquiry')
            ->where('user_id', $tenantUserId)
            ->where('stage_id', $stageId)
            ->count();

        if ($count > 0) {
            throw new StageInUseException(
                "Cannot delete stage: {$count} records are using this stage",
                $count
            );
        }

        $this->forgetStagesCache($tenantUserId);

        $stage->delete();
    }

    /**
     * Find stage by stage_id (string). Returns null if not found.
     */
    public function findByStageId(string $stageId): ?CustomersHubStage
    {
        return CustomersHubStage::where('stage_id', $stageId)->first();
    }

    /**
     * Get active stages ordered by order (for pipeline, filters, etc.).
     */
    public function getActiveStages(): \Illuminate\Support\Collection
    {
        return CustomersHubStage::where('is_active', true)->orderBy('order')->get();
    }

    private function stageToArray($s): array
    {
        $baseNameAr = $s->base_stage_name_ar ?? $s->stage_name_ar;
        $baseNameEn = $s->base_stage_name_en ?? $s->stage_name_en;
        $baseColor = $s->base_color ?? $s->color;
        $baseOrder = $s->base_order ?? $s->order;
        $isOverridden = ($s->override_id ?? null) !== null;

        return [
            'id' => $s->id,
            'stage_id' => $s->stage_id,
            'stage_name_ar' => $s->stage_name_ar,
            'stage_name_en' => $s->stage_name_en,
            'color' => $s->color,
            'order' => $s->order,
            'base_stage_name_ar' => $baseNameAr,
            'base_stage_name_en' => $baseNameEn,
            'base_color' => $baseColor,
            'base_order' => $baseOrder,
            'is_overridden' => $isOverridden,
            'description' => $s->description,
            'is_active' => $s->is_active,
            'is_global' => (bool) ($s->is_global ?? true),
            'is_system' => (bool) ($s->is_system ?? false),
            'user_id' => $s->user_id ?? null,
            'created_at' => $this->timestampToIso($s->created_at ?? null),
            'updated_at' => $this->timestampToIso($s->updated_at ?? null),
        ];
    }

    private function forgetStagesCache(int $userId): void
    {
        foreach ([true, false] as $activeOnly) {
            foreach (['order', 'created_at'] as $orderBy) {
                $key = "ch:stages:{$userId}:" . ($activeOnly ? '1' : '0') . ":{$orderBy}";
                \Illuminate\Support\Facades\Cache::forget($key);
            }
        }
    }

    private function timestampToIso(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }
        if (is_string($value)) {
            return Carbon::parse($value)->toIso8601String();
        }
        if (is_object($value) && method_exists($value, 'toIso8601String')) {
            /** @phpstan-ignore-next-line */
            return $value->toIso8601String();
        }
        return null;
    }
}
