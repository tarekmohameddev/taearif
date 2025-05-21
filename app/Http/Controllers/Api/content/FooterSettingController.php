<?php

namespace App\Http\Controllers\Api\Content;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Api\FooterSetting;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;


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
                ],
                'columns' => [
                    [
                        'id' => '1',
                        'title' => 'روابط سريعة',
                        'links' => [
                            ['id' => '1-1', 'text' => 'الرئيسية', 'url' => '/'],
                            ['id' => '1-2', 'text' => 'من نحن', 'url' => '/about'],
                            ['id' => '1-3', 'text' => 'خدماتنا', 'url' => '/services'],
                            ['id' => '1-4', 'text' => 'اتصل بنا', 'url' => '/contact'],
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
    public function update(Request $request)
    {
        $user = $request->user();

        // Validation
        $validator = Validator::make($request->all(), [
            'status' => 'required|boolean',
            'general' => 'required|array',
            'general.companyName' => 'required|string|max:100',
            'general.address' => 'nullable|string|max:255',
            'general.phone' => 'nullable|string|max:20',
            'general.email' => 'nullable|email|max:100',
            'general.workingHours' => 'nullable|string|max:100',
            'general.showContactInfo' => 'boolean',
            'general.showWorkingHours' => 'boolean',
            'general.copyrightText' => 'nullable|string|max:255',
            'general.showCopyright' => 'boolean',

            'social' => 'required|array',
            'social.*.id' => 'required|string',
            'social.*.platform' => 'required|string|in:facebook,twitter,instagram,linkedin,youtube',
            'social.*.url' => 'nullable|string|max:255',
            'social.*.enabled' => 'boolean',

            'columns' => 'required|array',
            'columns.*.id' => 'required|string',
            'columns.*.title' => 'required|string|max:100',
            'columns.*.enabled' => 'boolean',
            'columns.*.links' => 'required|array',
            'columns.*.links.*.id' => 'required|string',
            'columns.*.links.*.text' => 'required|string|max:100',
            'columns.*.links.*.url' => 'required|string|max:255',

            'newsletter' => 'required|array',
            'newsletter.enabled' => 'boolean',
            'newsletter.title' => 'required|string|max:100',
            'newsletter.description' => 'nullable|string|max:255',
            'newsletter.buttonText' => 'required|string|max:50',
            'newsletter.placeholderText' => 'required|string|max:100',

            'style' => 'required|array',
            'style.layout' => 'required|string|in:full-width,contained',
            'style.backgroundColor' => 'required|string|max:20',
            'style.textColor' => 'required|string|max:20',
            'style.accentColor' => 'required|string|max:20',
            'style.columns' => 'required|integer|min:1|max:4',
            'style.showSocialIcons' => 'boolean',
            'style.socialIconsPosition' => 'required|string|in:top,bottom',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find or create settings
        $settings = FooterSetting::updateOrCreate(
            ['user_id' => $user->id],
            $request->only(['general', 'social', 'columns', 'newsletter', 'style', 'status'])
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
