<?php

namespace App\Providers;

use App\Models\Menu;
use App\Models\User;
use App\Models\Social;
use App\Models\Language;
use App\Models\User\SEO;
use App\Models\UserStep;
use App\Models\User\Blog;
use App\Models\Api\ApiMenuItem;
use App\Models\User\FooterText;
use App\Models\User\HomeSection;
// use App\Models\Api\ApiBannerSetting;
use App\Models\User\UserContact;
use App\Models\User\UserService;
use App\Models\Api\FooterSetting;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use App\Models\Api\ApiMenuSetting;
use App\Models\Api\GeneralSetting;
use Illuminate\Support\Facades\DB;
use App\Models\Api\ApiBannerSetting;
use App\Models\User\FooterQuickLink;
use App\Models\User\UserShopSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use App\Models\User\Menu as UserMenu;
use App\Models\User\UserItemCategory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\User\Language as UserLanguage;
use App\Models\Api\ApiPixel;
use App\Models\Api\CustomerDropdownSetting;
use App\Models\Api\UserPropertyRequest;
use App\Models\Api\ApiCustomerInquiry;
use App\Observers\Matching\UsersPropertyRequestObserver;
use App\Observers\Matching\ApiCustomerInquiryObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {

        Paginator::useBootstrap();
        if (!app()->runningInConsole()) {
            try {
                $socials = Social::orderBy('serial_number', 'ASC')->get();
                $langs = Language::all();
            } catch (\Exception $e) {
                // Handle database connection errors gracefully
                \Log::warning('AppServiceProvider: Database connection failed during bootstrap', [
                    'error' => $e->getMessage()
                ]);
                $socials = collect();
                $langs = collect();
            }

            View::composer('*', function ($view) {
                // $api_Banner_settingsData = null;
                $api_general_settingsData = null;

                $username = request()->segment(1);
                $user = User::where('username', $username)->first();

                // if ($user) {
                    // $api_Banner_settingsData = ApiBannerSetting::where('user_id', $user->id)->first();
                    // $api_general_settingsData = GeneralSetting::where('user_id', $user->id)->first();
                // }

                // if ($api_Banner_settingsData && is_string($api_Banner_settingsData)) {
                //     $api_Banner_settingsData = json_decode($api_Banner_settingsData);
                // }

                if (session()->has('lang')) {
                    $currentLang = Language::where('code', session()->get('lang'))->first();
                } else {
                    $currentLang = Language::where('is_default', 1)->first();
                }

                $bs = $currentLang->basic_setting;
                $be = $currentLang->basic_extended;
                Config::set('app.timezone', $bs->timezone);

                $menus = Menu::where('language_id', $currentLang->id)->count() > 0
                    ? Menu::where('language_id', $currentLang->id)->first()->menus
                    : json_encode([]);

                $rtl = $currentLang->rtl == 1 ? 1 : 0;

                $view->with('bs', $bs);
                $view->with('be', $be);
                // $view->with('api_Banner_settingsData', $api_Banner_settingsData);
                // $view->with('api_general_settingsData', $api_general_settingsData);
                $view->with('currentLang', $currentLang);
                $view->with('menus', $menus);
                $view->with('rtl', $rtl);
            });

            View::composer(['user.*'], function ($view) {
                if (Auth::check()) {
                    $userBs = BasicSetting::with('timezoneinfo')->where('user_id', Auth::user()->id)->first();
                    $userRoomSettings = DB::table('user_room_settings')->where('user_id', Auth::guard('web')->user()->id)->first();
                    $api_general_settingsData = GeneralSetting::where('user_id', Auth::user()->id)->first();


                    $view->with(
                        [
                            'userBs' => $userBs,
                            'userapi_general_settingsData' => $api_general_settingsData,
                            'roomSetting' => $userRoomSettings
                        ]
                    );
                    Config::set('app.timezone', $userBs->timezoneinfo->timezone ?? '');
                    $userId = Auth::guard('web')->user()->id;
                    if (request()->has('language')) {
                        $lang = UserLanguage::where([
                            ['code', request('language')],
                            ['user_id', $userId]
                        ])->first();
                        session()->put('currentLangCode', request('language'));
                    } else {
                        $lang = UserLanguage::where([
                            ['is_default', 1],
                            ['user_id', $userId]
                        ])->first();
                        session()->put('currentLangCode', $lang->code);
                    }
                    $keywords = json_decode($lang->keywords, true);
                    $view->with('keywords', $keywords);
                }
            });

            View::composer(['user-front.*'], function ($view) {
                // Skip error pages to prevent recursive abort() calls
                if (str_contains($view->getName(), 'user-front.errors.')) {
                    return;
                }
                if (session()->has('user_midtrans')) {
                    $user = session()->get('user_midtrans');
                } else {
                    $user = getUser();
                }

                if (session()->has('user_lang')) {
                    $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
                    if (empty($userCurrentLang)) {
                        $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                        session()->put('user_lang', $userCurrentLang->code);
                    }
                } else {
                    $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                }

                $keywords = json_decode(optional($userCurrentLang)->keywords ?? '{}', true);


                // if (UserMenu::where('language_id', $userCurrentLang->id)->where('user_id', $user->id)->count() > 0) {
                //     $userMenus = UserMenu::where('language_id', $userCurrentLang->id)->where('user_id', $user->id)->first()->menus;
                // } else {
                //     $userMenus = json_encode([]);
                // }

                $menuItems = ApiMenuItem::with('children')
                ->where('user_id', $user->id)
                ->where('is_active', 1)
                ->whereNull('parent_id')
                ->orderBy('order')
                ->get();

                // If the result is empty
                if ($menuItems->isEmpty()) {
                    $menuItems = collect();
                }


                $userBs = BasicSetting::where('user_id', $user->id)->with('timezoneinfo')->first();
                $userRoomSettings = DB::table('user_room_settings')->where('user_id', $user->id)->first();

                // Config::set('app.timezone', $userBs->timezoneinfo->timezone);

                if ($userBs && $userBs->timezoneinfo && $userBs->timezoneinfo->timezone) {
                    Config::set('app.timezone', $userBs->timezoneinfo->timezone);
                } else {
                    Config::set('app.timezone', 'UTC');
                }

                Config::set('captcha.sitekey', optional($userBs)->google_recaptcha_site_key ?? '');
                Config::set('captcha.secret', optional($userBs)->google_recaptcha_secret_key ?? '');
                $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
                if (empty($userCurrentLang)) {
                    $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                    session()->put('user_lang', $userCurrentLang->code);
                }

                // $social_medias = $user->social_media()->get() ?? collect([]);
                // $social_media = $user->social_media()->get() ?? collect([]);
                $userSeo = SEO::where('language_id', $userCurrentLang->id)->where('user_id', $user->id)->first();
                $userLangs = UserLanguage::where('user_id', $user->id)->get();
                $userShopSetting = UserShopSetting::where('user_id', $user->id)->first();

                // $packagePermissions = UserPermissionHelper::packagePermission($user->id);
                // $packagePermissions = json_decode($packagePermissions, true);
                if ($user && $user->id) {
                    $packagePermissions = UserPermissionHelper::packagePermission($user->id);
                    $packagePermissions = json_decode($packagePermissions, true);
                } else {
                    $packagePermissions = [];
                }


                $footerData = FooterText::where('language_id', $userCurrentLang->id)
                    ->where('user_id', $user->id)
                    ->first();
                $api_footerData = FooterSetting::where('user_id', $user->id)->first();
                $api_general_settingsData = GeneralSetting::where('user_id', $user->id)->first();
                // api_pixelsData
                $userApi_pixelsData = ApiPixel::where('user_id', $user->id)->get();
                $view->with('userApi_pixelsData', $userApi_pixelsData);

                if ($userBs && $userBs->theme == 'home_seven') {
                    $fservices = UserService::where('lang_id', $userCurrentLang->id)
                        ->where('user_id', $user->id)
                        ->get();
                    $view->with('fservices', $fservices);
                }

                if ($userBs && $userBs->theme == 'home_eight') {
                    $categories = UserItemCategory::query()
                        ->where('user_id', $user->id)
                        ->where('language_id', $userCurrentLang->id)
                        ->with('subcategories')
                        ->where('status', 1)
                        ->get();
                    $view->with('categories', $categories);
                }


                $footerQuickLinks = FooterQuickLink::where('language_id', $userCurrentLang->id)
                    ->where('user_id', $user->id)
                    ->orderBy('serial_number', 'asc')
                    ->get();
                $cookieAlert = BasicSetting::where('user_id', $user->id)
                    // ->where('language_id', $userCurrentLang->id)
                    ->select('cookie_alert_status', 'cookie_alert_text', 'cookie_alert_button_text')
                    ->first();
                $footerRecentBlogs = User\Blog::query()
                    ->where('user_id', $user->id)
                    ->where('language_id', $userCurrentLang->id)
                    ->orderBy('id', 'DESC')
                    ->limit(3)
                    ->get();
                $userContact = UserContact::where([
                    ['user_id', $user->id],
                    ['language_id', $userCurrentLang->id]
                ])->first();

                $home_text = User\HomePageText::query()
                    ->where([
                        ['user_id', $user->id],
                        ['language_id', $userCurrentLang->id]
                    ])->first();
                $home_sections = User\HomeSection::where('user_id', $user->id)->first();

                // Add membership logic for footer branding
                $showTaearifBranding = true; // Default to taearif branding
                if ($user && $user->id) {
                    // Get the latest active membership (not expired)
                    $currentMembership = \App\Models\Membership::query()->where([
                        ['user_id', '=', $user->id],
                        ['status', '=', 1],
                        ['start_date', '<=', \Carbon\Carbon::now()->format('Y-m-d')],
                        ['expire_date', '>=', \Carbon\Carbon::now()->format('Y-m-d')]
                    ])->orderBy('id', 'DESC')->first();
                    
                    // Show custom copyright ONLY if user has active membership AND it's not free package
                    if ($currentMembership && $currentMembership->package_id != 16) {
                        $showTaearifBranding = false; // Show custom copyright for non-free active packages
                    }
                }

                $view->with('user', $user);
                $view->with('home_text', $home_text);
                $view->with('home_sections', $home_sections);
                $view->with('userSeo', $userSeo);
                $view->with('userBs', $userBs);
                $view->with('userMenus', $menuItems);
                $view->with('userFooterQuickLinks', $footerQuickLinks);
                $view->with('userFooterData', $footerData);
                $view->with('userApi_footerData', $api_footerData);
                $view->with('userApi_general_settingsData', $api_general_settingsData);
                $view->with('userFooterRecentBlogs', $footerRecentBlogs);
                $view->with('roomSetting', $userRoomSettings);
                $view->with('userContact', $userContact);
                // $view->with('social_medias', $social_medias);
                // $view->with('social_media', $social_media);
                $view->with('userCurrentLang', $userCurrentLang);
                $view->with('userLangs', $userLangs);
                $view->with('keywords', $keywords);
                $view->with('cookieAlertInfo', $cookieAlert);
                $view->with('packagePermissions', $packagePermissions);
                $view->with('userShopSetting', $userShopSetting);
                $view->with('showTaearifBranding', $showTaearifBranding);
                //
                if ($userBs && $userBs->theme == 'home_seven') {
                    $view->with('fservices', $fservices);
                }
                if ($userBs && $userBs->theme == 'home_eight') {
                    $view->with('categories', $categories);
                }


            });

            View::share('langs', $langs);
            View::share('socials', $socials);
        }

        // if (Schema::hasTable('basic_settings')) { // Avoid migration errors
        //     $timezone = BasicSetting::first()?->timezone ?? 'UTC';
        //     Config::set('app.timezone', $timezone);
        //     date_default_timezone_set($timezone);
        // }


        View::composer('user.layout', function ($view) {
            $user = Auth::guard('web')->user();
            $progressSteps = [];

            if ($user) {
                $steps = UserStep::firstOrCreate(['user_id' => $user->id]);
                $progressSteps = [
                    ['url' => 'user.basic_settings.general-settings','title' => 'تحديث الشعار الخاص بك', 'completed' => (bool) $steps->logo_uploaded],
                    ['url' => 'user.basic_settings.general-settings','title' => 'تحديث ايقونة الموقع', 'completed' => (bool) $steps->favicon_uploaded],
                    ['url' => 'user.basic_settings.general-settings','title' => 'تحديث اسم الموقع الخاص بك', 'completed' => (bool) $steps->website_named],
                    ['url' => 'user.home.page.text.edit','title' => 'تحديث بيانات الصفحة الرئيسية', 'completed' => (bool) $steps->homepage_updated],
                    ['url' => 'user.basic_settings.general-settings','title' => 'تحديث بيانات الصفحة عن الشركه', 'completed' => (bool) $steps->homepage_about_update],
                    ['url' => 'user.basic_settings.general-settings','title' => 'تحديث بيانات الصفحة معلومات الاتصال', 'completed' => (bool) $steps->contacts_social_info],
                    ['url' => 'user.home_page.hero.slider_version','title' => 'تحديث بيانات الصفحة البانرات', 'completed' => (bool) $steps->banner],
                    ['url' => 'user.home_page.hero.slider_version','title' => 'تحديث بيانات الصفحة صورة اعلى الصفحات الفرعية', 'completed' => (bool) $steps->sub_pages_upper_image],
                    ['url' => 'user.menu_builder.index','title' => 'تحديث بيانات الصفحة منشئ القائمة', 'completed' => (bool) $steps->menu_builder],
                    ['url' => 'user.services.index','title' => 'تحديث بيانات الصفحة خدماتنا', 'completed' => (bool) $steps->services],
                    ['url' => 'user.footer.text','title' => 'تحديث بيانات الصفحة الذيل', 'completed' => (bool) $steps->footer],
                ];
            }

            $view->with('steps', $progressSteps);
        });

        // View composer for customer dropdown - provides variables to all views that include it
        View::composer('user-front.realestate.partials.customer-dropdown', function ($view) {
            $user = getUser();
            
            if ($user) {
                $dropdownSettings = CustomerDropdownSetting::where('user_id', $user->id)->first();
                
                $view->with([
                    'customer_dropdown_visible' => $dropdownSettings ? $dropdownSettings->is_visible : true,
                    'customer_dropdown_show_login' => $dropdownSettings ? $dropdownSettings->show_login : true,
                    'customer_dropdown_show_register' => $dropdownSettings ? $dropdownSettings->show_register : true,
                    'customer_dropdown_show_dashboard' => $dropdownSettings ? $dropdownSettings->show_dashboard : true,
                    'customer_dropdown_show_logout' => $dropdownSettings ? $dropdownSettings->show_logout : true,
                ]);
            } else {
                // Default values when no user is available
                $view->with([
                    'customer_dropdown_visible' => true,
                    'customer_dropdown_show_login' => true,
                    'customer_dropdown_show_register' => true,
                    'customer_dropdown_show_dashboard' => true,
                    'customer_dropdown_show_logout' => true,
                ]);
            }
        });

        // Register observers for matching triggers
        try {
            UserPropertyRequest::observe(UsersPropertyRequestObserver::class);
            ApiCustomerInquiry::observe(ApiCustomerInquiryObserver::class);
        } catch (\Throwable $e) {
            \Log::warning('Failed to register matching observers', ['error' => $e->getMessage()]);
        }
    }
}
