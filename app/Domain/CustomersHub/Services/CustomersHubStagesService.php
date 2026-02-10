<?php

namespace App\Domain\CustomersHub\Services;

use App\Models\CustomersHub\CustomersHubStage;
use App\Domain\CustomersHub\Exceptions\StageInUseException;
use Illuminate\Support\Facades\DB;

class CustomersHubStagesService
{
    /**
     * Get all stages, optionally filtered by active and ordered.
     */
    public function getAll(bool $activeOnly = false, string $orderBy = 'order'): array
    {
        $query = CustomersHubStage::query();

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $allowedOrder = in_array($orderBy, ['order', 'created_at'], true) ? $orderBy : 'order';
        $query->orderBy($allowedOrder);

        $stages = $query->get();

        return [
            'stages' => $stages->map(fn (CustomersHubStage $s) => $this->stageToArray($s))->values()->all(),
            'total' => $stages->count(),
        ];
    }

    /**
     * Create a new stage.
     *
     * @throws \Illuminate\Validation\ValidationException when stage_id already exists
     */
    public function create(array $data): CustomersHubStage
    {
        if (CustomersHubStage::where('stage_id', $data['stage_id'])->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'stage_id' => ['Stage ID already exists'],
            ]);
        }

        return CustomersHubStage::create([
            'stage_id' => $data['stage_id'],
            'stage_name_ar' => $data['stage_name_ar'],
            'stage_name_en' => $data['stage_name_en'],
            'color' => $data['color'],
            'order' => (int) $data['order'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
        ]);
    }

    /**
     * Update stage by stage_id (string).
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException when not found
     */
    public function update(string $stageId, array $data): CustomersHubStage
    {
        $stage = CustomersHubStage::where('stage_id', $stageId)->firstOrFail();

        $allowed = ['stage_name_ar', 'stage_name_en', 'color', 'order', 'description', 'is_active'];
        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                if ($key === 'order') {
                    $stage->order = (int) $data[$key];
                } elseif ($key === 'is_active') {
                    $stage->is_active = (bool) $data[$key];
                } else {
                    $stage->$key = $data[$key];
                }
            }
        }

        $stage->save();

        return $stage;
    }

    /**
     * Delete stage by stage_id. Fails if any customer uses this stage.
     *
     * @return void
     * @throws \RuntimeException when stage is in use (with requests_count in message/data)
     */
    public function delete(string $stageId): void
    {
        $stage = CustomersHubStage::where('stage_id', $stageId)->firstOrFail();

        $count = DB::table('api_customers')->where('customers_hub_stage_id', $stageId)->count();

        if ($count > 0) {
            throw new StageInUseException(
                "Cannot delete stage: {$count} customers are using this stage",
                $count
            );
        }

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

    private function stageToArray(CustomersHubStage $s): array
    {
        return [
            'id' => $s->id,
            'stage_id' => $s->stage_id,
            'stage_name_ar' => $s->stage_name_ar,
            'stage_name_en' => $s->stage_name_en,
            'color' => $s->color,
            'order' => $s->order,
            'description' => $s->description,
            'is_active' => $s->is_active,
            'created_at' => $s->created_at?->toIso8601String(),
            'updated_at' => $s->updated_at?->toIso8601String(),
        ];
    }
}
