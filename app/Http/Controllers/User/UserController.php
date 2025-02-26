<?php

namespace App\Http\Controllers\User;

use App;
use Validator;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Package;
use App\Models\Customer;
use App\Models\UserStep;
use App\Models\Membership;
use App\Models\User\Brand;
use App\Models\User\Skill;
use Illuminate\Http\Request;
use App\Models\User\Follower;
use App\Models\User\Language;
use App\Models\User\Portfolio;
use App\Models\User\FooterText;
use App\Models\User\HeroSlider;
use App\Models\User\HeroStatic;
use App\Models\User\UserService;
use App\Models\User\BasicSetting;
use App\Models\User\HomePageText;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Models\User\FooterQuickLink;
use App\Models\User\UserTestimonial;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User\PortfolioCategory;
use Illuminate\Support\Facades\Config;
use App\Models\User\CounterInformation;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    // webstie_settings
    // public function webstie_settings(Request $request)
    // {
    //     // $language = Language::where('user_id', Auth::guard('web')->user()->id)->where('code', $request->language)->firstOrFail();
    //     if ($request->has('language')) {
    //         $lang = Language::where([
    //             ['code', $request->language],
    //             ['user_id', Auth::id()]
    //         ])->first();
    //         Session::put('currentLangCode', $request->language);
    //     } else {
    //         $lang = Language::where([
    //             ['is_default', 1],
    //             ['user_id', Auth::id()]
    //         ])
    //             ->first();
    //         Session::put('currentLangCode', $lang->code);
    //     }

    //     if (!$lang) {
    //         return redirect()->back()->with('error', 'Default language not found.');
    //     }

    //     // Collect all necessary information
    //     $information = [

    //         'footertext' => FooterText::where('language_id', $lang->id)
    //             ->where('user_id', Auth::id())
    //             ->first(),

    //         'brands' => Brand::where('user_id', Auth::id())
    //             ->orderBy('id', 'desc')
    //             ->get(),

    //         'footer_quick_links' => FooterQuickLink::where('user_id', Auth::id())
    //             ->get(),

    //         'basic_settings' => BasicSetting::where('user_id', Auth::id())
    //             ->first(),

    //         'counterInformations' => CounterInformation::where('language_id', $lang->id)
    //             ->where('user_id', Auth::id())
    //             ->orderBy('id', 'DESC')
    //             ->get(),

    //         'skills' => Skill::where('language_id', $lang->id)
    //             ->where('user_id', Auth::id())
    //             ->orderBy('id', 'DESC')
    //             ->get(),
    //         'portfolios' => Portfolio::where('language_id', $lang->id)
    //             ->where('user_id', Auth::id())
    //             ->orderBy('id', 'DESC')
    //             ->get(),
    //         'sliders' => HeroSlider::where('language_id', $lang->id)
    //             ->where('user_id', Auth::id())
    //             ->orderBy('id', 'DESC')
    //             ->get(),

    //         'home_setting' => HomePageText::firstOrCreate(
    //             ['user_id' => Auth::id(), 'language_id' => $lang->id],
    //             []
    //         ),

    //         'services' => UserService::where('lang_id', $lang->id)
    //             ->where('user_id', Auth::id())
    //             ->orderBy('id', 'DESC')
    //             ->get()

    //     ];
    //     // dd($language );
    //     // dd($lang->id );
    //     // dd($information['home_setting'] );

    //     return view('user.webstie_settings', compact('information',));
    // }

    //
    public function webstie_settings(Request $request)
    {
        $lang = Language::where('user_id', Auth::id())->first();
        if ($request->has('language')) {
            $lang = Language::where([
                ['code', $request->language],
                ['user_id', Auth::id()]
            ])->first();
            Session::put('currentLangCode', $request->language);
        } else {
            if (!$lang) {
                $lang = Language::where([
                    ['is_default', 1],
                    ['user_id', Auth::id()]
                ])->first();
            }
            Session::put('currentLangCode', $lang->code);
        }

        if (!$lang) {
            return redirect()->back()->with('error', 'Default language not found.');
        }

        $information = [
            'footertext' => FooterText::where('language_id', $lang->id)
                ->where('user_id', Auth::id())
                ->first(),
            'brands' => Brand::where('user_id', Auth::id())
                ->orderBy('id', 'desc')
                ->get(),
            'footer_quick_links' => FooterQuickLink::where('user_id', Auth::id())
                ->get(),
            'basic_settings' => BasicSetting::where('user_id', Auth::id())
                ->first(),
            'counterInformations' => CounterInformation::where('language_id', $lang->id)
                ->where('user_id', Auth::id())
                ->orderBy('id', 'DESC')
                ->get(),
            'skills' => Skill::where('language_id', $lang->id)
                ->where('user_id', Auth::id())
                ->orderBy('id', 'DESC')
                ->get(),
            'portfolios' => Portfolio::where([
                ['language_id', '=', $lang->id],
                ['user_id', '=', Auth::id()],
            ])
                ->orderBy('id', 'DESC')
                ->get(),
            'categories' => PortfolioCategory::where([
                ['language_id', '=', $lang->id],
                ['user_id', '=', Auth::id()],
                ['status', '=', 1]
            ])
                ->orderBy('serial_number', 'ASC')
                ->get(),
            'sliders' => HeroSlider::where('language_id', $lang->id)
                ->where('user_id', Auth::id())
                ->orderBy('id', 'DESC')
                ->get(),
            'sliders_static' => HeroStatic::where('language_id', $lang->id)->first(),
            'testimonials' => UserTestimonial::where([
                ['user_id', '=', Auth::id()],
                ['lang_id', '=', $lang->id],
            ])->orderBy('id', 'DESC')
                ->get(),
            'home_setting' => HomePageText::firstOrCreate(
                ['user_id' => Auth::id(), 'language_id' => $lang->id],
                []
            ),
            'services' => UserService::where('lang_id', $lang->id)
                ->where('user_id', Auth::id())
                ->orderBy('id', 'DESC')
                ->get()
        ];

        return view('user.webstie_settings', compact('information'));
    }
    //

    public function userban(Request $request)
    {
        $user = Customer::where('id', $request->user_id)->first();
        $user->update([
            'status' => $request->status,
        ]);
        Session::flash('success', 'Status update successfully!');
        return back();
    }
    public function emailStatus(Request $request)
    {
        $bs = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        Config::set('app.timezone', $bs->timezoneinfo->timezone);

        $user = Customer::findOrFail($request->user_id);
        if ($user->email_verified_at) {
            $v = null;
        } else {
            $v = Carbon::now();
        }
        $user->update([
            'email_verified_at' => $v,
        ]);
        Session::flash('success', 'Email status updated for ' . $user->username);
        return back();
    }

    public function sitesettings()
    {
        return view('user.sitesettings');
    }
    public function home_page_settings()
    {
        return view('user.home_page_settings');
    }
    public function show_steps()
    {
        $steps = UserStep::where('user_id',  Auth::guard('web')->user()->id)->first();

        if (!$steps) {
            // Initialize steps for a new user
            $steps = UserStep::create(['user_id' =>  Auth::guard('web')->user()->id]);
        }

        // Map progress to the required frontend structure
        $progressSteps = [
            ['url' => 'user.basic_settings.general-settings', 'title' => 'تحديث الشعار الخاص بك', 'completed' => (bool) $steps->logo_uploaded],
            ['url' => 'user.basic_settings.general-settings', 'title' => 'تحديث ايقونة الموقع', 'completed' => (bool) $steps->favicon_uploaded],
            ['url' => 'user.basic_settings.general-settings', 'title' => 'تحديث اسم الموقع الخاص بك', 'completed' => (bool) $steps->website_named],
            ['url' => 'user.home.page.text.edit', 'title' => 'تحديث بيانات الصفحة الرئيسية', 'completed' => (bool) $steps->homepage_updated],
            ['url' => 'user.basic_settings.general-settings', 'title' => 'تحديث بيانات الصفحة عن الشركه', 'completed' => (bool) $steps->homepage_about_update],
            ['url' => 'user.basic_settings.general-settings', 'title' => 'تحديث بيانات الصفحة معلومات الاتصال', 'completed' => (bool) $steps->contacts_social_info],
            ['url' => 'user.home_page.hero.slider_version', 'title' => 'تحديث بيانات الصفحة البانرات', 'completed' => (bool) $steps->banner],
            ['url' => 'user.home_page.hero.slider_version', 'title' => 'تحديث بيانات الصفحة صورة اعلى الصفحات الفرعية', 'completed' => (bool) $steps->sub_pages_upper_image],
            ['url' => 'user.menu_builder.index', 'title' => 'تحديث بيانات الصفحة منشئ القائمة', 'completed' => (bool) $steps->menu_builder],
            ['url' => 'user.services.index', 'title' => 'تحديث بيانات الصفحة خدماتنا', 'completed' => (bool) $steps->services],
            ['url' => 'user.footer.text', 'title' => 'تحديث بيانات الصفحة الذيل', 'completed' => (bool) $steps->footer],
        ];

        $data['steps'] = $progressSteps;

        return view('user.show_steps', $data);
    }

    public function index()
    {

        $user = Auth::guard('web')->user();
        $bs = BasicSetting::where('user_id', Auth::guard('web')->user()->id)->first();
        // Config::set('app.timezone', $bs->timezoneinfo->timezone);
        if (!$bs || !$bs->timezoneinfo) {
            Config::set('app.timezone', 'UTC'); // Set a default timezone
        } else {
            Config::set('app.timezone', $bs->timezoneinfo->timezone);
        }

        $deLang = Language::where('user_id', Auth::guard('web')->user()->id)->where('is_default', 1)->firstOrFail();
        $data['user'] = $user;
        $data['skills'] = $user->skills()->where('language_id', $deLang->id)->count();
        $data['portfolios'] = $user->portfolios()->where('language_id', $deLang->id)->count();
        $data['services'] = $user->services()->where('lang_id', $deLang->id)->count();
        $data['testimonials'] = $user->testimonials()->where('lang_id', $deLang->id)->count();
        $data['blogs'] = $user->blogs()->where('language_id', $deLang->id)->count();
        $data['counter_informations'] = $user->counterInformations()->where('language_id', $deLang->id)->count();
        $data['followers'] = Follower::where('following_id', Auth::guard('web')->user()->id)->count();
        $data['followings'] = Follower::where('follower_id', Auth::guard('web')->user()->id)->count();

        $data['memberships'] = Membership::query()->where('user_id', Auth::user()->id)
            ->orderBy('id', 'DESC')
            ->limit(10)->get();

        $data['users'] = [];
        $followingListIds = Follower::query()->where('follower_id', Auth::guard('web')->user()->id)->pluck('following_id');
        if (count($followingListIds) > 0) {
            $data['users'] = User::whereIn('id', $followingListIds)->limit(10)->get();
        }

        $nextPackageCount = Membership::query()->where([
            ['user_id', Auth::guard('web')->user()->id],
            ['expire_date', '>=', Carbon::now()->toDateString()]
        ])->whereYear('start_date', '<>', '9999')->where('status', '<>', 2)->count();
        //current package
        $data['current_membership'] = Membership::query()->where([
            ['user_id', Auth::guard('web')->user()->id],
            ['start_date', '<=', Carbon::now()->toDateString()],
            ['expire_date', '>=', Carbon::now()->toDateString()]
        ])->where('status', 1)->whereYear('start_date', '<>', '9999')->first();
        if ($data['current_membership']) {
            $countCurrMem = Membership::query()->where([
                ['user_id', Auth::guard('web')->user()->id],
                ['start_date', '<=', Carbon::now()->toDateString()],
                ['expire_date', '>=', Carbon::now()->toDateString()]
            ])->where('status', 1)->whereYear('start_date', '<>', '9999')->count();
            if ($countCurrMem > 1) {
                $data['next_membership'] = Membership::query()->where([
                    ['user_id', Auth::guard('web')->user()->id],
                    ['start_date', '<=', Carbon::now()->toDateString()],
                    ['expire_date', '>=', Carbon::now()->toDateString()]
                ])->where('status', '<>', 2)->whereYear('start_date', '<>', '9999')->orderBy('id', 'DESC')->first();
            } else {
                $data['next_membership'] = Membership::query()->where([
                    ['user_id', Auth::guard('web')->user()->id]
                ])->where(function ($query) use ($data) {
                    $query->where('start_date', '>=', $data['current_membership']->expire_date)
                        ->orWhere('transaction_details', '=', '"offline"');
                })->whereYear('start_date', '<>', '9999')->where('status', '<>', 2)->first();
            }
            $data['next_package'] = $data['next_membership'] ? Package::query()->where('id', $data['next_membership']->package_id)->first() : null;
        }
        $data['current_package'] = $data['current_membership'] ? Package::query()->where('id', $data['current_membership']->package_id)->first() : null;
        $data['package_count'] = $nextPackageCount;

        // $steps = UserStep::where('user_id',  Auth::guard('web')->user()->id)->first();

        // if (!$steps) {
        //     // Initialize steps for a new user
        //     $steps = UserStep::create(['user_id' =>  Auth::guard('web')->user()->id]);
        // }

        // // Map progress to the required frontend structure
        // $progressSteps = [
        //     ['title' => 'تحديث الشعار الخاص بك', 'completed' => (bool) $steps->logo_uploaded],
        //     ['title' => 'تحديث ايقونة الموقع', 'completed' => (bool) $steps->favicon_uploaded],
        //     ['title' => 'تحديث اسم الموقع الخاص بك', 'completed' => (bool) $steps->website_named],
        //     ['title' => 'تحديث بيانات الصفحة الرئيسية', 'completed' => (bool) $steps->homepage_updated],
        // ];

        // $data['steps'] = $progressSteps;




        return view('user.dashboard', $data);
    }

    public function registerUsers()
    {
        $term = request('term');
        $data['current_language'] = Language::where([['is_default', 1], ['user_id', Auth::guard('web')->user()->id]])->firstOrFail();
        $data['users'] = Customer::when($term, function ($query, $term) {
            $query->where('username', 'like', '%' . $term . '%')->orWhere('email', 'like', '%' . $term . '%');
        })->where('user_id', Auth::guard('web')->user()->id)->orderBy('id', "DESC")->paginate(10);


        return view('user.register_customer.index', $data);
    }

    public function status(Request $request)
    {
        $user = Auth::user();
        $user->online_status = $request->value;
        $user->save();
        $msg = '';
        if ($request->value == 1) {
            $msg = "Profile has been made visible";
        } else {
            $msg = "Profile has been hidden";
        }
        Session::flash('success', $msg);
        return "success";
    }

    public function profile()
    {
        $user = Auth::user();
        return view('user.edit-profile', compact('user'));
    }

    public function profileupdate(Request $request)
    {
        $img = $request->file('photo');
        $allowedExts = array('jpg', 'png', 'jpeg');

        $request->validate([
            'first_name' => 'required',
            'last_name' => 'required',
            'username' => 'required|unique:users,username,' . Auth::user()->id,
            'phone' => 'required',
            'city' => 'required',
            'state' => 'required',
            'country' => 'required',
            'address' => 'required',
            'photo' => [
                function ($attribute, $value, $fail) use ($request, $img, $allowedExts) {
                    if ($request->hasFile('photo')) {
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            return $fail("Only png, jpg, jpeg image is allowed");
                        }
                    }
                },
            ],
        ]);

        //--- Validation Section Ends
        $input = $request->all();
        $data = Auth::user();
        if ($file = $request->file('photo')) {
            $name = time() . $file->getClientOriginalName();
            $file->move(public_path('assets/front/img/user/'), $name);
            if ($data->photo != null) {
                @unlink(public_path('assets/front/img/user/' . $data->photo));
            }
            $input['photo'] = $name;
        }
        $data->update($input);
        Session::flash('success', 'Profile Updated Successfully!');
        return "success";
    }

    public function resetform()
    {
        return view('user.reset');
    }

    public function reset(Request $request)
    {

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required',
            'confirmation_password' => 'required',
        ]);
        $user = Auth::user();
        if ($request->current_password) {
            if (Hash::check($request->current_password, $user->password)) {
                if ($request->new_password == $request->confirmation_password) {
                    $input['password'] = Hash::make($request->new_password);
                } else {
                    return back()->with('err', __('Confirm password does not match.'));
                }
            } else {
                return back()->with('err', __('Current password Does not match.'));
            }
        }

        $user->update($input);
        Session::flash('success', 'Successfully change your password');
        return back();
    }

    public function changePass()
    {
        return view('user.changepass');
    }

    public function updatePassword(Request $request)
    {
        $messages = [
            'password.required' => 'The new password field is required',
            'password.confirmed' => "Password does'nt match"
        ];
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'password' => 'required|confirmed'
        ], $messages);
        // if given old password matches with the password of this authenticated user...
        if (Hash::check($request->old_password, Auth::guard('web')->user()->password)) {
            $oldPassMatch = 'matched';
        } else {
            $oldPassMatch = 'not_matched';
        }
        if ($validator->fails() || $oldPassMatch == 'not_matched') {
            if ($oldPassMatch == 'not_matched') {
                $validator->errors()->add('oldPassMatch', true);
            }
            return redirect()->route('user.changePass')
                ->withErrors($validator);
        }

        // updating password in database...
        $user = App\Models\User::findOrFail(Auth::guard('web')->user()->id);
        $user->password = bcrypt($request->password);
        $user->save();

        Session::flash('success', 'Password changed successfully!');

        return redirect()->back();
    }

    public function shippingdetails()
    {
        $user = Auth::user();
        return view('user.shipping_details', compact('user'));
    }

    public function shippingupdate(Request $request)
    {

        $request->validate([
            "shpping_fname" => 'required',
            "shpping_lname" => 'required',
            "shpping_email" => 'required',
            "shpping_number" => 'required',
            "shpping_city" => 'required',
            "shpping_state" => 'required',
            "shpping_address" => 'required',
            "shpping_country" => 'required',
        ]);


        Auth::user()->update($request->all());

        Session::flash('success', 'Shipping Details Update Successfully.');
        return back();
    }

    public function billingdetails()
    {
        $user = Auth::user();
        return view('user.billing_details', compact('user'));
    }

    public function billingupdate(Request $request)
    {
        $request->validate([
            "billing_fname" => 'required',
            "billing_lname" => 'required',
            "billing_email" => 'required',
            "billing_number" => 'required',
            "billing_city" => 'required',
            "billing_state" => 'required',
            "billing_address" => 'required',
            "billing_country" => 'required',
        ]);

        Auth::user()->update($request->all());

        Session::flash('success', 'Billing Details Update Successfully.');
        return back();
    }

    public function changeTheme(Request $request)
    {
        return redirect()->back()->withCookie(cookie()->forever('user-theme', $request->theme));
    }

    public function delete(Request $request)
    {
        $user = Customer::findOrFail($request->user_id);

        // room booking info delete
        if ($user->roomBookings()->count() > 0) {
            $user->roomBookings()->delete();
        }
        // room reviews delete
        if ($user->roomReviews()->count() > 0) {
            $user->roomReviews()->delete();
        }
        // donation delails delete
        if ($user->donationDetails()->count() > 0) {
            $user->donationDetails()->delete();
        }
        // delete course enrolment
        if ($user->courseEnrolment()->count() > 0) {
            $user->courseEnrolment()->delete();
        }

        if ($user->quizScore()->count() > 0) {
            $user->quizScore()->delete();
        }

        if ($user->review()->count() > 0) {
            $user->review()->delete();
        }

        // deleting customer wishlist, order list and order item list

        if ($user->customerWishlist()->count()) {
            $user->customerWishlist()->delete();
        }
        if ($user->customerOrderList()->count()) {
            $user->customerOrderList()->delete();
        }
        if ($user->customerOrderItemList()->count()) {
            $user->customerOrderItemList()->delete();
        }
        // user image unlinking
        @unlink(public_path('assets/user/img/users/' . $user->image));
        $user->delete();
        Session::flash('success', 'Customer deleted successfully!');
        return back();
    }

    public function changePassCstmr()
    {
        return view('user.register_customer.changepass');
    }
    public function updatePasswordCstmr(Request $request)
    {
        // dd($request->all());
        $id = $request->customer_id;
        $rules = [
            'password' => 'required|confirmed',
            'password_confirmation' => 'required'
        ];

        $messages = [
            'password.confirmed' => 'Password confirmation does not match.',
            'password_confirmation.required' => 'The confirm new password field is required.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            return back()->withErrors($validator->getMessageBag()->toArray())->withInput();
        }

        $user = Customer::where('id', $id)->where('user_id', Auth::guard('web')->user()->id)->firstOrFail();

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        $request->session()->flash('success', 'Password updated successfully!');
        return back();
    }
    public function view($id)
    {
        $data['statuses'] = ([
            1 => 'Approved',
            0 => 'Pending',
            2 => 'Rejected',
        ]);
        $data['current_language'] = Language::where([['is_default', 1], ['user_id', Auth::guard('web')->user()->id]])->firstOrFail();
        $data['user'] = Customer::findOrFail($id);
        // dd($user);
        return view('user.register_customer.details', $data);
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        foreach ($ids as $id) {
            $user = Customer::findOrFail($id);
            // room booking info delete
            if ($user->roomBookings()->count() > 0) {
                $user->roomBookings()->delete();
            }
            // room reviews delete
            if ($user->roomReviews()->count() > 0) {
                $user->roomReviews()->delete();
            }
            // donation delails delete
            if ($user->donationDetails()->count() > 0) {
                $user->donationDetails()->delete();
            }
            // delete course enrolment
            if ($user->courseEnrolment()->count() > 0) {
                $user->courseEnrolment()->delete();
            }

            if ($user->quizScore()->count() > 0) {
                $user->quizScore()->delete();
            }

            if ($user->review()->count() > 0) {
                $user->review()->delete();
            }


            if ($user->customerWishlist()->count()) {
                $user->customerWishlist()->delete();
            }
            if ($user->customerOrderList()->count()) {
                $user->customerOrderList()->delete();
            }
            if ($user->customerOrderItemList()->count()) {
                $user->customerOrderItemList()->delete();
            }
            // user image unlinking
            @unlink(public_path('assets/user/img/users/' . $user->image));
            $user->delete();
        }
        Session::flash('success', 'Customer(s) deleted successfully!');
        return 'success';
    }

    public function secretLogin(Request $request)
    {

        $customer = Customer::where('id', $request->user_id)->first();

        $param = $customer->user->username;
        if ($customer) {
            Auth::guard('customer')->login($customer);;
            return redirect()->route('customer.dashboard', $param)
                ->withSuccess('You have Successfully loggedin');
        }

        return redirect()->route('customer.login', $param)->withSuccess('Oppes! You have entered invalid credentials');
    }
}
