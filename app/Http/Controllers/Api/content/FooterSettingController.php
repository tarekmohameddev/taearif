<?php

namespace App\Http\Controllers\Api\content;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Api\FooterSetting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Content\UpdateFooterSettingsRequest;
use Illuminate\Support\Facades\Auth;


class FooterSettingController extends Controller
{
    /**
     * Get the footer settings for the authenticated user
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {


        $user = $request->user();
        $settings = FooterSetting::where('user_id', $user->id)->first();

        if (!$settings) {
            // Create default settings if none exist
            $settings = FooterSetting::create([
                'user_id' => $user->id,
                'general' => [
                    'companyName' => 'اسم الشركة',
                    'address' => 'عنوان الشركة، المدينة، البلد',
                    'phone' => '+966 5XXXXXXXX',
                    'email' => 'info@example.com',
                    'workingHours' => 'الأحد - الخميس: 9:00 ص - 5:00 م',
                    'showContactInfo' => true,
                    'showWorkingHours' => true,
                    'copyrightText' => '© ' . date('Y') . ' جميع الحقوق محفوظة',
                    'showCopyright' => true,
                ],
                'social' => [
                    ['id' => '1', 'platform' => 'facebook', 'url' => 'https://facebook.com/', 'enabled' => true],
                    ['id' => '2', 'platform' => 'twitter', 'url' => 'https://twitter.com/', 'enabled' => true],
                    ['id' => '3', 'platform' => 'instagram', 'url' => 'https://instagram.com/', 'enabled' => true],
                    ['id' => '4', 'platform' => 'linkedin', 'url' => 'https://linkedin.com/', 'enabled' => false],
                    ['id' => '5', 'platform' => 'youtube', 'url' => 'https://youtube.com/', 'enabled' => false],
                    ['id' => '6', 'platform' => 'snapchat', 'url' => 'https://snapchat.com/', 'enabled' => false],
                    ['id' => '7', 'platform' => 'tiktok', 'url' => 'https://tiktok.com/', 'enabled' => false],
                ],
                'columns' => [
                    [
                        'id' => '1',
                        'title' => 'روابط سريعة',
                        'links' => [
                            ['id' => '1-1', 'text' => 'الرئيسية', 'url' => '/'],
                            // ['id' => '1-2', 'text' => 'من نحن', 'url' => '/about'],
                            // ['id' => '1-3', 'text' => 'خدماتنا', 'url' => '/services'],
                            // ['id' => '1-4', 'text' => 'اتصل بنا', 'url' => '/contact'],
                        ],
                        'enabled' => true,
                    ],
                    [
                        'id' => '3',
                        'title' => 'الدعم',
                        'links' => [
                            ['id' => '3-1', 'text' => 'الأسئلة الشائعة', 'url' => '/faq'],
                            ['id' => '3-2', 'text' => 'سياسة الخصوصية', 'url' => '/privacy'],
                            ['id' => '3-3', 'text' => 'الشروط والأحكام', 'url' => '/terms'],
                        ],
                        'enabled' => true,
                    ],
                ],
                'newsletter' => [
                    'enabled' => true,
                    'title' => 'اشترك في نشرتنا البريدية',
                    'description' => 'اشترك للحصول على آخر الأخبار والعروض',
                    'buttonText' => 'اشتراك',
                    'placeholderText' => 'أدخل بريدك الإلكتروني',
                ],
                'style' => [
                    'layout' => 'full-width',
                    'backgroundColor' => '#1f2937',
                    'textColor' => '#ffffff',
                    'accentColor' => '#3b82f6',
                    'columns' => 4,
                    'showSocialIcons' => true,
                    'socialIconsPosition' => 'top',
                ],
            ]);

        }

        return response()->json([
            'status' => 'success',
            'data' => ['settings' => $settings]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Update the footer settings
     */
    public function update(UpdateFooterSettingsRequest $request)
    {
        $user = auth()->user();
        $validated = $request->validated();

        // Find or create settings
        $settings = FooterSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'general' => $validated['general'],
                'social' => $validated['social'],
                'columns' => $validated['columns'],
                'newsletter' => $validated['newsletter'],
                'style' => $validated['style'],
                'status' => $validated['status'],
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Footer settings updated successfully',
            'data' => ['settings' => $settings]
        ]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
