<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\CustomerDropdownSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerDropdownSettingController extends Controller
{
    /**
     * Get the customer dropdown settings for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = CustomerDropdownSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            // Create default settings if none exist
            $settings = CustomerDropdownSetting::create([
                'user_id' => $user->id,
                'is_visible' => true,
                'show_login' => true,
                'show_register' => true,
                'show_dashboard' => true,
                'show_logout' => true,
                'additional_settings' => [
                    'button_text' => 'Register',
                    'button_style' => 'primary',
                    'dropdown_position' => 'right',
                ],
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'settings' => $settings
            ]
        ]);
    }

    /**
     * Get dropdown settings for a specific user (public method for blade templates)
     *
     * @param int $userId
     * @return array
     */
    public static function getDropdownSettings($userId)
    {
        try {
            $settings = CustomerDropdownSetting::where('user_id', $userId)->first();
            
            if (!$settings) {
                // Return default settings if none exist
                return [
                    'is_visible' => true,
                    'show_login' => true,
                    'show_register' => true,
                    'show_dashboard' => true,
                    'show_logout' => true,
                ];
            }

            return [
                'is_visible' => $settings->is_visible,
                'show_login' => $settings->show_login,
                'show_register' => $settings->show_register,
                'show_dashboard' => $settings->show_dashboard,
                'show_logout' => $settings->show_logout,
            ];
        } catch (\Exception $e) {
            // Fallback to default settings if there's an error
            return [
                'is_visible' => true,
                'show_login' => true,
                'show_register' => true,
                'show_dashboard' => true,
                'show_logout' => true,
            ];
        }
    }

    /**
     * Update customer dropdown settings
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'is_visible' => 'boolean',
            'show_login' => 'boolean',
            'show_register' => 'boolean',
            'show_dashboard' => 'boolean',
            'show_logout' => 'boolean',
            'additional_settings' => 'nullable|array',
            'additional_settings.button_text' => 'nullable|string|max:50',
            'additional_settings.button_style' => 'nullable|string|in:primary,secondary,outline,link',
            'additional_settings.dropdown_position' => 'nullable|string|in:left,right,center',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find or create settings
        $settings = CustomerDropdownSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            $settings = new CustomerDropdownSetting();
            $settings->user_id = $user->id;
        }

        // Update settings
        $settings->is_visible = $request->input('is_visible', $settings->is_visible ?? true);
        $settings->show_login = $request->input('show_login', $settings->show_login ?? true);
        $settings->show_register = $request->input('show_register', $settings->show_register ?? true);
        $settings->show_dashboard = $request->input('show_dashboard', $settings->show_dashboard ?? true);
        $settings->show_logout = $request->input('show_logout', $settings->show_logout ?? true);
        
        // Update additional settings
        if ($request->has('additional_settings')) {
            $currentAdditional = $settings->additional_settings ?? [];
            $settings->additional_settings = array_merge($currentAdditional, $request->input('additional_settings', []));
        }

        $settings->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Customer dropdown settings updated successfully',
            'data' => [
                'settings' => $settings
            ]
        ]);
    }

    /**
     * Toggle visibility of the customer dropdown
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function toggleVisibility(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $user = $request->user();
        $settings = CustomerDropdownSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json(['message' => 'Settings not found.'], 404);
        }

        $settings->is_visible = $request->boolean('enabled');
        $settings->save();

        return response()->json([
            'message' => 'Customer dropdown visibility updated successfully.',
            'is_visible' => $settings->is_visible
        ]);
    }
}
