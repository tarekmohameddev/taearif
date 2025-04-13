<?php

namespace App\Http\Controllers\Api\Content;

use Illuminate\Http\Request;
use App\Models\Api\GeneralSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

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

        return response()->json([
            'status' => 'success',
            'data' => [
                'settings' => $responseSettings
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'site_name' => 'required|string|max:255',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'logo' => 'nullable|string|max:255',
            'favicon' => 'nullable|string|max:255',
            'maintenance_mode' => 'nullable|boolean',
            'show_breadcrumb' => 'nullable|boolean',
            'show_properties' => 'nullable|boolean',
            'additional_settings' => 'nullable|array',

        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $settings = GeneralSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            $settings = new GeneralSetting();
            $settings->user_id = $user->id;
        }

        $settings->site_name = $request->input('site_name');
        $settings->tagline = $request->input('tagline');
        $settings->description = $request->input('description');
        $settings->logo = $request->input('logo');
        $settings->favicon = $request->input('favicon');
        $settings->maintenance_mode = $request->input('maintenance_mode', false);
        $settings->show_breadcrumb = $request->input('show_breadcrumb', true);
        $settings->show_properties = $request->input('show_properties', false);
        $settings->additional_settings = $request->input('additional_settings', []);


        $settings->save();

        $responseSettings = $settings->toArray();
        $responseSettings['logo'] = asset($settings->logo);
        $responseSettings['favicon'] = asset($settings->favicon);

        return response()->json([
            'status' => 'success',
            'message' => 'General settings updated successfully',
            'data' => [
                'settings' => $responseSettings
            ]
        ]);
    }
    public function ShowProperties(Request $request)
    {
        $request->validate([
            'enabled' => 'required|boolean',
        ]);
        $user = $request->user();
        $settings = GeneralSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            return response()->json(['message' => 'Settings not found.'], 404);
        }

        $settings->show_properties = $request->boolean('enabled');
        $settings->save();

        return response()->json([
            'message' => 'show_properties updated successfully.',
            'show_properties' => $settings->show_properties
        ]);
    }

}
