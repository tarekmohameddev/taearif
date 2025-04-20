<?php

namespace App\Http\Controllers\Api\content;

use App\Models\Api\ApiBannerSetting;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
                ]
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
    public function update(Request $request)
    {
        $user = $request->user();

        // Validate the request
        $validator = Validator::make($request->all(), [
            'banner_type' => 'required|string|in:static,slider',

            // Static banner settings
            'static' => 'required|array',
            'static.enabled' => 'boolean',
            'static.image' => 'nullable|string',
            'static.title' => 'nullable|string|max:200',
            'static.subtitle' => 'nullable|string|max:500',
            'static.caption' => 'nullable|string|max:500',
            'static.buttonText' => 'nullable|string|max:50',
            'static.buttonUrl' => 'nullable|string|max:255',
            'static.buttonStyle' => 'string|in:primary,secondary,outline,link',
            'static.textAlignment' => 'string|in:left,center,right',
            'static.overlayColor' => 'nullable|string|max:30',
            'static.textColor' => 'nullable|string|max:30',

            // Slider banner settings
            'slider' => 'required|array',
            'slider.enabled' => 'boolean',
            'slider.slides' => 'array',
            'slider.slides.*.id' => 'required|string',
            'slider.slides.*.image' => 'nullable|string',
            'slider.slides.*.title' => 'nullable|string|max:200',
            'slider.slides.*.subtitle' => 'nullable|string|max:500',
            'slider.slides.*.caption' => 'nullable|string|max:500',
            'slider.slides.*.buttonText' => 'nullable|string|max:50',
            'slider.slides.*.buttonUrl' => 'nullable|string|max:255',
            'slider.slides.*.buttonStyle' => 'string|in:primary,secondary,outline,link',
            'slider.slides.*.textAlignment' => 'string|in:left,center,right',
            'slider.slides.*.enabled' => 'boolean',
            'slider.autoplay' => 'boolean',
            'slider.autoplaySpeed' => 'integer|min:1000|max:10000',
            'slider.showArrows' => 'boolean',
            'slider.showDots' => 'boolean',
            'slider.animation' => 'string|in:fade,slide',
            'slider.overlayColor' => 'nullable|string|max:30',
            'slider.textColor' => 'nullable|string|max:30',

            // Common settings
            'common' => 'required|array',
            'common.height' => 'string|in:small,medium,large,full',
            'common.showSearchBox' => 'boolean',
            'common.searchBoxPosition' => 'string|in:left,center,right',
            'common.responsive' => 'boolean',
            'common.fullWidth' => 'boolean',
        ]);


        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find or create settings
        $settings = ApiBannerSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            $settings = new ApiBannerSetting();
            $settings->user_id = $user->id;
        }

        // Update all settings at once
        $settings->banner_type = $request->input('banner_type');
        $settings->static = $request->input('static');
        $settings->slider = $request->input('slider');
        $settings->common = $request->input('common');

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
