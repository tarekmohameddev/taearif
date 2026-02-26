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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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
use Illuminate\Database\Eloquent\Relations\Relation;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\Message;
use App\Models\EmailMessageLog;
use App\Models\SmsMessageLog;
use App\Models\User\Language as UserLanguage;
use App\Models\Api\ApiPixel;
use App\Models\Api\CustomerDropdownSetting;
use App\Models\Api\UserPropertyRequest;
use App\Models\Api\ApiCustomerInquiry;
use App\Models\Api\Post;
use App\Models\Api\UserApiCustomerReminder;
use App\Observers\Matching\UsersPropertyRequestObserver;
use App\Observers\Matching\ApiCustomerInquiryObserver;
use App\Observers\UserApiCustomerReminderObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            \App\Domain\Communication\Contracts\CommunicationService::class,
            \App\Domain\Communication\Services\CommunicationServiceImpl::class
        );
        $this->app->bind(
            \App\Domain\Communication\Contracts\MessageDispatcher::class,
            \App\Domain\Communication\Services\MessageDispatcherImpl::class
        );
        $this->app->bind(
            \App\Domain\Communication\Contracts\CreditService::class,
            \App\Domain\Communication\Services\CreditServiceImpl::class
        );
        $this->app->singleton(
            \App\Domain\Communication\Services\IdempotencyService::class
        );
        $this->app->bind(
            \App\Domain\Communication\Sms\Contracts\SmsGatewayClient::class,
            \App\Domain\Communication\Sms\Services\Gateways\ConfiguredSmsGatewayClient::class
        );
        $this->app->bind(
            \App\Domain\Communication\Sms\Contracts\SmsDispatcher::class,
            \App\Domain\Communication\Sms\Services\SmsDispatcherService::class
        );
        $this->app->bind(
            \App\Domain\Communication\Email\Contracts\EmailGatewayClient::class,
            \App\Domain\Communication\Email\Services\Gateways\ConfiguredEmailGatewayClient::class
        );
        $this->app->bind(
            \App\Domain\Communication\Email\Contracts\EmailDispatcher::class,
            \App\Domain\Communication\Email\Services\EmailDispatcherService::class
        );
        $this->app->bind(
            \App\Domain\Communication\Email\Contracts\EmailGatewayClient::class,
            \App\Domain\Communication\Email\Services\Gateways\ConfiguredEmailGatewayClient::class
        );
        $this->app->bind(
            \App\Domain\Communication\Email\Contracts\EmailDispatcher::class,
            \App\Domain\Communication\Email\Services\EmailDispatcherService::class
        );

        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppNumberService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppConversationService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppTemplateService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppAutomationRuleService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppAiConfigService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppWebhookService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppStatsService::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppChannelSender::class);
        $this->app->singleton(\App\Domain\Communication\WhatsApp\Services\WhatsAppServiceDispatchAdapter::class);
        $this->app->singleton(\App\Domain\Communication\Services\DeliveryAttemptRecorder::class);
        $this->app->singleton(\App\Domain\Communication\Services\CommunicationRetrySender::class);

        $this->app->singleton(\App\Domain\Communication\Services\WebhookEventNormalizer::class);
        $this->app->singleton(\App\Domain\Communication\Services\StatusTransitionGuard::class);
        $this->app->singleton(\App\Domain\Communication\Services\RetryPolicyHelper::class);
        $this->app->singleton(\App\Domain\Communication\Services\WebhookEventJournal::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Relation::enforceMorphMap([
            User::class => User::class,
            'message' => Message::class,
            'email_message_log' => EmailMessageLog::class,
            'sms_message_log' => SmsMessageLog::class,
            UserPropertyRequest::class => UserPropertyRequest::class,
            ApiCustomerInquiry::class => ApiCustomerInquiry::class,
            Post::class => Post::class,
        ]);

        Paginator::useBootstrap();
        if (!app()->runningInConsole()) {
            try {
                $socials = Social::orderBy('serial_number', 'ASC')->get();
                $langs = Language::all();
            } catch (\Exception $e) {
                // Handle database connection errors gracefully
                Log::warning('AppServiceProvider: Database connection failed during bootstrap', [
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
                    $authenticatedUser = Auth::guard('web')->user();
                    // Use tenantOwnerId() to get the correct ID for settings (Tenant ID for employees, User ID for tenants)
                    $settingsUserId = $authenticatedUser->tenantOwnerId();

                    $userBs = BasicSetting::with('timezoneinfo')->where('user_id', $settingsUserId)->first();
                    $userRoomSettings = DB::table('user_room_settings')->where('user_id', $settingsUserId)->first();
                    $api_general_settingsData = GeneralSetting::where('user_id', $settingsUserId)->first();


                    $view->with(
                        [
                            'userBs' => $userBs,
                            'userapi_general_settingsData' => $api_general_settingsData,
                            'roomSetting' => $userRoomSettings
                        ]
                    );
                    Config::set('app.timezone', $userBs->timezoneinfo->timezone ?? '');

                    // Language logic usually depends on the USER'S preference or the TENANT'S defaults.
                    // For now, let's assume we keep the authenticated user's language preference context,
                    // but we might need to look up available languages from the TENANT.

                    // However, languages are often tied to the "site" (Tenant).
                    $userId = $settingsUserId;

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
                    $keywords = json_decode($lang->keywords ?? '{}', true);
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

                // $social_media = $user->social_media()->get() ?? collect([]);
                // For SEO, Languages, etc., we generally want the TENANT's data if we are in a tenant context.
                // However, 'user-front' usually implies the PUBLIC facing site of a tenant.
                // In that case, $user (from getUser()) is usually the Tenant.
                // But let's verify if $user can be an employee here.
                // getUser() likely retrieves the owner of the domain/username.
                // IF it returns an employee, we should map to tenant.

                $settingsUserId = $user->tenantOwnerId();

                $userSeo = SEO::where('language_id', $userCurrentLang->id)->where('user_id', $settingsUserId)->first();
                $userLangs = UserLanguage::where('user_id', $settingsUserId)->get();
                $userShopSetting = UserShopSetting::where('user_id', $settingsUserId)->first();

                // $packagePermissions = UserPermissionHelper::packagePermission($user->id);
                // $packagePermissions = json_decode($packagePermissions, true);
                if ($user && $user->id) {
                    $packagePermissions = UserPermissionHelper::packagePermission($settingsUserId);
                    $packagePermissions = json_decode($packagePermissions, true);
                } else {
                    $packagePermissions = [];
                }


                $footerData = FooterText::where('language_id', $userCurrentLang->id)
                    ->where('user_id', $settingsUserId)
                    ->first();
                $api_footerData = FooterSetting::where('user_id', $settingsUserId)->first();
                $api_general_settingsData = GeneralSetting::where('user_id', $settingsUserId)->first();
                // api_pixelsData
                $userApi_pixelsData = ApiPixel::where('user_id', $settingsUserId)->get();
                $view->with('userApi_pixelsData', $userApi_pixelsData);

                if ($userBs && $userBs->theme == 'home_seven') {
                    $fservices = UserService::where('lang_id', $userCurrentLang->id)
                        ->where('user_id', $settingsUserId)
                        ->get();
                    $view->with('fservices', $fservices);
                }

                if ($userBs && $userBs->theme == 'home_eight') {
                    $categories = UserItemCategory::query()
                        ->where('user_id', $settingsUserId)
                        ->where('language_id', $userCurrentLang->id)
                        ->with('subcategories')
                        ->where('status', 1)
                        ->get();
                    $view->with('categories', $categories);
                }


                $footerQuickLinks = FooterQuickLink::where('language_id', $userCurrentLang->id)
                    ->where('user_id', $settingsUserId)
                    ->orderBy('serial_number', 'asc')
                    ->get();
                $cookieAlert = BasicSetting::where('user_id', $settingsUserId)
                    // ->where('language_id', $userCurrentLang->id)
                    ->select('cookie_alert_status', 'cookie_alert_text', 'cookie_alert_button_text')
                    ->first();
                $footerRecentBlogs = User\Blog::query()
                    ->where('user_id', $settingsUserId)
                    ->where('language_id', $userCurrentLang->id)
                    ->orderBy('id', 'DESC')
                    ->limit(3)
                    ->get();
                $userContact = UserContact::where([
                    ['user_id', $settingsUserId],
                    ['language_id', $userCurrentLang->id]
                ])->first();

                $home_text = User\HomePageText::query()
                    ->where([
                        ['user_id', $settingsUserId],
                        ['language_id', $userCurrentLang->id]
                    ])->first();
                $home_sections = User\HomeSection::where('user_id', $settingsUserId)->first();

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

            View::composer(['admin.layout', 'admin.partials.top-navbar', 'admin.partials.side-navbar', 'admin.partials.styles'], function ($view) {
                $view->with('adminLanguages', \App\Models\Language::orderBy('is_default', 'desc')->get());
                $locale = app()->getLocale();
                $lang = \App\Models\Language::where('code', $locale)->first();
                // RTL when language has rtl=1 or when locale is Arabic (admin is Arabic-first)
                $view->with('admin_rtl', ($lang && (int) $lang->rtl === 1) || $locale === 'ar');
            });

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
                // Use tenantOwnerId() to get the correct User ID (Tenant or Employee's Tenant)
                $settingsUserId = $user->tenantOwnerId();
                $steps = UserStep::firstOrCreate(['user_id' => $settingsUserId]);

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
                // If $user is an employee, use the Tenant ID
                $settingsUserId = $user->tenantOwnerId();
                $dropdownSettings = CustomerDropdownSetting::where('user_id', $settingsUserId)->first();

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
            UserApiCustomerReminder::observe(UserApiCustomerReminderObserver::class);
        } catch (\Throwable $e) {
            Log::warning('Failed to register matching observers', ['error' => $e->getMessage()]);
        }

        // OPTIMIZED: Enhanced query performance logging and cache hit rate tracking
        // Logs slow queries (>100ms) in all environments for monitoring
        // In production, uses info level; in development, uses warning level
        $logLevel = app()->environment('production') ? 'info' : 'warning';
        $slowQueryThreshold = (int) config('app.slow_query_threshold', 100); // Configurable threshold

        DB::listen(function ($query) use ($logLevel, $slowQueryThreshold) {
            if ($query->time > $slowQueryThreshold) {
                $logData = [
                    'time' => $query->time . 'ms',
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'connection' => $query->connectionName ?? 'default',
                ];

                // In production, don't log full SQL for security, just summary
                if (app()->environment('production')) {
                    // Extract table name and operation type for production logging
                    $sql = $query->sql;
                    $operation = 'unknown';
                    $table = 'unknown';

                    if (preg_match('/^\s*(SELECT|INSERT|UPDATE|DELETE)\s+/i', $sql, $matches)) {
                        $operation = strtoupper(trim($matches[1]));
                    }
                    if (preg_match('/FROM\s+`?(\w+)`?/i', $sql, $matches)) {
                        $table = $matches[1];
                    } elseif (preg_match('/INTO\s+`?(\w+)`?/i', $sql, $matches)) {
                        $table = $matches[1];
                    } elseif (preg_match('/UPDATE\s+`?(\w+)`?/i', $sql, $matches)) {
                        $table = $matches[1];
                    }

                    $logData = [
                        'time' => $query->time . 'ms',
                        'operation' => $operation,
                        'table' => $table,
                        'connection' => $query->connectionName ?? 'default',
                    ];
                }

                if ($logLevel === 'info') {
                    Log::info('Slow Query Detected', $logData);
                } else {
                    Log::warning('Slow Query Detected', $logData);
                }
            }
        });

        // Track cache hit rates for important caches (property filter options)
        // Monitor cache effectiveness by logging cache misses
        // NOTE: Counter tracking disabled for file driver to prevent disk space leak
        $originalRemember = Cache::getStore();
        Cache::macro('rememberWithTracking', function ($key, $ttl, $callback) {
            $store = Cache::getStore();
            $isFileDriver = $store instanceof \Illuminate\Cache\FileStore;

            if (Cache::has($key)) {
                // Cache hit - increment hit counter (only if not file driver)
                if (!$isFileDriver) {
                    $hitKey = "cache_stats_hit_{$key}";
                    $currentValue = Cache::increment($hitKey, 1);
                    // Set TTL on counter key to prevent indefinite growth (7 days)
                    Cache::put($hitKey, $currentValue, now()->addDays(7));
                }
                return Cache::get($key);
            } else {
                // Cache miss - increment miss counter and log (only if not file driver)
                if (!$isFileDriver) {
                    $missKey = "cache_stats_miss_{$key}";
                    $currentValue = Cache::increment($missKey, 1);
                    // Set TTL on counter key to prevent indefinite growth (7 days)
                    Cache::put($missKey, $currentValue, now()->addDays(7));
                }

                // Log cache miss for important caches
                if (strpos($key, 'property_filter_options') !== false) {
                    Log::info('Property filter options cache miss', [
                        'cache_key' => $key,
                        'ttl' => $ttl
                    ]);
                }

                $value = $callback();
                Cache::put($key, $value, $ttl);
                return $value;
            }
        });
    }
}
