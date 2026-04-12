<?php

namespace App\Http\Controllers\Api\property;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Property\StorePropertyRequestStatusRequest;
use App\Http\Requests\Api\Property\UpdatePropertyRequestStatusRequest;
use App\Models\MerchantStatusLabel;
use App\Models\PropertyRequestStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PropertyRequestStatusController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $ownerId = $request->user()->tenantOwnerId();

        $overrides = MerchantStatusLabel::query()
            ->where('user_id', $ownerId)
            ->get()
            ->keyBy('status_id');

        $rows = PropertyRequestStatus::query()
            ->forTenant($ownerId)
            ->ordered()
            ->get()
            ->map(fn (PropertyRequestStatus $s) => $this->transformStatus($s, $overrides->get($s->id)));

        return response()->json([
            'status' => 'success',
            'data' => $rows,
        ]);
    }

    public function store(StorePropertyRequestStatusRequest $request): JsonResponse
    {
        $ownerId = $request->user()->tenantOwnerId();

        $validated = $request->validated();

        $nextOrder = (int) (PropertyRequestStatus::forTenant($ownerId)->max('display_order') ?? 0) + 1;

        $status = PropertyRequestStatus::create([
            'user_id' => $ownerId,
            'name_ar' => $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'slug' => $validated['slug'],
            'display_order' => isset($validated['display_order']) ? (int) $validated['display_order'] : $nextOrder,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            'is_system' => false,
        ]);

        $this->forgetPropertyRequestFilterMetaCache($ownerId);

        return response()->json([
            'status' => 'success',
            'message' => 'Property request status created successfully',
            'data' => $this->transformStatus($status, null),
        ], 201);
    }

    public function update(UpdatePropertyRequestStatusRequest $request, int $id): JsonResponse
    {
        $ownerId = $request->user()->tenantOwnerId();

        $status = PropertyRequestStatus::query()->findOrFail($id);

        if ($status->user_id === null && $status->is_system) {
            $validated = $request->validated();
            MerchantStatusLabel::updateOrCreate(
                ['user_id' => $ownerId, 'status_id' => $status->id],
                array_merge(
                    [
                        'name_ar' => $status->name_ar,
                        'name_en' => $status->name_en,
                    ],
                    collect($validated)->only(['name_ar', 'name_en'])->all()
                )
            );

            $this->forgetPropertyRequestFilterMetaCache($ownerId);

            $override = MerchantStatusLabel::query()
                ->where('user_id', $ownerId)
                ->where('status_id', $status->id)
                ->first();

            return response()->json([
                'status' => 'success',
                'message' => 'Property request status updated successfully',
                'data' => $this->transformStatus($status->fresh(), $override),
            ]);
        }

        if ($status->user_id === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Default statuses cannot be modified',
            ], 403);
        }

        if ((int) $status->user_id !== $ownerId) {
            abort(404);
        }

        $status->update($request->validated());

        $this->forgetPropertyRequestFilterMetaCache($ownerId);

        return response()->json([
            'status' => 'success',
            'message' => 'Property request status updated successfully',
            'data' => $this->transformStatus($status->fresh(), null),
        ]);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $ownerId = $request->user()->tenantOwnerId();

        $status = PropertyRequestStatus::query()->findOrFail($id);

        if ($status->is_system || $status->user_id === null) {
            return response()->json([
                'status' => 'error',
                'message' => 'System statuses cannot be deleted.',
            ], 403);
        }

        if ((int) $status->user_id !== $ownerId) {
            abort(404);
        }

        $status->delete();

        $this->forgetPropertyRequestFilterMetaCache($ownerId);

        return response()->json([
            'status' => 'success',
            'message' => 'Property request status deleted successfully',
        ]);
    }

    private function forgetPropertyRequestFilterMetaCache(int $ownerId): void
    {
        Cache::forget('property_request_filter_options_meta_' . $ownerId);
    }

    /**
     * @return array<string, mixed>
     */
    private function transformStatus(PropertyRequestStatus $status, ?MerchantStatusLabel $override = null): array
    {
        return [
            'id' => $status->id,
            'name_ar' => $override?->name_ar ?? $status->name_ar,
            'name_en' => $override?->name_en ?? $status->name_en,
            'slug' => $status->slug,
            'display_order' => $status->display_order,
            'is_active' => (bool) $status->is_active,
            'is_default' => $status->user_id === null,
            'is_system' => (bool) $status->is_system,
            'is_deletable' => !$status->is_system,
        ];
    }
}
