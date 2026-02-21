<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\CustomerDropdownSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\ToggleCustomerDropdownVisibilityRequest;
use App\Http\Requests\Api\Content\UpdateCustomerDropdownSettingsRequest;
use Illuminate\Http\Request;

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
    public function update(UpdateCustomerDropdownSettingsRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // Find or create settings
        $settings = CustomerDropdownSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            $settings = new CustomerDropdownSetting();
            $settings->user_id = $user->id;
        }

        // Update settings
        $settings->is_visible = array_key_exists('is_visible', $validated)
            ? $validated['is_visible']
            : ($settings->is_visible ?? true);
        $settings->show_login = array_key_exists('show_login', $validated)
            ? $validated['show_login']
            : ($settings->show_login ?? true);
        $settings->show_register = array_key_exists('show_register', $validated)
            ? $validated['show_register']
            : ($settings->show_register ?? true);
        $settings->show_dashboard = array_key_exists('show_dashboard', $validated)
            ? $validated['show_dashboard']
            : ($settings->show_dashboard ?? true);
        $settings->show_logout = array_key_exists('show_logout', $validated)
            ? $validated['show_logout']
            : ($settings->show_logout ?? true);
        
        // Update additional settings
        if (array_key_exists('additional_settings', $validated)) {
            $currentAdditional = $settings->additional_settings ?? [];
            $settings->additional_settings = array_merge($currentAdditional, $validated['additional_settings'] ?? []);
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
    public function toggleVisibility(ToggleCustomerDropdownVisibilityRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();
        $settings = CustomerDropdownSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json(['message' => 'Settings not found.'], 404);
        }

        $settings->is_visible = (bool) $validated['enabled'];
        $settings->save();

        return response()->json([
            'message' => 'Customer dropdown visibility updated successfully.',
            'is_visible' => $settings->is_visible
        ]);
    }
}
