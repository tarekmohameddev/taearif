<?php

namespace App\Http\Controllers\Front;

use Validator;
use Carbon\Carbon;
use App\Models\Faq;
use App\Models\Seo;
use App\Models\Blog;
use App\Models\Page;
use App\Models\User;
use App\Models\Feature;
use App\Models\Package;
use App\Models\Partner;
use App\Models\Process;
use App\Models\Visitor;
use App\Models\Language;
use App\Models\Bcategory;
use App\Models\Subscriber;
use App\Models\User\Quote;
use App\Models\Testimonial;
use Illuminate\Http\Request;
use App\Models\BasicExtended;
use App\Models\User\UserCity;
use App\Models\OfflineGateway;
use App\Models\PaymentGateway;
use App\Models\User\UserVcard;
use App\Models\User\HeroSlider;
use App\Models\User\QuoteInput;
use Illuminate\Validation\Rule;
use App\Http\Helpers\MegaMailer;
use App\Models\User\UserContact;
use App\Models\User\UserFeature;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use App\Models\User\UserDistrict;
use JeroenDesloovere\VCard\VCard;
use App\Models\Api\ApiMenuSetting;
use App\Models\Api\GeneralSetting;
use App\Models\BasicSetting as BS;
use App\Traits\MiscellaneousTrait;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Models\BasicExtended as BE;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\Api\ApiAboutSettings;
use App\Models\Api\ApiBannerSetting;
use App\Models\User\UserOfferBanner;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use App\Models\User\CustomerWishList;
use App\Models\User\UserCustomDomain;
use App\Models\User\HotelBooking\Room;
use App\Models\User\PortfolioCategory;
use Illuminate\Support\Facades\Config;
use App\Models\User\CounterInformation;
use App\Services\PropertyFilterService;
use Illuminate\Support\Facades\Session;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\Api\ApiUserCategorySetting;
use App\Models\User\CourseManagement\Course;
use App\Models\User\HotelBooking\RoomContent;
use App\Models\User\Language as UserLanguage;
use App\Models\User\RealestateManagement\City;
use App\Models\User\DonationManagement\Donation;
use App\Models\User\RealestateManagement\Country;
use App\Models\User\RealestateManagement\Project;
use App\Models\User\RealestateManagement\Category;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\CourseManagement\CourseCategory;
use App\Models\User\CourseManagement\CourseEnrolment;
use App\Models\User\DonationManagement\DonationDetail;
use App\Models\User\RealestateManagement\ApiUserCategory;
use Illuminate\Database\Eloquent\Collection;
use App\Services\CategoryVisibility;

class FrontendController extends Controller
{
    use MiscellaneousTrait;
    public function __construct()
    {
        $bs = BS::first();
        $be = BE::first();

        Config::set('captcha.sitekey', $bs->google_recaptcha_site_key);
        Config::set('captcha.secret', $bs->google_recaptcha_secret_key);
        Config::set('mail.host', $be->smtp_host);
        Config::set('mail.port', $be->smtp_port);
        Config::set('mail.username', $be->smtp_username);
        Config::set('mail.password', $be->smtp_password);
        Config::set('mail.encryption', $be->encryption);
        Config::set('mail.encryption', $be->encryption);
    }
    public function index()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;
        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;

