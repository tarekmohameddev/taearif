<?php

namespace App\Http\Controllers\Api\CRM;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Models\Api\PropertyRequestAutoCustomerSetting;
use App\Models\Api\UserApiCustomerStage;
use Illuminate\Support\Facades\Validator;

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

        // Get all available stages for this tenant
        $availableStages = UserApiCustomerStage::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('order')
            ->get(['id', 'stage_name', 'color', 'icon', 'order']);

        if (!$settings) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'settings' => [
                        'auto_create_customer' => false,
                        'default_stage_id' => null,
                        'default_stage' => null,
                    ],
                    'available_stages' => $availableStages,
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'settings' => [
                    'auto_create_customer' => $settings->auto_create_customer,
                    'default_stage_id' => $settings->default_stage_id,
                    'default_stage' => $settings->defaultStage,
                ],
                'available_stages' => $availableStages,
            ],
        ]);
    }

    /**
     * Update property request auto-customer settings
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'auto_create_customer' => 'required|boolean',
            'default_stage_id' => 'nullable|integer|exists:users_api_customers_stages,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // If auto_create_customer is true, default_stage_id is required
        if ($request->auto_create_customer && !$request->default_stage_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'يجب اختيار مرحلة افتراضية عند تفعيل إنشاء العملاء تلقائياً',
                'errors' => ['default_stage_id' => ['يجب اختيار مرحلة افتراضية']],
            ], 422);
        }

        // Verify that the stage belongs to the current user
        if ($request->default_stage_id) {
            $stageExists = UserApiCustomerStage::where('id', $request->default_stage_id)
                ->where('user_id', $user->id)
                ->exists();

            if (!$stageExists) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'المرحلة المحددة غير موجودة',
                    'errors' => ['default_stage_id' => ['المرحلة المحددة غير موجودة']],
                ], 422);
            }
        }

        $settings = PropertyRequestAutoCustomerSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'auto_create_customer' => $request->auto_create_customer,
                'default_stage_id' => $request->auto_create_customer ? $request->default_stage_id : null,
            ]
        );

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
}

