<?php

namespace App\Http\Controllers\Api\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerStage;
use App\Http\Requests\UpdatePropertyRequestSettingsRequest;
use App\Services\PropertyRequestCustomerService;

class PropertyRequestSettingsController extends Controller
{
    /**
     * Get current property request auto-customer settings
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $settings = PropertyRequestAutoCustomerSetting::where('user_id', $user->id)
            ->with('defaultStage')
            ->first();

        $availableStages = $this->getAvailableStages($user->id);

        return response()->json([
            'status' => 'success',
            'data' => $this->formatSettingsResponse($settings, $availableStages),
        ]);
    }

    /**
     * Update property request auto-customer settings
     */
    public function update(UpdatePropertyRequestSettingsRequest $request): JsonResponse
    {
        $user = $request->user();

        $settings = PropertyRequestAutoCustomerSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'auto_create_customer' => $request->auto_create_customer,
                'default_stage_id' => $request->auto_create_customer ? $request->default_stage_id : null,
            ]
        );

        // Clear cache after update
        PropertyRequestCustomerService::clearSettingsCache($user->id);

        $settings->load('defaultStage');

        return response()->json([
            'status' => 'success',
            'message' => 'تم تحديث الإعدادات بنجاح',
            'data' => [
                'settings' => [
                    'auto_create_customer' => $settings->auto_create_customer,
                    'default_stage_id' => $settings->default_stage_id,
                    'default_stage' => $settings->defaultStage,
                ],
            ],
        ]);
    }

    /**
     * Get available stages for the user
     */
    private function getAvailableStages(int $userId)
    {
        return UserApiCustomerStage::where('user_id', $userId)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'stage_name', 'color', 'icon', 'order']);
    }

    /**
     * Format the settings response consistently
     */
    private function formatSettingsResponse(?PropertyRequestAutoCustomerSetting $settings, $availableStages): array
    {
        return [
            'settings' => [
                'auto_create_customer' => $settings?->auto_create_customer ?? false,
                'default_stage_id' => $settings?->default_stage_id,
                'default_stage' => $settings?->defaultStage,
            ],
            'available_stages' => $availableStages,
        ];
    }
}

