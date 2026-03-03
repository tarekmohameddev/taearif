<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\ApiBannerSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\UpdateBannerSettingsRequest;
use Illuminate\Http\Request;

class ApiBannerSettingController extends Controller
{
    /**
     * Get the banner settings for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = ApiBannerSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            // Create default settings if none exist
            $settings = ApiBannerSetting::create([
                'user_id' => $user->id,
                'banner_type' => 'static',
                'static' => [
                    'enabled' => true,
                    'image' => null,
                    'title' => 'أفضل العقارات في المملكة',
                    'subtitle' => 'اكتشف مجموعة واسعة من العقارات المميزة',
                    'caption' => 'وصف جديد',
                    'showButton' => true,
                    'buttonText' => 'استكشف العقارات',
                    'buttonUrl' => '/properties',
                    'buttonStyle' => 'primary',
                    'textAlignment' => 'center',
                    'overlayColor' => 'rgba(0, 0, 0, 0.5)',
                    'textColor' => '#ffffff'
                ],
                'slider' => [
                    'enabled' => false,
                    'slides' => [
                        [
                            'id' => '1',
                            'image' => null,
                            'title' => 'عقارات فاخرة',
                            'subtitle' => 'اكتشف مجموعة من العقارات الفاخرة',
                            'caption' => 'وصف جديد',
                            'showButton' => true,
                            'buttonText' => 'استكشف الآن',
                            'buttonUrl' => '/properties',
                            'buttonStyle' => 'primary',
                            'textAlignment' => 'center',
                            'enabled' => true
                        ],
                        [
                            'id' => '2',
                            'image' => null,
                            'title' => 'مشاريع سكنية',
                            'subtitle' => 'تصفح أحدث المشاريع السكنية',
                            'showButton' => true,
                            'buttonText' => 'عرض المشاريع',
                            'buttonUrl' => '/projects',
                            'buttonStyle' => 'secondary',
                            'textAlignment' => 'center',
                            'enabled' => true
                        ]
                    ],
                    'autoplay' => true,
                    'autoplaySpeed' => 5000,
                    'showArrows' => true,
                    'showDots' => true,
                    'animation' => 'fade',
                    'overlayColor' => 'rgba(0, 0, 0, 0.5)',
                    'textColor' => '#ffffff'
                ],
                'common' => [
                    'height' => 'medium', // small, medium, large, full
                    'showSearchBox' => true,
                    'searchBoxPosition' => 'center', // left, center, right
                    'responsive' => true,
                    'fullWidth' => true
                ],
                'status' => false
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
     * Update all banner settings at once
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(UpdateBannerSettingsRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // Find or create settings
        $settings = ApiBannerSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            $settings = new ApiBannerSetting();
            $settings->user_id = $user->id;
        }

        // Update all settings at once
        $settings->banner_type = $validated['banner_type'];
        $settings->static = $validated['static'];
        $settings->slider = $validated['slider'];
        $settings->common = $validated['common'];
        $settings->status = $validated['status'];

        $settings->save();


        $responseSettings = $settings->toArray();

        if (!empty($responseSettings['static'])) {
            $static = $responseSettings['static'];
            if (isset($static['image']) && $static['image']) {
                $static['image'] = asset($static['image']);
            }
            $responseSettings['static'] = $static;
        }


        if (!empty($responseSettings['slider'])) {
            $slider = $responseSettings['slider']['slides'];
            if (is_array($slider)) {
                foreach ($slider as &$slide) {
                    if (isset($slide['image']) && $slide['image']) {
                        $slide['image'] = asset($slide['image']);
                    }
                }
            }
            $responseSettings['slider'] = $slider;
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Banner settings updated successfully',
            'data' => [
                'settings' => $responseSettings
            ]
        ]);
    }
}
