<?php

namespace App\Http\Controllers\Api\content;

use Illuminate\Http\Request;
use App\Models\User\BasicSetting;
use App\Models\Api\GeneralSetting;
use App\Models\MaintenanceMode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\ToggleGeneralShowPropertiesRequest;
use App\Http\Requests\Api\Content\UpdateGeneralSettingsRequest;

class GeneralSettingController extends Controller
{
    /**
     * Get the general settings for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = GeneralSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            $settings = GeneralSetting::create([
                'user_id' => $user->id,
                'site_name' => 'موقعي الاول',
                'tagline' => 'افضل موقع في المملكة',
                'description' => 'مرحباً بكم في موقعي',
                'maintenance_mode' => false,
                'show_breadcrumb' => true,
                'additional_settings' => null,
            ]);
        }


        $responseSettings = $settings->toArray();
        $responseSettings['logo'] = asset($settings->logo);
        $responseSettings['favicon'] = asset($settings->favicon);

        $basicSetting = BasicSetting::where('user_id', $user->id)->first();

        if ($basicSetting) {
            $responseSettings['primary_color'] = $basicSetting->base_color;
            $responseSettings['secondary_color'] = $basicSetting->secondary_color;
            $responseSettings['accent_color'] = $basicSetting->accent_color;
        }
        return response()->json([
            'status' => 'success',
            'data' => [
                'settings' => $responseSettings
            ]
        ]);
    }

    public function update(UpdateGeneralSettingsRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Check if user is trying to disable maintenance mode and has permission
        if (($validated['maintenance_mode'] ?? null) == 0 && !$user->can('disable', MaintenanceMode::class)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Free package users cannot disable maintenance mode. Please upgrade your package to access this feature.',
                'code' => 'MAINTENANCE_MODE_RESTRICTED'
            ], 403);
        }

        // Fetch GeneralSettings for the user
        $settings = GeneralSetting::where('user_id', $user->id)->first();

        // If no settings found, create a new one
        if (!$settings) {
            $settings = new GeneralSetting();
            $settings->user_id = $user->id;
        }

        // Update GeneralSettings fields
        $settings->site_name = $validated['site_name'];
        $settings->tagline = $validated['tagline'] ?? null;
        $settings->description = $validated['description'] ?? null;
        $settings->logo = $validated['logo'] ?? null;
        $settings->favicon = $validated['favicon'] ?? null;
        $settings->maintenance_mode = $validated['maintenance_mode'] ?? false;
        $settings->show_breadcrumb = $validated['show_breadcrumb'] ?? true;
        $settings->show_properties = $validated['show_properties'] ?? false;
        $settings->additional_settings = $validated['additional_settings'] ?? [];
        $settings->color = $validated['color'] ?? null;

        // Fetch BasicSettings for the user and update the colors AND logo/favicon
        $basicSetting = BasicSetting::where('user_id', $user->id)->first();
        if ($basicSetting) {
            $basicSetting->base_color = $validated['primary_color'] ?? $basicSetting->base_color;
            $basicSetting->secondary_color = $validated['secondary_color'] ?? $basicSetting->secondary_color;
            $basicSetting->accent_color = $validated['accent_color'] ?? $basicSetting->accent_color;

            // Update logo and favicon in BasicSetting as well (for re-seeding)
            if (array_key_exists('logo', $validated) && $validated['logo'] !== null) {
                $basicSetting->logo = $validated['logo'];
            }
            if (array_key_exists('favicon', $validated) && $validated['favicon'] !== null) {
                $basicSetting->favicon = $validated['favicon'];
            }

            // Update company name from site_name (for re-seeding)
            if (array_key_exists('site_name', $validated)) {
                $basicSetting->company_name = $validated['site_name'];
            }

            // Save the BasicSetting after updating the colors, logo, favicon, and company name
            $basicSetting->save();
        }

        // Save the updated GeneralSettings
        $settings->save();

        // Re-seed tenant website pages if logo or company name changed
        if ((array_key_exists('logo', $validated) && $validated['logo'] !== null) || array_key_exists('site_name', $validated)) {
            try {
                $seeder = app(\App\Services\TenantWebsiteSeeder::class);
                $seeder->reseedWebsite($user);
                $updatedFields = ['site_name'];
                if (array_key_exists('logo', $validated) && $validated['logo'] !== null) {
                    $updatedFields[] = 'logo';
                }
                \Log::info('Auto re-seeded website after settings update', [
                    'user_id' => $user->id,
                    'updated_fields' => $updatedFields
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to auto re-seed website after settings update', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the settings update if re-seed fails
            }
        }

        // Prepare response data with the updated settings
        $responseSettings = $settings->toArray();
        $responseSettings['logo'] = asset($settings->logo);
        $responseSettings['favicon'] = asset($settings->favicon);

        // Make sure to include the updated colors in the response
        if ($basicSetting) {
            $responseSettings['primary_color'] = $basicSetting->base_color;
            $responseSettings['secondary_color'] = $basicSetting->secondary_color;
            $responseSettings['accent_color'] = $basicSetting->accent_color;
        }

        // Return the response
        return response()->json([
            'status' => 'success',
            'message' => 'General settings updated successfully',
            'data' => [
                'settings' => $responseSettings
            ]
        ]);
    }

    public function ShowProperties(ToggleGeneralShowPropertiesRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $settings = GeneralSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json(['message' => 'Settings not found.'], 404);
        }

        $settings->show_properties = (bool) $validated['enabled'];
        $settings->save();

        return response()->json([
            'message' => 'show_properties updated successfully.',
            'show_properties' => $settings->show_properties
        ]);
    }

    /**
     * Get comprehensive membership status for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMembershipStatus(Request $request)
    {
        $user = $request->user();
        $membershipService = app(\App\Services\MembershipService::class);

        $status = $membershipService->getMembershipStatus($user);

        return response()->json([
            'status' => 'success',
            'data' => [
                'membership_status' => $status
            ]
        ]);
    }

}
