<?php

namespace App\Http\Controllers\User;

use App\Models\User;
use App\Models\User\Menu;
use App\Models\User\Brand;
use App\Models\User\Skill;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Services\LogoService;
use App\Models\User\FooterText;
use App\Models\User\HeroStatic;
use App\Models\User\HomeSection;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\User\CounterInformation;
use Illuminate\Support\Facades\Session;
use App\Models\User\RealestateManagement\Amenity;

class OnboardingController extends Controller
{
    /**
     * Show Step 1 of Onboarding
     */
    public function index()
    {
        $user = Auth::user();

        // Redirect if already onboarded
        if ($user->onboarding_completed) {
            return redirect()->route('user-dashboard');
        }

        return view('user.onboarding');
    }

    /**
     * Store Step 1 Data and Redirect to Step 2
     *
     * if $home13 = $request->website_field = real-estate;
     * Real Estate → Theme: "home13" Activated
     * Lawyer → Theme: "home_six" Deactivated
     * Personal → Theme: "home_two" Deactivated
     */
    public function store(Request $request)
    {
        $request->validate([
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'favicon' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:512',
            'base_color' => 'required|string|max:7',
            'secondary_color' => 'required|string|max:7',
            'website_field' => 'required|string',
        ]);

        $user = Auth::user();
        $lang = Language::where([['user_id', Auth::id()], ['is_default', 1]])->first();
        $bss = BasicSetting::firstOrNew(['user_id' => $user->id]);

        $bss->base_color = $request->base_color;
        $bss->secondary_color = $request->secondary_color;

        $templateMapping = [
            'real-estate' => 'home13',
            'lawyer' => 'home_seven',
            'personal' => 'home_two'
        ];
        if (array_key_exists($request->website_field, $templateMapping)) {
            $bss->theme = $templateMapping[$request->website_field];
        }

        // Variables to store uploaded filenames
        $logoFilename = null;
        $faviconFilename = null;

        // Handle favicon upload (used as logo if no separate logo is provided)
        if ($request->hasFile('favicon')) {
            try {
                if ($bss->favicon && file_exists(public_path('assets/front/img/user/' . $bss->favicon))) {
                    @unlink(public_path('assets/front/img/user/' . $bss->favicon));
                }
                $faviconFile = $request->file('favicon');
                $faviconFilename = 'favicon_' . uniqid() . '.' . $faviconFile->getClientOriginalExtension();

                // Upload to user folder (for header)
                $faviconFile->move(public_path('assets/front/img/user/'), $faviconFilename);
                $bss->favicon = $faviconFilename; // Save only filename

                // If no logo is uploaded, use favicon as logo
                if (!$request->hasFile('logo')) {
                    $logoFilename = $faviconFilename;
                    // Copy to footer folder
                    if (!file_exists(public_path('assets/front/img/user/footer'))) {
                        mkdir(public_path('assets/front/img/user/footer'), 0755, true);
                    }
                    copy(
                        public_path('assets/front/img/user/' . $faviconFilename),
                        public_path('assets/front/img/user/footer/' . $faviconFilename)
                    );
                }
            } catch (\Exception $e) {
                \Log::error("Favicon upload failed: " . $e->getMessage());
                Session::flash('error', 'Favicon upload failed. Please try again.');
                return back();
            }
        }

        // Handle logo upload (overrides favicon if provided)
        if ($request->hasFile('logo')) {
            try {
                if ($bss->logo && file_exists(public_path('assets/front/img/user/' . $bss->logo))) {
                    @unlink(public_path('assets/front/img/user/' . $bss->logo));
                }
                $logoFile = $request->file('logo');
                $logoFilename = 'logo_' . uniqid() . '.' . $logoFile->getClientOriginalExtension();

                // Upload to user folder (for header)
                $logoFile->move(public_path('assets/front/img/user/'), $logoFilename);
                $bss->logo = $logoFilename; // Save only filename

                // Copy to footer folder (for footer)
                if (!file_exists(public_path('assets/front/img/user/footer'))) {
                    mkdir(public_path('assets/front/img/user/footer'), 0755, true);
                }
                copy(
                    public_path('assets/front/img/user/' . $logoFilename),
                    public_path('assets/front/img/user/footer/' . $logoFilename)
                );
            } catch (\Exception $e) {
                \Log::error("Logo upload failed: " . $e->getMessage());
                Session::flash('error', 'Logo upload failed. Please try again.');
                return back();
            }
        }

        // Save BasicSetting before calling updateUserFooterText
        $bss->save();


        // Real-estate updates, passing the logo filename
        if ($request->website_field == 'real-estate') {
            $this->updateUserMenu($user->id, $lang->id);
            $this->updateUserBasicSettings($user->id,$user->username);
            $this->updateUserHeroStatic($user->id, $lang->id ,$user->username);
            $this->updateUserHomePageText($user->id, $lang->id);
            $this->updateUserFooterText($user->id, $lang->id, $logoFilename);
            $this->updateUserCounterInformation($user->id, $lang->id);
            $this->updateUserBrands($user->id, $lang->id);
            $this->updateUserSkills($user->id, $lang->id, $request->secondary_color);
            $this->updateUserAmenities($user->id, $lang->id);
        }

        // log::info($user->username);
        // Update additional BasicSetting fields
        BasicSetting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'company_name' => $user->username,
                'industry_type' => $request->industry_type,
                'primary_color' => $request->secondary_color
            ]
        );

        return redirect()->route('onboarding.showStep2')->with('success', 'تم حفظ البيانات بنجاح!');
    }

    private function updateUserMenu($userId, $languageId)
    {
        $realEstateMenu = [
            ["text" => "الصفحة الرئيسية", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "home"],
            ["text" => "نبذة عنا", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "about-us"],
            ["text" => "المشاريع", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "projects"],
            ["text" => "العقارات", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "properties"],
            ["text" => "اتصل بنا", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "contact"]
        ];

        $menuJson = json_encode($realEstateMenu);

        $existingMenu = Menu::where(['user_id' => $userId, 'language_id' => $languageId])->first();

        if ($existingMenu) {
            $existingMenu->menus = $menuJson;
            $existingMenu->save();
        } else {
            Menu::create([
                'user_id' => $userId,
                'language_id' => $languageId,
                'menus' => $menuJson
            ]);
        }
    }

    // "logo": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
    // "favicon": "https://taearif.com/assets/front/img/user/67c6ef042c39b.jpeg",
    private function updateUserBasicSettings($userId ,$website_title)
    {
        $basicSettingsJson = '{
            "breadcrumb": "https://codecanyon8.kreativdev.com/estaty/assets/img/hero/static/6574372e0ad77.jpg",
            "preloader": "67c6ef042c39b.jpeg",
            "website_title" : "'.$website_title.'",
            "theme": "home13",
            "from_name": null,
            "is_quote": "1",
            "qr_color": "000000",
            "qr_size": "248",
            "qr_style": "square",
            "qr_eye_style": "square",
            "qr_margin": "0",
            "qr_text": null,
            "qr_text_color": "000000",
            "qr_text_size": "15",
            "qr_text_x": "50",
            "qr_text_y": "50",
            "qr_inserted_image": null,
            "qr_inserted_image_size": "20",
            "qr_inserted_image_x": "50",
            "qr_inserted_image_y": "50",
            "qr_type": "default",
            "qr_url": "https://taearif.com/",
            "whatsapp_status": "0",
            "whatsapp_number": null,
            "whatsapp_header_title": null,
            "whatsapp_popup_status": "0",
            "whatsapp_popup_message": null,
            "disqus_status": "0",
            "disqus_short_name": null,
            "analytics_status": "0",
            "measurement_id": null,
            "pixel_status": "0",
            "pixel_id": null,
            "tawkto_status": "0",
            "tawkto_direct_chat_link": null,
            "custom_css": null,
            "base_currency_symbol": "$",
            "base_currency_symbol_position": "left",
            "base_currency_text": "USD",
            "base_currency_rate": null,
            "base_currency_text_position": null,
            "is_recaptcha": "0",
            "google_recaptcha_site_key": null,
            "google_recaptcha_secret_key": null,
            "adsense_publisher_id": null,
            "timezone": "1",
            "features_section_image": null,
            "cv": null,
            "cv_original": null,
            "email_verification_status": "1",
            "cookie_alert_status": "0",
            "cookie_alert_text": null,
            "cookie_alert_button_text": null,
            "property_country_status": "1",
            "property_state_status": "1",
            "short_description": "شركة  هي شركة عقارية مبتكرة ومتخصصة في تقديم خدمات العقارات بجودة عالية وحلول مهنية.",
            "industry_type": "Real Estate Company"
        }';

        $basicSettingsArray = json_decode($basicSettingsJson, true);
        $user = User::find($userId);
        if (!$user) {
            return;
        }
        $existingSettings = BasicSetting::where('user_id', $userId)->first();

        if ($existingSettings) {
            $existingSettings->update($basicSettingsArray);
        } else {
            BasicSetting::create($basicSettingsArray);
        }
    }
    private function updateUserHeroStatic($userId, $languageId,$title)
    {
        $heroStaticJson = '{
            "img": "663e29ef26870d10bd72a4e9d24b908ff34e5a49.jpg",
            "title": "' . $title . '",
            "subtitle": "نبذة تعريفية",
            "btn_name": null,
            "btn_url": null,
            "hero_text": "نبذة تعريفية",
            "secound_btn_name": null,
            "secound_btn_url": null,
            "designation": null
        }';

        $heroStaticArray = json_decode($heroStaticJson, true);

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $language = Language::find($languageId);
        if (!$language) {
            return;
        }

        $heroStaticArray['user_id'] = $userId;
        $heroStaticArray['language_id'] = $languageId;

        $existingHeroStatic = HeroStatic::where(['user_id' => $userId, 'language_id' => $languageId])->first();

        if ($existingHeroStatic) {
            $existingHeroStatic->update($heroStaticArray);
        } else {
            HeroStatic::create($heroStaticArray);
        }
    }
    private function updateUserHomePageText($userId, $languageId)
    {
        $homePageTextJson = '{
            "about_image": "67d16c0704ed7.jpg",
            "why_choose_us_section_subtitle": "لدينا أسباب كثيرة لاختيارنا",
            "why_choose_us_section_video_image": "d1d67774227ae9d427fd1d391b578eb76c7ac1412.jpg",
            "why_choose_us_section_button_url": "http://taearif.com/",
            "why_choose_us_section_video_url": "https://taearif.com/",
            "why_choose_us_section_title": "لماذا أخترتنا",
            "why_choose_us_section_image_two": "779b32869d807d0b5287d561a4f8f62c65811059.jpg",
            "why_choose_us_section_image": "8334abcb1b2c7634fd7f72fe50d8f711d8dcc5a1.jpg",
            "why_choose_us_section_button_text": "خدماتنا",
            "why_choose_us_section_text": " علي الجانب الآخر نشجب ونستنكر هؤلاء الرجال المفتونون بنشوة اللحظة الهائمون في رغباتهم فلا يدركون ما يعقبها من الألم والأسي المحتم، واللوم كذلك يشمل هؤلاء الذين أخفقوا في واجباتهم نتيجة لضعف إرادتهم فيتساوي مع هؤلاء الذين يتجنبون وينأون عن تحمل الكدح والألم .",
            "about_image_two": "8334abcb1b2c7634fd7f72fe50d8f711d8dcc5a1.jpg",
            "about_title": "معلومات عنا",
            "about_subtitle": "نوفّر لك وجهتك المفضلة دائمًا",
            "about_content": "ن ينتقد شخص ما أراد أن يشعر بالسعادة التي لا تشوبها عواقب أليمةلا يوجد منفذ إضافي. عصر ن ينتقد شخص ما أراد أن يشعر بالسعادة التي لا تشوبها عواقب أليمة يستخدم معرف الحمية الغذائية والنقل في. رضا العميل بنسبة 100% نحن دائما نشعر بالقلق إزاء لدينا المشروع والعميل رضا العميل بنسبة 100% نحن دائما نشعر بالقلق إزاء لدينا المشروع والعميل ",
            "about_button_text": "معلومات عنا",
            "about_button_url": "https://taearif.com/",
            "skills_title": "عنوان قسم المهارات",
            "skills_subtitle": "عنوان قسم المهارات",
            "skills_content": "عنوان قسم المهارات",
            "service_title": "عنوان قسم الخدمة",
            "service_subtitle": "قسم الخدمة العنوان الفرعي",
            "experience_title": null,
            "experience_subtitle": null,
            "portfolio_title": "حالات مميزة",
            "portfolio_subtitle": null,
            "view_all_portfolio_text": "مشاهدة الكل",
            "testimonial_title": "شهادات العملاء",
            "testimonial_subtitle": "ما يقوله عملائنا عنا",
            "testimonial_image": "622ded84e62f8.jpg",
            "blog_title": "أخبارنا ومدونتنا",
            "blog_subtitle": null,
            "view_all_blog_text": "مشاهدة الكل",
            "team_section_title": "أعضاء الفريق",
            "team_section_subtitle": "تعرف على خبرائنا المحترفين",
            "video_section_image": "4e075552eb76535027695b317dcc7cfed9e1e3cf.jpg",
            "video_section_url": "https://www.youtube.com/watch?v=IjlYXtI2-GU",
            "work_process_section_title": "كيف نعمل",
            "work_process_section_subtitle": "عملية العمل لدينا",
            "work_process_section_img": "00733bb91bb288918e16a40dfc1516839e550f91.jpg",
            "quote_section_title": "إقتبس",
            "quote_section_subtitle": "ولكن لمعرفة من الذي ولد كل هذا الخطأ ",
            "counter_section_image": "622df3492b4f1.jpg",
            "work_process_btn_txt": "ابدأ مشروعًا",
            "work_process_btn_url": "http://example.com/",
            "contact_section_title": "ابدأ مشروعًا",
            "contact_section_subtitle": "ابدأ مشروعًا"
        }';

        // Validate JSON
        $homePageTextArray = json_decode($homePageTextJson, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('JSON decode failed in updateUserHomePageText: ' . json_last_error_msg());
            return false;
        }

        // Validate user and language
        $user = User::find($userId);
        if (!$user) {
            Log::warning("User not found in updateUserHomePageText: {$userId}");
            return false;
        }

        $language = Language::find($languageId);
        if (!$language) {
            Log::warning("Language not found in updateUserHomePageText: {$languageId}");
            return false;
        }

        $homePageTextArray['user_id'] = $userId;
        $homePageTextArray['language_id'] = $languageId;

        try {
            // Delete existing records to prevent duplicates
            HomePageText::where([
                'language_id' => $languageId
            ])->delete();

            // Insert
            HomePageText::create($homePageTextArray);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to process HomePageText for user {$userId}, language {$languageId}: " . $e->getMessage());
            return false;
        }
    }

    private function updateUserFooterText($userId, $languageId, $logoFilename = null)
    {
        $basicSetting = BasicSetting::where('user_id', $userId)->first();
        // Strip any existing full URL to filename if necessary
        $existingLogo = $basicSetting->logo ? basename($basicSetting->logo) : 'default_logo.png';
        $logoValue = $logoFilename ?? $existingLogo;



        $footerTextArray = [
            'logo' => $logoValue, // Store only filename
            'about_company' => 'قوة الاختيار غير مقيدة وعندما لا شيء يمنعنا من ذلك.',
            'copyright_text' => '<p>حقوق النشر © 2025. جميع الحقوق محفوظة <br /></p>',
            'newsletter_text' => 'قوة الاختيار غير مقيدة وعندما لا شيء يمنعنا من ذلك.',
            'bg_image' => '629946900c7edbf11b02a301001c6b36432d7876.png',
            'user_id' => $userId,
            'language_id' => $languageId,
        ];



        $user = User::find($userId);
        if (!$user) {
            \Log::warning("User not found for ID: $userId");
            return;
        }

        $language = Language::find($languageId);
        if (!$language) {
            \Log::warning("Language not found for ID: $languageId");
            return;
        }

        $existingFooterText = FooterText::where(['user_id' => $userId, 'language_id' => $languageId])->first();

        if ($existingFooterText) {
            $existingFooterText->update($footerTextArray);

        } else {
            FooterText::create($footerTextArray);

        }
    }

    private function updateUserCounterInformation($userId, $languageId)
    {
        $counterInformationJson = '[
            {
                "title": "هناك العديد من الأشكال المتوفرة لنص لوريم إيبسوم، لكن الأغلبية هي.",
                "icon": "fas fa-hand-holding-usd",
                "count": "14",
                "serial_number": "4"
            },
            {
                "title": "هناك العديد من الأشكال المتوفرة لنص لوريم إيبسوم، لكن الأغلبية هي.",
                "icon": "fas fa-city",
                "count": "30",
                "serial_number": "3"
            },
            {
                "title": "هناك العديد من الأشكال المتوفرة لنص لوريم إيبسوم، لكن الأغلبية هي.",
                "icon": "far fa-handshake",
                "count": "250",
                "serial_number": "2"
            },
            {
                "title": "هناك العديد من الأشكال المتوفرة لنص لوريم إيبسوم، لكن الأغلبية هي.",
                "icon": "fas fa-users",
                "count": "100",
                "serial_number": "1"
            }
        ]';

        $counterInformationArray = json_decode($counterInformationJson, true);

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $language = Language::find($languageId);
        if (!$language) {
            return;
        }

        foreach ($counterInformationArray as $counterData) {
            $counterData['user_id'] = $userId;
            $counterData['language_id'] = $languageId;

            $existingCounterInfo = CounterInformation::where([
                'user_id' => $userId,
                'language_id' => $languageId,
                'serial_number' => $counterData['serial_number']
            ])->first();

            if ($existingCounterInfo) {
                $existingCounterInfo->update($counterData);
            } else {
                CounterInformation::create($counterData);
            }
        }
    }
    private function updateUserBrands($userId, $languageId)
    {
        $brandsJson = '[
            {
                "brand_img": "5aa31b6db8a2537c0c5183ffeba3c41520b4dbb4.png",
                "brand_url": "http://example.com/",
                "serial_number": "1"
            },
            {
                "brand_img": "740956cb3655761bca3023188009714b9b9b8a81.png",
                "brand_url": "http://example.com/",
                "serial_number": "2"
            },
            {
                "brand_img": "bddbcaa9c1b9538ca70aaa63f48a8564fb33cd7f.png",
                "brand_url": "http://example.com/",
                "serial_number": "3"
            },
            {
                "brand_img": "78b3b58b9ff06a45495566b93072b2cdcd6fe326.png",
                "brand_url": "http://example.com/",
                "serial_number": "4"
            },
            {
                "brand_img": "bd6428fac35263a4cdfd309b58f72f765883bd7d.png",
                "brand_url": "http://example.com/",
                "serial_number": "5"
            }
        ]';

        $brandsArray = json_decode($brandsJson, true);

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        // $language = Language::find($languageId);
        // if (!$language) {
        //     return;
        // }

        foreach ($brandsArray as $brandData) {
            $brandData['user_id'] = $userId;
            // $brandData['language_id'] = $languageId;

            $existingBrand = Brand::where([
                'user_id' => $userId,
                // 'language_id' => $languageId,
                'serial_number' => $brandData['serial_number']
            ])->first();

            if ($existingBrand) {
                $existingBrand->update($brandData);
            } else {
                Brand::create($brandData);
            }
        }
    }
    private function updateUserSkills($userId, $languageId,$secondary_color)
    {
        $skillsJson = '[
            {
                "icon": "far fa-edit",
                "title": "Business Strategy",
                "slug": "business-strategy",
                "percentage": "90",
                "color": "' . $secondary_color . '",
                "serial_number": "1"
            },
            {
                "icon": "far fa-money-bill-alt",
                "title": "Financial Planing",
                "slug": "financial-planing",
                "percentage": "75",
                "color": "' . $secondary_color . '",
                "serial_number": "2"
            },
            {
                "icon": "fas fa-signal",
                "title": "Marketing Startegy",
                "slug": "marketing-startegy",
                "percentage": "85",
                "color": "' . $secondary_color . '",
                "serial_number": "3"
            },
            {
                "icon": "fas fa-handshake",
                "title": "Relationship Buildup",
                "slug": "relationship-buildup",
                "percentage": "80",
                "color": "' . $secondary_color . '",
                "serial_number": "4"
            }
        ]';

        $skillsArray = json_decode($skillsJson, true);

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $language = Language::find($languageId);
        if (!$language) {
            return;
        }

        foreach ($skillsArray as $skillData) {
            $skillData['user_id'] = $userId;
            $skillData['language_id'] = $languageId;

            $existingSkill = Skill::where([
                'user_id' => $userId,
                'language_id' => $languageId,
                'serial_number' => $skillData['serial_number']
            ])->first();

            if ($existingSkill) {
                $existingSkill->update($skillData);
            } else {
                Skill::create($skillData);
            }
        }
    }
    private function updateUserAmenities($userId, $languageId)
    {
        $amenitiesJson = '[
            {
                "name": "تجربة",
                "slug": "تجربة",
                "icon": "fab fa-accusoft",
                "status": "1",
                "serial_number": "0"
            },
            {
                "name": "ميزة خاصة بالعقار",
                "slug": "ميزة-خاصة-بالعقار",
                "icon": "fab fa-accusoft",
                "status": "1",
                "serial_number": "1"
            }
        ]';

        $amenitiesArray = json_decode($amenitiesJson, true);

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        $language = Language::find($languageId);
        if (!$language) {
            return;
        }

        foreach ($amenitiesArray as $amenityData) {
            $amenityData['user_id'] = $userId;
            $amenityData['language_id'] = $languageId;

            $existingAmenity = Amenity::where([
                'user_id' => $userId,
                'language_id' => $languageId,
                'serial_number' => $amenityData['serial_number']
            ])->first();

            if ($existingAmenity) {
                $existingAmenity->update($amenityData);
            } else {
                Amenity::create($amenityData);
            }
        }
    }


    /**
     * Show Step 2 of Onboarding
     */
    public function showStep2()
    {
        $user = Auth::user();
        $data['sections'] = HomeSection::where('user_id', Auth::guard('web')->user()->id)->first();

        if ($user->onboarding_completed) {
            return redirect()->route('user-dashboard');
        }

        return view('user.onboarding_step2', compact('data'));
    }

    /**
     * Process Step 2 and Complete Onboarding
     */


    public function step2(Request $request)
    {
        $user_id = Auth::guard('web')->user()->id;

        // Retrieve existing section or create a new one
        $sections = HomeSection::where('user_id', $user_id)->first();
        if (is_null($sections)) {
            $sections = new HomeSection;
            $sections->user_id = $user_id;
        }

        // Define all possible checkbox fields
        $checkboxFields = [
            'intro_section',
            'portfolio_section',
            'featured_services_section',
            'why_choose_us_section',
            'counter_info_section',
            'video_section',
            'team_members_section',
            'skills_section',
            'testimonials_section',
            'blogs_section',
            'brand_section',
            'top_footer_section',
            'copyright_section'
        ];

        // Set all checkboxes to 0 by default
        foreach ($checkboxFields as $field) {
            $sections->$field = 0;
        }

        // Update the fields that are present in the request (checked boxes)
        foreach ($request->except('_token') as $key => $value) {
            if (in_array($key, $checkboxFields)) {
                $sections->$key = 1;
            }
        }

        // Save the changes
        $sections->save();

        // Flash success message and return
        Session::flash('success', 'Sections customized successfully!');
        return back();
    }


    /**
     * Skip Onboarding and Redirect to Dashboard
     */
    public function skip()
    {
        $user = Auth::user();
        $user->update(['onboarding_completed' => true]);

        return redirect()->route('onboarding.showStep2');
    }
}
