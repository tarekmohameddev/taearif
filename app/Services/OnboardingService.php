<?php

namespace App\Services;


use App\Models\User\Menu;
use App\Models\User\Language;
use App\Models\User\BasicSetting;
use App\Models\Api\GeneralSetting;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiMenuItem;
use App\Models\Api\ApiMenuSetting;
use App\Models\Api\FooterSetting;
use App\Models\User;
use App\Models\User\BasicSetting as UserBasicSetting;
use App\Models\User\Language as UserLanguage;
use App\Models\User\Menu as UserMenu;
use App\Models\User\GeneralSetting as UserGeneralSetting;
use App\Models\User\FooterSetting as UserFooterSetting;
use App\Models\Api\Menu as ApiMenu;
use Illuminate\Support\Facades\DB;

class OnboardingService extends Controller
{
    public function applyDefaultsFor($user)
    {
        try {
            // Get default language (system-wide)
            $lang = Language::where('is_default', 1)->first();

            // Default payload
            $title = "hsl hgav;i";
            $category = "realestate";
            $colors = [
                'primary' => '#1e40af',
                'secondary' => '#3b82f6',
                'accent' => '#93c5fd',
            ];
            $logo = "logos/20fd8e4f-ecee-41f4-aaed-b5ebc71b3fcc.jpg";
            $favicon = "logos/20fd8e4f-ecee-41f4-aaed-b5ebc71b3fcc.jpg";

            // Basic settings
            $bss = BasicSetting::firstOrNew(['user_id' => $user->id]);
            $bss->base_color = $colors['primary'];
            $bss->secondary_color = $colors['secondary'];
            $bss->accent_color = $colors['accent'];
            $bss->logo = $logo;
            $bss->favicon = $favicon;
            $bss->company_name = $title;
            $bss->industry_type = $category;

            $templateMapping = [
                'realestate' => 'home13',
                'lawyer' => 'home_seven',
                'personal' => 'home_two'
            ];
            if (array_key_exists($category, $templateMapping)) {
                $bss->theme = $templateMapping[$category];
            }

            $bss->save();

            // Menu setup
            if ($category === 'realestate' && $lang) {
                $this->updateUserMenu($user->id, $lang->id);
            }

            // Footer settings

            $this->seedDefaultApiMenuItems($user->id);
            $this->ApiMenuSetting($user->id);
            $this->saveDefaultFooterSettings($user->id);

            $user->onboarding_completed = false;
            $user->save();

            GeneralSetting::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'site_name' => $title,
                    'favicon'   => $favicon,
                    'logo'      => $logo,
                ]
            );
        } catch (\Exception $e) {
            Log::error("Auto onboarding failed for user {$user->id}: " . $e->getMessage());
        }
    }

    private function updateUserMenu($userId, $languageId)
    {
        $realEstateMenu = [
            ["text" => "الصفحة الرئيسية", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "home"],
            ["text" => "اتصل بنا", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "contact"],
            ["text" => "من نحن", "href" => "", "icon" => "empty", "target" => "_self", "title" => "", "type" => "about"]
        ];

        $menuJson = json_encode($realEstateMenu, JSON_UNESCAPED_UNICODE);

        $menuModel = Menu::where([
            'user_id' => $userId,
            'language_id' => $languageId
        ])->first();

        if ($menuModel) {
            $menuModel->menus = $menuJson;
            $menuModel->save();
        } else {
            Menu::create([
                'user_id' => $userId,
                'language_id' => $languageId,
                'menus' => $menuJson
            ]);
        }
    }


    private function seedDefaultApiMenuItems(int $userId)
    {
        $defaultItems = [
            [
                "text" => "الرئيسية",
                "label" => "الرئيسية",
                "type" => "home",
                "url" => "/",
                "target" => "_self",
                "is_external" => false,
                "is_active" => true,
                "order" => 1,
                "parent_id" => null,
                "show_on_mobile" => true,
                "show_on_desktop" => true,
            ],
            [
                "text" => "من نحن",
                "label" => "من نحن",
                "type" => "about",
                "url" => "/about",
                "target" => "_self",
                "is_external" => false,
                "is_active" => true,
                "order" => 2,
                "parent_id" => null,
                "show_on_mobile" => true,
                "show_on_desktop" => true,
            ],
            [
                "text" => "اتصل بنا",
                "label" => "اتصل بنا",
                "type" => "contact",
                "url" => "/contact",
                "target" => "_self",
                "is_external" => false,
                "is_active" => true,
                "order" => 3,
                "parent_id" => null,
                "show_on_mobile" => true,
                "show_on_desktop" => true,
            ],
        ];

        foreach ($defaultItems as $item) {
            ApiMenuItem::create(array_merge($item, ['user_id' => $userId]));
        }
    }
    // API Menu settings
    private function ApiMenuSetting($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return;
        }
        // API Menu settings
        $menuSettings = [
            'menu_position' => 'top',
            'menu_style' => 'default',
            'mobile_menu_type' => 'default',
            'is_sticky' => true,
            'is_transparent' => false,
            'status' => true,
        ];
        $menuSetting = ApiMenuSetting::where('user_id', $userId)->first();
        if (!$menuSetting) {
            $menuSetting = new ApiMenuSetting();
        }
        $menuSetting->user_id = $userId;
        $menuSetting->menu_position = $menuSettings['menu_position'];
        $menuSetting->menu_style = $menuSettings['menu_style'];
        $menuSetting->mobile_menu_type = $menuSettings['mobile_menu_type'];
        $menuSetting->is_sticky = $menuSettings['is_sticky'];
        $menuSetting->is_transparent = $menuSettings['is_transparent'];
        $menuSetting->status = $menuSettings['status'];
        $menuSetting->save();
    }

    // Save default footer settings
    private function saveDefaultFooterSettings(int $userId)
    {
        $general = [
            "companyName" => "اسم الشركة",
            "address" => "عنوان الشركة، المدينة، البلد",
            "phone" => "+966 5XXXXXXXX",
            "email" => "info@example.com",
            "workingHours" => "الأحد - الخميس: 9:00 ص - 5:00 م",
            "copyrightText" => "© " . date('Y') . " جميع الحقوق محفوظة",
            "showCopyright" => true,
            "showContactInfo" => true,
            "showWorkingHours" => true,
        ];

        $social = [
            ["id" => "1", "platform" => "facebook", "url" => "https://facebook.com/", "enabled" => true],
            ["id" => "2", "platform" => "twitter", "url" => "https://twitter.com/", "enabled" => true],
            ["id" => "3", "platform" => "instagram", "url" => "https://instagram.com/", "enabled" => true],
            ["id" => "4", "platform" => "linkedin", "url" => "https://linkedin.com/", "enabled" => false],
            ["id" => "5", "platform" => "youtube", "url" => "https://youtube.com/", "enabled" => false],
        ];

        $columns = [
            [
                "id" => "1",
                "title" => "روابط سريعة",
                "links" => [
                    ["id" => "1-1", "text" => "الرئيسية", "url" => "/"],
                    ["id" => "1-2", "text" => "من نحن", "url" => "/about"],
                    ["id" => "1-3", "text" => "خدماتنا", "url" => "/services"],
                    ["id" => "1-4", "text" => "اتصل بنا", "url" => "/contact"],
                ],
                "enabled" => true,
            ],
            [
                "id" => "3",
                "title" => "الدعم",
                "links" => [
                    ["id" => "3-1", "text" => "الأسئلة الشائعة", "url" => "/faq"],
                    ["id" => "3-2", "text" => "سياسة الخصوصية", "url" => "/privacy"],
                    ["id" => "3-3", "text" => "الشروط والأحكام", "url" => "/terms"],
                ],
                "enabled" => true,
            ],
        ];

        $newsletter = [
            "enabled" => true,
            "title" => "اشترك في نشرتنا البريدية",
            "description" => "اشترك للحصول على آخر الأخبار والعروض",
            "buttonText" => "اشتراك",
            "placeholderText" => "أدخل بريدك الإلكتروني",
        ];

        $style = [
            "layout" => "full-width",
            "backgroundColor" => "#1f2937",
            "textColor" => "#ffffff",
            "accentColor" => "#3b82f6",
            "columns" => 4,
            "showSocialIcons" => true,
            "socialIconsPosition" => "top",
        ];

        // Check if a record already exists
        $existing = DB::table('api_footer_settings')->where('user_id', $userId)->first();

        $data = [
            'user_id' => $userId,
            'general' => json_encode($general, JSON_UNESCAPED_UNICODE),
            'social' => json_encode($social, JSON_UNESCAPED_UNICODE),
            'columns' => json_encode($columns, JSON_UNESCAPED_UNICODE),
            'newsletter' => json_encode($newsletter, JSON_UNESCAPED_UNICODE),
            'style' => json_encode($style, JSON_UNESCAPED_UNICODE),
            'status' => true,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('api_footer_settings')->where('user_id', $userId)->update($data);
        } else {
            $data['created_at'] = now();
            DB::table('api_footer_settings')->insert($data);
        }
    }

}