        $data['processes'] = Process::where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['features'] = Feature::where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['featured_users'] = User::where([
            ['featured', 1],
            ['status', 1],
        ])
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })->get();

        $data['templates'] = User::where([
            ['preview_template', 1],
            ['show_home', 1],
            ['status', 1],
            ['online_status', 1],
        ])
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })->orderBy('template_serial_number', 'ASC')->get();

        $data['testimonials'] = Testimonial::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();
        $data['blogs'] = Blog::where('language_id', $lang_id)->orderBy('id', 'DESC')->take(3)->get();

        $data['packages'] = Package::query()->where('status', '1')->where('featured', '1')->get();
        $data['partners'] = Partner::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();

        $data['seo'] = Seo::where('language_id', $lang_id)->first();

        $terms = [];
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'monthly')->count() > 0) {
            $terms[] = 'Monthly';
        }
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'yearly')->count() > 0) {
            $terms[] = 'Yearly';
        }
        if (Package::query()->where('status', '1')->where('featured', '1')->where('term', 'lifetime')->count() > 0) {
            $terms[] = 'Lifetime';
        }
        $data['terms'] = $terms;

        $be = BasicExtended::select('package_features')->firstOrFail();
        $allPfeatures = $be->package_features ? $be->package_features : "[]";
        $data['allPfeatures'] = json_decode($allPfeatures, true);

        $data['vcards'] = UserVcard::where('preview_template', 1)->where([['status', 1], ['show_in_home', 1]])->orderBy('template_serial_number', 'ASC')->take(3)->get();

        return view('front.index', $data);
    }

    public function realestate()
    {
        return view('front.realestate');
    }

    public function subscribe(Request $request)
    {
        $rules = [
            'email' => 'required|email|unique:subscribers',
        ];


        $bs = BS::first();

        if ($bs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        $messages = [];
        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            $errors = $validator->getMessageBag()->toArray();
            $errrors['errors'] = $errors;
            return response()->json($errrors);
        }

        $subsc = new Subscriber;
        $subsc->email = $request->email;
        $subsc->save();

        return "success";
    }

    public function loginView()
    {

        return view('front.login');
    }

    public function checkUsername($username)
    {
        $count = User::where('username', $username)->count();
        $status = $count > 0 ? true : false;
        return response()->json($status);
    }

    public function step1($status, $id)
    {

        Session::forget('coupon');
        Session::forget('coupon_amount');

        if (Auth::check()) {
            return redirect()->route('user.plan.extend.index');
        }
        $data['status'] = $status;
        $data['id'] = $id;
        $package = Package::findOrFail($id);
        $data['package'] = $package;

        $hasSubdomain = false;
        $features = [];
        if (!empty($package->features)) {
            $features = json_decode($package->features, true);
        }
        if (is_array($features) && in_array('Subdomain', $features)) {
            $hasSubdomain = true;
        }

        $data['hasSubdomain'] = $hasSubdomain;

        return view('front.step', $data);
    }

    public function step2(Request $request)
    {
        $data = $request->session()->get('data');

        if (session()->has('coupon_amount')) {
            $data['cAmount'] = session()->get('coupon_amount');
        } else {
            $data['cAmount'] = 0;
        }

        $stripe = PaymentGateway::where('keyword', 'stripe')->where('status', 1)->first();

        if (is_null($stripe)) {
            $data['stripe_key'] = null;
        } else {
            $stripe_info = json_decode($stripe->information, true);
            $data['stripe_key'] = $stripe_info['key'];
        }
        return view('front.checkout', $data);
    }

    public function checkout(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $bs = $currentLang->basic_setting;

        $this->validate($request, [
            'username' => 'required|alpha_num|unique:users',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
            'phone' => 'required|min:8',
            'g-recaptcha-response' => Rule::requiredIf(function () use ($bs) {
                if ($bs->is_recaptcha == 1) {
                    return true;
                }
            }),
        ], [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
        ]);
        $seo = Seo::where('language_id', $currentLang->id)->first();
        $be = $currentLang->basic_extended;
        $data['bex'] = $be;
        $data['username'] = $request->username;
        $data['email'] = $request->email;
        $data['password'] = $request->password;
        $data['phone'] = $request->phone;
        $data['status'] = $request->status;
        $data['id'] = $request->id;
        $online = PaymentGateway::query()->where('status', 1)->get();
        $offline = OfflineGateway::where('status', 1)->get();
        $data['offline'] = $offline;
        $data['payment_methods'] = $online->merge($offline);
        $data['package'] = Package::query()->findOrFail($request->id);
        $data['seo'] = $seo;
        $request->session()->put('data', $data);
        return redirect()->route('front.registration.step2');
    }


    // packages start
    public function pricing(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $data['bex'] = BE::first();
        $data['abs'] = BS::first();

        $terms = [];
        if (Package::query()->where('status', '1')->where('term', 'monthly')->count() > 0) {
            $terms[] = 'Monthly';
        }
        if (Package::query()->where('status', '1')->where('term', 'yearly')->count() > 0) {
            $terms[] = 'Yearly';
        }
        if (Package::query()->where('status', '1')->where('term', 'lifetime')->count() > 0) {
            $terms[] = 'Lifetime';
        }
        $data['terms'] = $terms;

        $be = BasicExtended::select('package_features')->firstOrFail();
        $allPfeatures = $be->package_features ? $be->package_features : "[]";
        $data['allPfeatures'] = json_decode($allPfeatures, true);

        return view('front.pricing', $data);
    }

    // blog section start
    public function blogs(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $data['currentLang'] = $currentLang;

        $lang_id = $currentLang->id;

        $category = $request->category;
        if (!empty($category)) {
            $data['category'] = Bcategory::findOrFail($category);
        }
        $term = $request->term;




        $data['blogs'] = Blog::when($category, function ($query, $category) {
            return $query->where('bcategory_id', $category);
        })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'like', '%' . $term . '%');
            })
            ->when($currentLang, function ($query, $currentLang) {
                return $query->where('language_id', $currentLang->id);
            })->orderBy('serial_number', 'ASC')->paginate(6);
        return view('front.blogs', $data);
    }

    public function blogdetails($slug, $id)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }

        $lang_id = $currentLang->id;


        $data['blog'] = Blog::findOrFail($id);
        $data['bcats'] = Bcategory::where('status', 1)->where('language_id', $lang_id)->orderBy('serial_number', 'ASC')->get();
        $data['recentBlogs'] = Blog::where('language_id', $currentLang->id)->take(3)->latest()->get();

        return view('front.blog-details', $data);
    }
    public function templates()
    {
        $data['templates'] = User::where([
            ['preview_template', 1],
            ['status', 1],
            ['online_status', 1],
        ])
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })->orderBy('template_serial_number', 'ASC')->paginate(9);
        return view('front.templates', $data);
    }
    public function vcards()
    {
        $data['vcards'] = UserVcard::where('preview_template', 1)->where('status', 1)->orderBy('template_serial_number', 'ASC')->paginate(12);
        return view('front.vcards', $data);
    }
    public function contactView()
    {

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        return view('front.contact', $data);
    }

    public function faqs()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $lang_id = $currentLang->id;
        $data['faqs'] = Faq::where('language_id', $lang_id)
            ->orderBy('serial_number', 'ASC')
            ->get();
        return view('front.faq', $data);
    }

    public function dynamicPage($slug)
    {
        $data['page'] = Page::where('slug', $slug)->firstOrFail();

        return view('front.dynamic', $data);
    }

    public function users(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['seo'] = Seo::where('language_id', $currentLang->id)->first();

        $homeTexts = [];
        if (!empty($request->designation)) {
            $homeTexts = HomePageText::when($request->designation, function ($q) use ($request) {
                return $q->where('designation', 'like', '%' . $request->designation . '%');
            })->select('user_id')->get();
        }

        $userIds = [];

        foreach ($homeTexts as $key => $homeText) {
            if (!in_array($homeText->user_id, $userIds)) {
                $userIds[] = $homeText->user_id;
            }
        }
        $data['users'] = null;
        $users = User::where('online_status', 1)
            ->whereHas('memberships', function ($q) {
                $q->where('status', '=', 1)
                    ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
                    ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'));
            })
            ->when($request->company, function ($q) use ($request) {
                return $q->where('company_name', 'like', '%' . $request->company . '%');
            })
            ->when($request->location, function ($q) use ($request) {
                return $q->where(function ($query) use ($request) {
                    $query->where('city', 'like', '%' . $request->location . '%')
                        ->orWhere('state', 'like', '%' . $request->location . '%')
                        ->orWhere('address', 'like', '%' . $request->location . '%')
                        ->orWhere('country', 'like', '%' . $request->location . '%');
                });
            })
            ->orderBy('id', 'DESC')
            ->paginate(9);

        $data['users'] = $users;
        return view('front.users', $data);
    }

    public function userDetailView(Request $request,$domain , PropertyFilterService $propertyFilterService,CategoryVisibility $visibility)
    {
        $user = getUser();

        $data['user'] = $user;
        if (Auth::check() && Auth::user()->id != $user->id && $user->online_status != 1) {
            return redirect()->route('front.index');
        } elseif (!Auth::check() && $user->online_status != 1) {
            return redirect()->route('front.index');
        }
        $package = UserPermissionHelper::userPackage($user->id);
        if (is_null($package)) {
            Session::flash('warning', 'User membership is expired');
            if (Auth::check()) {
                return redirect()->route('user-dashboard')->with('error', 'User membership is expired');
            } else {
                return redirect()->route('front.user.view');
            }
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
        $userBs = \App\Models\User\BasicSetting::where('user_id', $user->id)->first();

        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $lang_id = $currentLang->id;
        $bs = $currentLang->basic_setting;
        $be = $currentLang->basic_extended;

        //
        $api_Banner_settingsData = ApiBannerSetting::where('user_id', $user->id)->first();
        $api_general_settingsData = GeneralSetting::where('user_id', $user->id)->first();
        $api_about_settingsData = ApiAboutSettings::where('user_id', $user->id)->first();
        $api_menu_settingsData = ApiMenuSetting::where('user_id', $user->id)->first();

        if ($api_Banner_settingsData && is_string($api_Banner_settingsData)) {
            $api_Banner_settingsData = json_decode($api_Banner_settingsData);
        }

        $data['api_Banner_settingsData'] = $api_Banner_settingsData;
        $data['api_general_settingsData'] = $api_general_settingsData;
        $data['api_about_settingsData'] = $api_about_settingsData;
        $data['api_menu_settingsData'] = $api_menu_settingsData;

        $data['home_sections'] = User\HomeSection::where('user_id', $user->id)->first();

        $tenantId = getUser()->id;


        // Fetch categories with user-specific visibility settings (only active in api_user_category_settings)
        $visibleCategoriesQuery = \App\Models\User\RealestateManagement\ApiUserCategory::join('api_user_category_settings', function ($join) use ($tenantId) {
            $join->on('api_user_categories.id', '=', 'api_user_category_settings.category_id')
                 ->where('api_user_category_settings.user_id', '=', $tenantId)
                 ->where('api_user_category_settings.is_active', 1);
        })
        ->select('api_user_categories.*');


        // Categories for filtering and display
        $showAll = $user->show_even_if_empty;

        $data['visibleCategories'] = $visibility->forTenant(
            $user->id,$request,(bool) $user->show_even_if_empty
        );


        // $data['property_contents'] = $property_contents->paginate(8);
        $propertyQuery = $propertyFilterService->buildQuery($tenantId, $request, $userCurrentLang);
        $data['property_contents'] = $propertyQuery->paginate(12);

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->first();
        $data['portfolios'] = $user->portfolios()
            ->where('language_id', $userCurrentLang->id)
            ->where('featured', 1)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['portfolio_categories'] = $user->portfolioCategories()
            ->whereHas('portfolios', function ($q) {
                $q->where('featured', 1);
            })
            ->where('language_id', $userCurrentLang->id)
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['skills'] = $user->skills()
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['counterInformations'] = $user->counterInformations()
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['services'] = $user->services()->where([
            ['lang_id', $userCurrentLang->id],
            ['featured', 1],
        ])
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $data['testimonials'] = $user->testimonials()
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get() ?? collect([]);
        $blogLimits = 3;
        $userdefaultLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        $data['videoSectionDetails'] =
        User\HomePageText::query()
            ->where([
                ['user_id', $user->id],
                ['language_id', $userdefaultLang->id],
            ])->select(['video_section_image', 'video_section_title', 'video_section_subtitle', 'video_section_button_url', 'video_section_button_text', 'video_section_url', 'video_section_text'])->first();

        // resources\views\user-front\maintenance_mode.blade.php
        // maintenance_mode

        if (isset($api_general_settingsData->maintenance_mode) && $api_general_settingsData->maintenance_mode == 1) {
            return view('user-front.maintenance_mode', $data);
        }



        if ($userBs->theme == 'home_one') {
            $blogLimits = 3;
        } elseif ($userBs->theme == 'home_four' || $userBs->theme == 'home_five' || $userBs->theme == 'home_seven') {
            $blogLimits = 3;
        } elseif ($userBs->theme == 'home_six' || $userBs->theme == 'home_eleven') {
            $blogLimits = 4;
        } elseif ($userBs->theme == 'home_two' || $userBs->theme == 'home_three') {
            $blogLimits = 6;
        }
        //  CHECK permissions
        if (!empty($user)) {
            $permissions = UserPermissionHelper::packagePermission($user->id);
            $permissions = json_decode($permissions, true);
        }

        $data['blogs'] = $user->blogs()
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('id', 'DESC')
            ->take($blogLimits)
            ->get() ?? collect([]);
        $data['teams'] = $user->teams()
            ->where('language_id', $userCurrentLang->id)
            ->where('featured', 1)
            ->get() ?? collect([]);
        $data['brands'] = $user->brands()
            ->get() ?? collect([]);
        $data['sliders'] = HeroSlider::where('language_id', $userCurrentLang->id)
            ->where('user_id', $user->id)
            ->orderBy('serial_number', 'asc')
            ->get();

        if ($userBs->theme == 'home_two') {
            $data['work_processes'] = User\WorkProcess::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->orderBy('serial_number', 'ASC')->get();
            return view('user-front.home-page.home-two', $data);
        } elseif ($userBs->theme == 'home_three') {
            $data['work_processes'] = User\WorkProcess::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->orderBy('serial_number', 'ASC')->get();
            $data['contact'] = UserContact::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->first();
            $data['faqs'] = User\FAQ::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('featured', 1)
                ->get();
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            return view('user-front.home-page.home-three', $data);
        } elseif ($userBs->theme == 'home_four') {

            $data['work_processes'] = User\WorkProcess::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->orderBy('serial_number', 'ASC')->get();
            $data['contact'] = UserContact::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->first();
            $data['faqs'] = User\FAQ::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('featured', 1)
                ->get();
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            $data['portfolioCategories'] = PortfolioCategory::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)->where('is_featured', 1)->get();
            return view('user-front.home-page.home-four', $data);
        } elseif ($userBs->theme == 'home_five') {

            $data['work_processes'] = User\WorkProcess::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->orderBy('serial_number', 'ASC')->get();
            $data['contact'] = UserContact::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->first();
            $data['faqs'] = User\FAQ::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('featured', 1)
                ->get();
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            $data['portfolioCategories'] = PortfolioCategory::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)->where('is_featured', 1)->get();
            return view('user-front.home-page.home-five', $data);
        } elseif ($userBs->theme == 'home_six') {
            $data['work_processes'] = User\WorkProcess::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->orderBy('serial_number', 'ASC')->get();
            $data['contact'] = UserContact::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->first();
            $data['faqs'] = User\FAQ::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('featured', 1)
                ->get();
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            return view('user-front.home-page.home-six', $data);
        } elseif ($userBs->theme == 'home_seven') {
            $data['contact'] = UserContact::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->first();
            $data['work_processes'] = User\WorkProcess::where([
                ['user_id', $user->id],
                ['language_id', $userCurrentLang->id],
            ])->orderBy('serial_number', 'ASC')->get();
            $data['faqs'] = User\FAQ::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('featured', 1)
                ->get();
            return view('user-front.home-page.home-seven', $data);
        } elseif ($userBs->theme == 'home_eight') {
            if (!empty($permissions) && !in_array('Ecommerce', $permissions)) {
                $userBs->theme = 'home_one';
                $userBs->save();
                return redirect()->route('front.user.view');
            }
            $data['sliders'] = HeroSlider::where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->where('user_id', $user->id)
                ->get();
            $data['features'] = UserFeature::where('language_id', $userCurrentLang->id)
                ->where('user_id', $user->id)
                ->orderBy('serial_number', 'ASC')
                ->get();
            $data['topbanners'] = UserOfferBanner::where('language_id', $userCurrentLang->id)
                ->where('user_id', $user->id)
                ->where('position', 'top')
                ->orderBy('id', 'DESC')
                ->get();
            $data['bottombanners'] = UserOfferBanner::where('language_id', $userCurrentLang->id)
                ->where('user_id', $user->id)
                ->where('position', 'bottom')
                ->orderBy('id', 'DESC')
                ->get();
            $data['leftbanners'] = UserOfferBanner::where('language_id', $userCurrentLang->id)
                ->where('user_id', $user->id)
                ->where('position', 'left')
                ->orderBy('id', 'DESC')
                ->get();

            $itemlimit = 10;
            $data['featured_items'] = DB::table('user_items')->where('user_items.user_id', $user->id)->where('user_items.status', 1)
                ->where('user_items.is_feature', 1)
                ->Join('user_item_contents', 'user_items.id', '=', 'user_item_contents.item_id')
                ->join('user_item_categories', 'user_item_contents.category_id', '=', 'user_item_categories.id')
                ->select('user_items.*', 'user_items.id AS item_id', 'user_item_contents.*', 'user_item_categories.name AS category')
                ->orderBy('user_items.id', 'DESC')
                ->where('user_item_contents.language_id', '=', $userCurrentLang->id)
                ->where('user_item_categories.language_id', '=', $userCurrentLang->id)
                ->limit($itemlimit)
                ->get();
            $data['new_items'] = DB::table('user_items')->where('user_items.status', 1)->where('user_items.user_id', $user->id)
                ->Join('user_item_contents', 'user_items.id', '=', 'user_item_contents.item_id')
                ->join('user_item_categories', 'user_item_contents.category_id', '=', 'user_item_categories.id')
                ->select('user_items.*', 'user_items.id AS item_id', 'user_item_contents.*', 'user_item_categories.name AS category')
                ->orderBy('user_items.id', 'DESC')
                ->where('user_item_contents.language_id', '=', $userCurrentLang->id)
                ->where('user_item_categories.language_id', '=', $userCurrentLang->id)
                ->limit($itemlimit)
                ->get();

            $data['rating_items'] = DB::table('user_items')->where('user_items.rating', '>', 0)->where('user_items.status', 1)->where('user_items.user_id', $user->id)
                ->Join('user_item_contents', 'user_items.id', '=', 'user_item_contents.item_id')
                ->join('user_item_categories', 'user_item_contents.category_id', '=', 'user_item_categories.id')
                ->select('user_items.*', 'user_items.id AS item_id', 'user_item_contents.*', 'user_item_categories.name AS category')
                ->orderBy('user_items.rating', 'DESC')
                ->where('user_item_contents.language_id', '=', $userCurrentLang->id)
                ->where('user_item_categories.language_id', '=', $userCurrentLang->id)
                ->limit($itemlimit)
                ->get();
            $data['special_offer_items'] = DB::table('user_items')->where('user_items.status', 1)->where('user_items.user_id', $user->id)
                ->where('user_items.special_offer', 1)
                ->Join('user_item_contents', 'user_items.id', '=', 'user_item_contents.item_id')
                ->join('user_item_categories', 'user_item_contents.category_id', '=', 'user_item_categories.id')
                ->select('user_items.*', 'user_items.id AS item_id', 'user_item_contents.*', 'user_item_categories.name AS category')
                ->orderBy('user_items.id', 'DESC')
                ->where('user_item_contents.language_id', '=', $userCurrentLang->id)
                ->where('user_item_categories.language_id', '=', $userCurrentLang->id)
                ->get();
            $data['best_seller_items'] = DB::table('user_order_items')
                ->leftJoin('user_items', 'user_items.id', '=', 'user_order_items.item_id')
                ->leftJoin('user_orders', 'user_orders.id', '=', 'user_order_items.user_order_id')
                ->Join('user_item_contents', 'user_items.id', '=', 'user_item_contents.item_id')
                ->join('user_item_categories', 'user_order_items.category', '=', 'user_item_categories.id')
                ->select(
                    'user_items.id',
                    'user_items.current_price',
                    'user_orders.payment_status',
                    'user_items.rating',
                    'user_items.previous_price',
                    'user_item_contents.title',
                    'user_item_contents.slug',
                    'user_item_categories.name AS category',
                    'user_item_categories.id AS category_id',
                    'user_items.thumbnail',
                    'user_items.flash',
                    'user_items.stock',
                    'user_items.type',
                    'user_items.start_date',
                    'user_items.end_date',
                    'user_items.start_time',
                    'user_items.end_time',
                    'user_items.flash_percentage',
                    'user_order_items.item_id',
                    DB::raw('SUM(user_order_items.qty) as total')
                )
                ->groupBy('user_items.id', 'user_orders.payment_status', 'user_items.flash', 'user_items.stock', 'user_items.type', 'user_items.start_date', 'user_items.start_time', 'user_items.end_time', 'user_items.end_date', 'user_items.flash_percentage', 'user_items.rating', 'user_items.current_price', 'user_item_categories.id', 'user_item_categories.name', 'user_item_contents.title', 'user_item_contents.slug', 'user_items.previous_price', 'user_items.thumbnail', 'user_order_items.item_id')
                ->where('user_orders.payment_status', '=', 'Completed')
                ->where('user_item_contents.language_id', '=', $userCurrentLang->id)
                ->where('user_item_categories.language_id', '=', $userCurrentLang->id)
                ->orderBy('total', 'desc')
                ->limit($itemlimit)
                ->get();
            $bs = BasicSetting::where('user_id', $user->id)->first();

            Config::set('app.timezone', $bs->timezoneinfo->timezone);
            $data['flash_items'] = DB::table('user_items')->where('user_items.status', 1)->where('user_items.user_id', $user->id)
                ->where('user_items.flash', 1)
                ->where('user_items.start_date_time', '<=', Carbon::now()->tz($bs->timezoneinfo->timezone)->format('Y-m-d H:i:s A'))
                ->where('user_items.end_date_time', '>=', Carbon::now()->tz($bs->timezoneinfo->timezone)->format('Y-m-d H:i:s A'))
                ->Join('user_item_contents', 'user_items.id', '=', 'user_item_contents.item_id')
                ->join('user_item_categories', 'user_item_contents.category_id', '=', 'user_item_categories.id')
                ->select('user_items.*', 'user_items.id AS item_id', 'user_item_contents.*', 'user_item_categories.name AS category')
                ->orderBy('user_items.id', 'DESC')
                ->where('user_item_contents.language_id', '=', $userCurrentLang->id)
                ->where('user_item_categories.language_id', '=', $userCurrentLang->id)
                ->get();

            if (Auth::guard('customer')->check()) {
                $data['myWishlist'] = CustomerWishList::where('customer_id', Auth::guard('customer')->user()->id)->pluck('item_id')->toArray();
            } else {
                $data['myWishlist'] = [];
            }
            return view('user-front.home-page.home-eight', $data);
        } elseif ($userBs->theme == 'home_nine') {
            if (!empty($permissions) && !in_array('Hotel Booking', $permissions)) {
                $userBs->theme = 'home_one';
                $userBs->save();
                return redirect()->route('front.user.view');
            }

            $data['sliders'] = HeroSlider::where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->where('user_id', $user->id)
                ->get();
            $data['rooms'] = RoomContent::where('user_id', $user->id)->with(['room' => function ($query) {
                $query->where('status', 1)->where('is_featured', 1);
            }])->where('language_id', $userCurrentLang->id)
                ->get();
            $data['chooseUsItems'] = DB::table('user_choose_us_items')->where('user_id', $user->id)->where('language_id', $userCurrentLang->id)->orderBy('serial_number')->get();

            $data['numOfBed'] = Room::where([['status', 1], ['user_id', $user->id]])->max('bed');

            $data['numOfBath'] = Room::where([['status', 1], ['user_id', $user->id]])->max('bath');

            $data['numOfGuest'] = Room::where([['status', 1, ['user_id', $user->id]]])->max('max_guests');

            return view('user-front.home-page.home-nine', $data);
        } elseif ($userBs->theme == 'home_ten') {
            if (!empty($permissions) && !in_array('Course Management', $permissions)) {
                $userBs->theme = 'home_one';
                $userBs->save();
                return redirect()->route('front.user.view');
            }
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            $data['categories'] = CourseCategory::query()->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('status', 1)
                ->orderBy('serial_number', 'ASC')
                ->get();

            $data['callToActionInfo'] = User\ActionSection::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            $courses = Course::query()
                ->join('user_course_informations', 'user_courses.id', '=', 'user_course_informations.course_id')
                ->join('user_course_categories', 'user_course_categories.id', '=', 'user_course_informations.course_category_id')
                ->join('user_course_instructors', 'user_course_instructors.id', '=', 'user_course_informations.instructor_id')
                ->where('user_courses.status', '=', 'published')
                ->where('user_courses.user_id', '=', $user->id)
                ->where('user_courses.is_featured', '=', 'yes')
                ->where('user_course_informations.language_id', '=', $userCurrentLang->id)
                ->select('user_courses.id', 'user_courses.thumbnail_image', 'user_courses.pricing_type', 'user_courses.previous_price', 'user_courses.current_price', 'user_courses.average_rating', 'user_courses.duration', 'user_course_informations.title', 'user_course_informations.slug', 'user_course_categories.name as categoryName', 'user_course_instructors.image as instructorImage', 'user_course_instructors.name as instructorName')
                ->orderByDesc('user_courses.id')
                ->get();

            $courses->map(function ($course) use ($user) {
                $course['enrolmentCount'] = CourseEnrolment::query()
                    ->where('user_id', $user->id)
                    ->where('course_id', '=', $course->id)
                    ->where(function ($query) {
                        $query->where('payment_status', 'completed')
                            ->orWhere('payment_status', 'free');
                    })
                    ->count();
            });

            $data['courses'] = $courses;
            $data['categories'] = CourseCategory::query()->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->where('status', 1)
                ->orderBy('serial_number', 'ASC')
                ->where('is_featured', '=', 1)
                ->get();

            $data['featuredImage'] = $userBs->features_section_image;
            $data['features'] = UserFeature::query()->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get();
            $userdefaultLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
            $data['videoData'] = HomePageText::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userdefaultLang->id)
                ->select('video_section_image', 'video_section_url')
                ->first();
            $data['countInfos'] = CounterInformation::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get();
            $data['currencyInfo'] = MiscellaneousTrait::getCurrencyInfo($user->id);

            return view('user-front.home-page.home-ten', $data);
        } elseif ($userBs->theme == 'home_eleven') {
            if (!empty($permissions) && !in_array('Donation Management', $permissions)) {
                $userBs->theme = 'home_one';
                $userBs->save();
                return redirect()->route('front.user.view');
            }
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            $data['features'] = UserFeature::where('language_id', $userCurrentLang->id)
                ->where('user_id', $user->id)
                ->orderBy('serial_number', 'ASC')
                ->get();
            $data['countInfos'] = CounterInformation::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get();
            $data['donationCategories'] = $userCurrentLang->donationCategories->where('status', 1)->where('is_featured', 1);

            $causes = Donation::where('user_id', $user->id)->latest()->take(6)->get();

            $causes->map(function ($cause) use ($user, $userCurrentLang) {
                $title = $cause->contents->where('language_id', $userCurrentLang->id)->first();
                if (!empty($title)) {
                    $cause['title'] = $title->title;
                    $cause['slug'] = $title->slug;
                    $cause['iamge'] = $title->image;
                }
                $raised_amount = DonationDetail::query()
                    ->where('donation_id', '=', $cause->id)
                    ->where('status', '=', "completed")
                    ->sum('amount');
                $goal_percentage = $raised_amount > 0 ? (($raised_amount / $cause->goal_amount) * 100) : 0;
                $cause['raised_amount'] = $raised_amount > 0 ? round($raised_amount, 2) : 0;
                $cause['goal_percentage'] = round($goal_percentage, 1);
            });
            $data['causes'] = $causes;
            return view('user-front.home-page.home-eleven', $data);
        } elseif ($userBs->theme == 'home_twelve') {
            if (!empty($permissions) && !in_array('Portfolio', $permissions)) {
                $userBs->theme = 'home_one';
                $userBs->save();
                return redirect()->route('front.user.view');
            }
            $data['static'] = User\HeroStatic::where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->first();
            $data['countInfos'] = CounterInformation::query()
                ->where('user_id', $user->id)
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get();

            $data['job_experiences'] = $user->job_experiences()
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get() ?? collect([]);
            $data['educations'] = $user->educations()
                ->where('language_id', $userCurrentLang->id)
                ->orderBy('serial_number', 'ASC')
                ->get() ?? collect([]);

            return view('user-front.home-page.home_twelve', $data);
        } elseif ($userBs && $userBs->theme == 'home13' || $userBs->theme == 'home14' || $userBs->theme == 'home15') {

            $data['featured_properties'] = Property::where([['user_properties.status', 1], ['user_properties.featured', 1], ['user_properties.user_id', $user->id]])
                ->leftJoin('user_property_contents', 'user_property_contents.property_id', 'user_properties.id')

                ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
                ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
                ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')
                ->where('user_property_contents.language_id', $userCurrentLang->id)
                ->select(
                    'user_properties.*',
                    'user_property_contents.slug',
                    'user_property_contents.title',
                    'user_property_contents.address',
                    'user_property_contents.language_id',
                    'user_cities.name_ar as city_name',
                    'user_states.name as state_name',
                    'user_countries.name as country_name'
                )
                // ->inRandomOrder()
                ->orderBy('user_properties.reorder_featured', 'asc')
                ->take(10)
                ->get();

            $propertyQuery = Property::with([
                    'category',
                    'user',
                    'contents',
                    'proertyAmenities.amenity'
                ])
                ->leftJoin('user_property_contents', 'user_property_contents.property_id', '=', 'user_properties.id')
                ->leftJoin('user_cities', 'user_cities.id', '=', 'user_property_contents.city_id')
                ->leftJoin('user_states', 'user_states.id', '=', 'user_property_contents.state_id')
                ->leftJoin('user_countries', 'user_countries.id', '=', 'user_property_contents.country_id')
                ->where('user_properties.user_id', $user->id)
                ->where('user_properties.status', 1)
                ->where('user_properties.featured', 1)
                ->where('user_property_contents.language_id', $userCurrentLang->id);

                // Filters
                if ($request->filled('type') && $request->type !== 'all') {
                    $propertyQuery->where('user_properties.type', $request->type);
                }
                if ($request->filled('purpose') && $request->purpose !== 'all') {
                    $propertyQuery->where('user_properties.purpose', $request->purpose);
                }
                if ($request->filled('category') && $request->category !== 'all') {
                    $propertyQuery->where('user_properties.category_id', $request->category);
                }
                if ($request->filled('beds')) {
                    $propertyQuery->where('user_properties.beds', $request->beds);
                }
                if ($request->filled('baths')) {
                    $propertyQuery->where('user_properties.bath', $request->baths);
                }
                if ($request->filled('area')) {
                    $propertyQuery->where('user_properties.area', $request->area);
                }
                if ($request->filled('min')) {
                    $propertyQuery->where('user_properties.price', '>=', $request->min);
                }
                if ($request->filled('max')) {
                    $propertyQuery->where('user_properties.price', '<=', $request->max);
                }
                if ($request->filled('location')) {
                    $propertyQuery->where('user_property_contents.address', 'LIKE', '%' . $request->location . '%');
                }
                if ($request->filled('city')) {
                    $propertyQuery->where('user_property_contents.city_id', $request->city);
                }
                if ($request->filled('state_id')) {
                    $propertyQuery->where('user_property_contents.state_id', $request->state_id);
                }
                if ($request->filled('country')) {
                    $propertyQuery->where('user_property_contents.country_id', $request->country);
                }

                // Sorting
                if ($request->filled('sort')) {
                    switch ($request->sort) {
                        case 'new':
                            $propertyQuery->orderBy('user_properties.id', 'desc');
                            break;
                        case 'old':
                            $propertyQuery->orderBy('user_properties.id', 'asc');
                            break;
                        case 'low-to-high':
                            $propertyQuery->orderBy('user_properties.price', 'asc');
                            break;
                        case 'high-to-low':
                            $propertyQuery->orderBy('user_properties.price', 'desc');
                            break;
                    }
                } else {
                    $propertyQuery->orderByRaw('CASE WHEN user_properties.reorder_featured = 0 THEN 1 ELSE 0 END')
                    ->orderBy('user_properties.reorder_featured', 'asc')
                    ->orderBy('user_properties.reorder','asc')
                    ->orderBy('user_properties.updated_at','desc');
                }

                // Fetch
                $data['properties'] = $propertyQuery->select(
                    'user_properties.*',
                    'user_property_contents.slug',
                    'user_property_contents.title',
                    'user_property_contents.address',
                    'user_property_contents.language_id',
                    'user_cities.name_ar as city_name',
                    'user_states.name as state_name',
                    'user_countries.name as country_name',
                    'user_property_contents.city_id',
                    'user_property_contents.state_id'
                )->paginate(8);


            $cities = UserCity::limit(6)->get();
            $cities->map(function ($city) {
                $city['propertyCount'] = $city->propertyContent()->count();
            });

            $allCities = UserCity::all();
            $allCountries = UserDistrict::select('country_name_ar')->distinct()->get();
            $data['all_countries'] = $allCountries;

            $data['cities'] = $allCities;

            $data['all_cities'] = \App\Models\User\UserCity::whereHas('propertyContent', function ($query) use ($user) {
                $query->whereHas('property', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('status', 1);
                });
            })->get();


            $selectedCityId = request('city_id');
            if ($selectedCityId) {
                $allStates = \App\Models\User\UserDistrict::where('city_id', $selectedCityId)->select('id', 'name_ar')->get();
            } else {
                $allStates = \App\Models\User\UserDistrict::select('id', 'name_ar')->get();
            }

            $data['all_states'] = \App\Models\User\UserDistrict::whereHas('propertyContent', function ($query) use ($user) {
                $query->whereHas('property', function ($q) use ($user) {
                    $q->where('user_id', $user->id)->where('status', 1);
                });
            })->get();

            $data['all_proeprty_categories'] = ApiUserCategory::where([['is_active', 1]])->get();

            $min = Property::where([['status', 1], ['user_id', $user->id]])->min('price');
            $max = Property::where([['status', 1], ['user_id', $user->id]])->max('price');
            $data['min'] = intval($min);
            $data['max'] = intval($max);
            $data['website'] = $user->username;
            if ($userBs && $userBs->theme == 'home13') {
                $data['heroStatic'] = User\HeroStatic::where('user_id', $user->id)
                    ->where('language_id', $userCurrentLang->id)
                    ->first();

                $data['projects'] = Project::published()->where('user_projects.user_id', $user->id)
                    ->leftJoin('user_project_contents', 'user_project_contents.project_id', 'user_projects.id')
                    ->where('user_projects.featured', 1)
                    ->where('user_project_contents.language_id', $userCurrentLang->id)
                    ->select('user_projects.*', 'user_project_contents.slug', 'user_project_contents.title', 'user_project_contents.address')
                    ->inRandomOrder()
                    ->latest()
                    ->take(8)
                    ->get();

                $data['property_categories'] = ApiUserCategory::where([['is_active', 1]])->get();

            return view('user-front.realestate.home.index-v1', $data);

            } elseif ($userBs->theme == 'home14') {

                $data['sliderInfos'] = HeroSlider::where('language_id', $userCurrentLang->id)
                    ->orderBy('serial_number', 'ASC')
                    ->where('user_id', $user->id)
                    ->get();

                $data['callToActionInfo'] = User\ActionSection::query()
                    ->where('user_id', $user->id)
                    ->where('language_id', $userCurrentLang->id)
                    ->first();

                $data['work_processes'] = User\WorkProcess::where([
                    ['user_id', $user->id],
                    ['language_id', $userCurrentLang->id],
                ])->orderBy('serial_number', 'ASC')->get();

                $data['projects'] = Project::published()->where('user_projects.user_id', $user->id)
                    ->leftJoin('user_project_contents', 'user_project_contents.project_id', 'user_projects.id')
                    ->where('user_projects.featured', 1)
                    ->where('user_project_contents.language_id', $userCurrentLang->id)
                    ->select('user_projects.*', 'user_project_contents.slug', 'user_project_contents.title', 'user_project_contents.address')
                    ->inRandomOrder()
                    ->latest()
                    ->take(8)
                    ->get();

                    $data['property_categories'] = ApiUserCategory::where([['is_active', 1]])->get();

            return view('user-front.realestate.home.index-v2', $data);

            } elseif ($userBs->theme == 'home15') {
                $data['heroStatic'] = User\HeroStatic::where('user_id', $user->id)
                    ->where('language_id', $userCurrentLang->id)
                    ->first();

                $data['work_processes'] = User\WorkProcess::where([
                    ['user_id', $user->id],
                    ['language_id', $userCurrentLang->id],
                ])->orderBy('serial_number', 'ASC')->get();

                $data['projects'] = Project::published()->where('user_projects.user_id', $user->id)
                    ->leftJoin('user_project_contents', 'user_project_contents.project_id', 'user_projects.id')
                    ->where('user_projects.featured', 1)
                    ->where('user_project_contents.language_id', $userCurrentLang->id)
                    ->select('user_projects.*', 'user_project_contents.slug', 'user_project_contents.title', 'user_project_contents.address')
                    ->inRandomOrder()
                    ->latest()
                    ->take(8)
                    ->get();

                $data['property_categories'] = ApiUserCategory::where([['is_active', 1]])->get();

            return view('user-front.realestate.home.index-v3', $data);
            }

        } else {
            return view('user-front.home-page.home-one', $data);
        }
    }

    public function paymentInstruction(Request $request)
    {
        $offline = OfflineGateway::where('name', $request->name)
            ->select('short_description', 'instructions', 'is_receipt')
            ->first();
        return response()->json([
            'description' => $offline->short_description,
            'instructions' => $offline->instructions,
            'is_receipt' => $offline->is_receipt,
        ]);
    }

    public function contactMessage($domain, Request $request)
    {

        $rules = [
            'fullname' => 'required',
            'email' => 'required|email:rfc,dns',
            'subject' => 'required',
            'message' => 'required',
        ];

        $ubs = BasicSetting::where('user_id', getUser()->id)->first();
        $messages = [];
        if ($ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            ];
        }

        $request->validate($rules, $messages);

        $request->validate($rules);
        if (!empty($request->type) && $request->type == 'vcard') {
            $data['toMail'] = $request->to_mail;
            $data['toName'] = $request->to_name;
        } else {
            $toUser = User::query()->findOrFail($request->id);
            $data['toMail'] = $toUser->email;
            $data['toName'] = $toUser->username;
        }
        $data['subject'] = $request->subject;
        $data['fullname'] = $request->fullname;
        $data['email'] = $request->email;
        $data['body'] = "<div>$request->message</div><br>
                         <strong>For further contact with the enquirer please use the below information:</strong><br>
                         <strong>Enquirer Name:</strong> $request->fullname <br>
                         <strong>Enquirer Mail:</strong> $request->email <br>
                         ";

        $mailer = new MegaMailer();
        $mailer->mailContactMessage($data);
        Session::flash('success', 'Mail sent successfully');
        return back();
    }

    public function adminContactMessage(Request $request)
    {
        $rules = [
            'name' => 'required',
            'email' => 'required|email:rfc,dns',
            'subject' => 'required',
            'message' => 'required',
        ];

        $bs = BS::select('is_recaptcha')->first();

        if ($bs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        $messages = [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
        ];

        $request->validate($rules, $messages);

        $data['fromMail'] = $request->email;
        $data['fromName'] = $request->name;
        $data['subject'] = $request->subject;
        $data['body'] = "<div>$request->message</div><br>
        <strong>For further contact with the enquirer please use the below information:</strong><br>
        <strong>Enquirer Name:</strong> $request->name <br>
        <strong>Enquirer Mail:</strong> $request->email <br>
        ";
        $mailer = new MegaMailer();
        $mailer->mailToAdmin($data);
        Session::flash('success', 'Message sent successfully');
        return back();
    }

    public function userServices($domain)
    {
        $user = getUser();
        $id = $user->id;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id],
            ])->first();

        $data['services'] = User\UserService::query()
            ->where('user_id', $id)
            ->where('lang_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->get();
        return view('user-front.service.index', $data);
    }

    public function userServiceDetail($domain, $slug, $id)
    {
        $data['service'] = User\UserService::query()->findOrFail($id);
        return view('user-front.service.show', $data);
    }

    public function userBlogs(Request $request, $domain)
    {
        $user = getUser();
        $id = $user->id;
        $data['user'] = $user;
        $catid = $request->category;
        $term = $request->term;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id],
            ])->first();

        $data['blogs'] = User\Blog::query()
            ->when($catid, function ($query, $catid) {
                return $query->where('category_id', $catid);
            })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'LIKE', '%' . $term . '%');
            })
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->paginate(3);

        $data['blog_categories'] = User\BlogCategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $id)
            ->get();

        $data['allCount'] = User\Blog::query()
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->count();
        return view('user-front.blog.index', $data);
    }

    public function userBlogDetail($domain, $slug, $id)
    {
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['blog'] = User\Blog::query()->findOrFail($id);

        $data['latestBlogs'] = User\Blog::query()
            ->where('user_id', $userId)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('id', 'DESC')
            ->limit(3)->get();

        $data['blog_categories'] = User\BlogCategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $userId)
            ->get();

        $data['allCount'] = User\Blog::query()
            ->where('user_id', $userId)
            ->where('language_id', $userCurrentLang->id)
            ->count();
        return view('user-front.blog.show', $data);
    }

    public function userPortfolios(Request $request, $domain)
    {
        $user = getUser();
        $id = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id],
            ])->first();
        $data['portfolio_categories'] = User\PortfolioCategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $id)
            ->get();

        $data['catId'] = $request->category;

        $data['portfolios'] = User\Portfolio::query()
            ->where('user_id', $id)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->get();
        return view('user-front.portfolio.index', $data);
    }

    public function userPortfolioDetail($domain, $slug, $id)
    {
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $portfolio = User\Portfolio::query()->findOrFail($id);
        $catId = $portfolio->category_id;
        $data['relatedPortfolios'] = User\Portfolio::where('category_id', $catId)->where('id', '<>', $portfolio->id)->where('user_id', $userId)->orderBy('id', 'DESC')->limit(5);
        $data['portfolio'] = $portfolio;
        $data['portfolio_categories'] = User\PortfolioCategory::query()
            ->where('status', 1)
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $userId)
            ->orderBy('serial_number', 'ASC')
            ->get();
        $data['allCount'] = User\Portfolio::where('language_id', $userCurrentLang->id)->where('user_id', $userId)->count();
        return view('user-front.portfolio.show', $data);
    }

    public function userJobs(Request $request, $domain)
    {
        $user = getUser();
        $id = $user->id;
        $data['user'] = $user;
        $cat_id = $request->category;
        $term = $request->term;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id],
            ])->first();

        $data['jobs'] = User\Job::query()
            ->when($cat_id, function ($query, $cat_id) {
                return $query->where('jcategory_id', $cat_id);
            })
            ->when($term, function ($query, $term) {
                return $query->where('title', 'LIKE', '%' . $term . '%');
            })
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('serial_number', 'ASC')
            ->paginate(4);

        $data['job_categories'] = User\Jcategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $id)
            ->get();

        $data['allCount'] = User\Job::query()
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->count();
        return view('user-front.job.index', $data);
    }

    public function userJobDetail($domain, $slug, $id)
    {
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['job'] = User\Job::query()->findOrFail($id);

        $data['latestJobs'] = User\Job::query()
            ->where('user_id', $userId)
            ->where('language_id', $userCurrentLang->id)
            ->orderBy('id', 'DESC')
            ->limit(3)->get();

        $data['job_categories'] = User\Jcategory::query()
            ->where('status', 1)
            ->orderBy('serial_number', 'ASC')
            ->where('language_id', $userCurrentLang->id)
            ->where('user_id', $userId)
            ->get();

        $data['allCount'] = User\Job::query()
            ->where('user_id', $userId)
            ->where('language_id', $userCurrentLang->id)
            ->count();
        return view('user-front.job.show', $data);
    }

    public function userTeam($domain)
    {
        $user = getUser();
        $id = $user->id;

        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id],
            ])->first();

        $data['members'] = User\Member::query()
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->get();
        return view('user-front.team', $data);
    }

    public function userFaqs($domain)
    {
        $user = getUser();
        $id = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        $data['home_text'] = User\HomePageText::query()
            ->where([
                ['user_id', $id],
                ['language_id', $userCurrentLang->id],
            ])->first();

        $data['faqs'] = User\FAQ::query()
            ->where('user_id', $id)
            ->where('language_id', $userCurrentLang->id)
            ->get();
        return view('user-front.faqs', $data);
    }
    public function quote($domain)
    {
        $user = getUser();
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $bs = User\BasicSetting::where('user_id', $user->id)->first();

        if ($bs->is_quote == 0) {
            return view('errors.404');
        }

        $data['inputs'] = QuoteInput::where([
            ['language_id', $userCurrentLang->id],
            ['user_id', $user->id],
        ])->orderBy('order_number', 'ASC')->get();
        return view('user-front.quote', $data);
    }

    public function sendquote(Request $request, $domain)
    {
        $user = getUser();
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }
        app()->setLocale($userCurrentLang->code);

        $quote_inputs = QuoteInput::where([
            ['language_id', $userCurrentLang->id],
            ['user_id', $user->id],
        ])->get();

        $rules = [
            'name' => 'required',
            'email' => 'required|email',
        ];
        $userBs = BasicSetting::where('user_id', $user->id)->first();
        if ($userBs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
        }
        $messages = [
            'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
            'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
        ];

        $allowedExts = array('zip');
        foreach ($quote_inputs as $input) {
            if ($input->required == 1) {
                $rules["$input->name"][] = 'required';
            }
            // check if input type is 5, then check for zip extension
            if ($input->type == 5) {
                $rules["$input->name"][] = function ($attribute, $value, $fail) use ($request, $input, $allowedExts) {
                    if ($request->hasFile("$input->name")) {
                        $ext = $request->file("$input->name")->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            $fail("Only zip file is allowed");
                        }
                    }
                };
            }
        }
        $request->validate($rules);
        $fields = [];
        foreach ($quote_inputs as $key => $input) {
            $in_name = $input->name;
            // if the input is file, then move it to 'files' folder
            if ($input->type == 5) {
                if ($request->hasFile("$in_name")) {
                    $fileName = uniqid() . '.' . $request->file("$in_name")->getClientOriginalExtension();
                    $directory = public_path('assets/front/files/');
                    @mkdir($directory, 0775, true);
                    $request->file("$in_name")->move($directory, $fileName);

                    $fields["$in_name"]['value'] = $fileName;
                    $fields["$in_name"]['type'] = $input->type;
                }
            } else {
                if ($request["$in_name"]) {
                    $fields["$in_name"]['value'] = $request["$in_name"];
                    $fields["$in_name"]['type'] = $input->type;
                }
            }
        }
        $jsonfields = json_encode($fields);
        $jsonfields = str_replace("\/", "/", $jsonfields);

        $quote = new Quote;
        $quote->name = $request->name;
        $quote->email = $request->email;
        $quote->user_id = $user->id;
        $quote->fields = $jsonfields;

        $quote->save();

        $subject = "Quote Request Received";

        $be = BE::first();

        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";

        if ($be->is_smtp == 1) {
            try {
                //Server settings
                $mail->isSMTP(); // Send using SMTP
                $mail->Host = $be->smtp_host; // Set the SMTP server to send through
                $mail->SMTPAuth = true; // Enable SMTP authentication
                $mail->Username = $be->smtp_username; // SMTP username
                $mail->Password = $be->smtp_password; // SMTP password
                $mail->SMTPSecure = $be->encryption; // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
                $mail->Port = $be->smtp_port; // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above
                $mail->CharSet = 'UTF-8';
                $mail->addReplyTo($request->email);
                //Recipients
                $mail->setFrom($be->from_mail, $request->name);
                $mail->addAddress($user->email); // Add a recipient

            } catch (Exception $e) {
                Session::flash('error', $e->getMessage());
                return back();
            }
        } else {
            try {
                //Recipients
                $mail->setFrom($be->from_mail, $request->name);
                $mail->addReplyTo($request->email);
                $mail->addAddress($user->email); // Add a recipient
            } catch (Exception $e) {
                Session::flash('error', $e->getMessage());
                return back();
            }
        }

        $message = '<div dir="ltr">You have received a new quote request.<br/><strong dir="ltr">Client Name - </strong><span dir="ltr">' . $request->name . '</span><br/><strong dir="ltr">Client Mail - </strong><span dir="ltr">' . $request->email . "</span><br>";

        if (!empty($fields) && is_array($fields)) {
            foreach ($fields as $key => $field) {
                if ($field['type'] != 5) {
                    $message .= "<div><strong dir='ltr'>" . ucwords(str_replace("_", " ", $key)) . " - </strong>";
                    if (!is_array($field['value'])) {
                        $message .= "<span dir='ltr'>" . $field['value'] . "</span>";
                    } else {
                        $values = $field['value'];
                        $i = 0;
                        foreach ($values as $key => $value) {
                            $i++;
                            $message .= "<span dir='ltr'>" . $value . "</span>";
                            if (count($values) > $i) {
                                $message .= ", ";
                            }
                        }
                    }
                    $message .= "</div>";
                }
            }
        }
        $message .= "</div>";

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();

        Session::flash('success', 'Quote request sent successfully');
        return back();
    }

    public function vcard($domain, $id)
    {
        $vcard = UserVcard::where('status', '=', 1)->findOrFail($id);
        $data['userBs'] = BasicSetting::select('is_recaptcha', 'google_recaptcha_site_key', 'google_recaptcha_secret_key')
            ->where('user_id', $vcard->user_id)->first();
        Config::set('captcha.sitekey', $data['userBs']->google_recaptcha_site_key);
        Config::set('captcha.secret', $data['userBs']->google_recaptcha_secret_key);

        $count = $vcard->user->memberships()->where('status', '=', 1)
            ->where('start_date', '<=', Carbon::now()->format('Y-m-d'))
            ->where('expire_date', '>=', Carbon::now()->format('Y-m-d'))
            ->count();

        // check if the vcard owner does not have membership
        if ($count == 0) {
            return view('errors.404');
        }

        $cFeatures = UserPermissionHelper::packagePermission($vcard->user_id);
        $cFeatures = json_decode($cFeatures, true);
        if (empty($cFeatures) || !is_array($cFeatures) || !in_array('vCard', $cFeatures)) {
            return view('errors.404');
        }

        $parsedUrl = parse_url(url()->current());
        $host = $parsedUrl['host'];
        // if the current host contains the website domain
        if (strpos($host, env('WEBSITE_HOST')) !== false) {
            $host = str_replace("www.", "", $host);
            // if the current URL is subdomain
            if ($host != env('WEBSITE_HOST')) {
                $hostArr = explode('.', $host);
                $username = $hostArr[0];
                if (strtolower($vcard->user->username) != strtolower($username) || !cPackageHasSubdomain($vcard->user)) {
                    return view('errors.404');
                }
            } else {
                $path = explode('/', $parsedUrl['path']);
                $username = $path[1];
                if (strtolower($vcard->user->username) != strtolower($username)) {
                    return view('errors.404');
                }
            }
        }
        // if the current host doesn't contain the website domain (meaning, custom domain)
        else {
            // Always include 'www.' at the begining of host
            if (substr($host, 0, 4) == 'www.') {
                $host = $host;
            } else {
                $host = 'www.' . $host;
            }
            // if the current package doesn't have 'custom domain' feature || the custom domain is not connected
            $cdomain = UserCustomDomain::where('requested_domain', '=', $host)->orWhere('requested_domain', '=', str_replace("www.", "", $host))->where('status', 1)->firstOrFail();
            $username = $cdomain->user->username;
            if (!cPackageHasCdomain($vcard->user) || ($username != $vcard->user->username)) {
                return view('errors.404');
            }
        }

        $infos = json_decode($vcard->information, true);

        $prefs = [];
        if (!empty($vcard->preferences)) {
            $prefs = json_decode($vcard->preferences, true);
        }

        $keywords = json_decode($vcard->keywords, true);

        $data['vcard'] = $vcard;
        $data['infos'] = $infos;
        $data['prefs'] = $prefs;
        $data['keywords'] = $keywords;
        if ($vcard->template == 1) {
            return view('vcard.index1', $data);
        } elseif ($vcard->template == 2) {
            return view('vcard.index2', $data);
        } elseif ($vcard->template == 3) {
            return view('vcard.index3', $data);
        } elseif ($vcard->template == 4) {
            return view('vcard.index4', $data);
        } elseif ($vcard->template == 5) {
            return view('vcard.index5', $data);
        } elseif ($vcard->template == 6) {
            return view('vcard.index6', $data);
        } elseif ($vcard->template == 7) {
            return view('vcard.index7', $data);
        } elseif ($vcard->template == 8) {
            return view('vcard.index8', $data);
        } elseif ($vcard->template == 9) {
            return view('vcard.index9', $data);
        } elseif ($vcard->template == 10) {
            return view('vcard.index10', $data);
        }
    }

    public function vcardImport($domain, $id)
    {
        $vcard = UserVcard::findOrFail($id);

        // define vcard
        $vcardObj = new VCard();

        // add personal data
        if (!empty($vcard->name)) {
            $vcardObj->addName($vcard->name);
        }
        if (!empty($vcard->company)) {
            $vcardObj->addCompany($vcard->company);
        }
        if (!empty($vcard->occupation)) {
            $vcardObj->addJobtitle($vcard->occupation);
        }
        if (!empty($vcard->email)) {
            $vcardObj->addEmail($vcard->email);
        }
        if (!empty($vcard->phone)) {
            $vcardObj->addPhoneNumber($vcard->phone, 'WORK');
        }
        if (!empty($vcard->address)) {
            $vcardObj->addAddress($vcard->address);
            $vcardObj->addLabel($vcard->address);
        }
        if (!empty($vcard->website_url)) {
            $vcardObj->addURL($vcard->website_url);
        }

        $vcardObj->addPhoto(public_path('assets/front/img/user/vcard/' . $vcard->profile_image));

        return \Response::make(
            $vcardObj->getOutput(),
            200,
            $vcardObj->getHeaders(true)
        );
    }

    public function changeLanguage($lang): \Illuminate\Http\RedirectResponse
    {
        session()->put('lang', $lang);
        app()->setLocale($lang);
        return redirect()->route('front.index');
    }

    public function changeUserLanguage(Request $request, $domain): \Illuminate\Http\RedirectResponse
    {
        session()->put('user_lang', $request->code);
        return redirect()->route('front.user.detail.view', $domain);
    }

    public function userCPage($param, $slug)
    {
        $user = getUser();
        $userId = $user->id;
        if (session()->has('user_lang')) {
            $userCurrentLang = UserLanguage::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = UserLanguage::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['page'] = User\Page::query()->where('user_id', $userId)->where('language_id', $userCurrentLang->id)->where('slug', $slug)->firstOrFail();

        return view('user-front.custom-page', $data);
    }

    public function get_info(Request $request)
    {
        // Get the client IP
        $ip = $request->ip();

        if ($ip === '127.0.0.1' || $ip === '::1') {
            $publicIpResponse = Http::get('https://api.ipify.org?format=json');
            $ip = $publicIpResponse->json()['ip'] ?? $ip;
        }

        // Use the IP for your logic...
        $response = Http::get("http://ip-api.com/json/{$ip}");

        $json = $response->json();
        if (isset($json['status']) && $json['status'] === 'success') {
            $country_value = $json['country'];
            $country_code  = $json['countryCode'];
            $region_name   = $json['regionName'];
            $city          = $json['city'];
        } else {
            $country_value = 'Unknown';
            $country_code  = null;
            $region_name   = null;
            $city          = null;
        }

        Visitor::create([
            'user_id'     => $request->input('user_id'),
            'device_type' => $request->input('device_type'),
            'country'     => $country_value,
            'country_code'=> $country_code,
            'region_name' => $region_name,
            'city'        => $city,
            'ip'          => $ip,
        ]);
    }



    public function getStats(Request $request)
    {
        $userId = Auth::user()->id;


        if (!$userId) {
            return response()->json(['error' => 'User ID is required'], 400);
        }

        // Device statistics filtered by user_id
        $deviceStats = DB::table('visitors')
            ->select(DB::raw('device_type, COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('device_type')
            ->get();

        // Country statistics filtered by user_id
        $countryStats = DB::table('visitors')
            ->select(DB::raw('country, COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('country')
            ->get();

        // Top 5 cities filtered by user_id
        $topCities = DB::table('visitors')
            ->select('city', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('city')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        // Top 5 regions filtered by user_id
        $topRegions = DB::table('visitors')
            ->select('region_name', DB::raw('COUNT(*) as count'))
            ->where('user_id', $userId)
            ->groupBy('region_name')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

        return response()->json([
            'deviceStats' => $deviceStats,
            'countryStats' => $countryStats,
            'topCities' => $topCities,
            'topRegions' => $topRegions,
        ]);
    }
}
