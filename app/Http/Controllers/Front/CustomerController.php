<?php

namespace App\Http\Controllers\Front;

use App\Constants\Constant;
use Config;
use User\HomePageText;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\Language;
use App\Http\Helpers\Uploader;
use App\Models\User\UserOrder;
use App\Models\User\UserContact;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\DB;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Controllers\Controller;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\User\CourseManagement\Course;
use App\Models\User\CourseManagement\CourseEnrolment;
use App\Models\User\CourseManagement\CourseInformation;
use App\Models\User\CourseManagement\Lesson;
use App\Models\User\CourseManagement\LessonComplete;
use App\Models\User\CourseManagement\LessonContent;
use App\Models\User\CourseManagement\LessonContentComplete;
use App\Models\User\CourseManagement\LessonQuiz;
use App\Models\User\CourseManagement\Module;
use App\Models\User\CourseManagement\QuizScore;
use App\Models\User\UserShopSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User\CustomerWishList;
use App\Models\User\DonationManagement\DonationContent;
use App\Models\User\DonationManagement\DonationDetail;
use App\Models\User\HomePageText as UserHomePageText;
use App\Models\User\HotelBooking\RoomBooking;
use App\Models\User\UserEmailTemplate;
use App\Models\User\UserOfflineGateway;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Carbon;

class CustomerController extends Controller
{

    public function __construct()
    {

        $user = getUser();
        $userBs = BasicSetting::where('user_id', $user->id)->first();
        Config::set('captcha.sitekey', $userBs->google_recaptcha_site_key);
        Config::set('captcha.secret', $userBs->google_recaptcha_secret_key);
    }


    public function login($domain, Request $request)
    {
        $user = getUser();
        // when user have to redirect to check out page after login.
        return view('user-front.customer.login');
    }
    public function orderdetails($domain, $id)
    {

        $user = getUser();
        $userShop = UserShopSetting::where('user_id', $user->id)->first();
        if (!empty($userShop) && ($userShop->is_shop == 0 || $userShop->catalog_mode == 1)) {
            return back();
        }
        $data['currentLanguage'] = $this->getUserCurrentLanguage(getUser()->id);
        $bex = UserShopSetting::where('user_id',  $user->id)->first();
        if ($bex->is_shop == 0) {
            return back();
        }
        $data['data'] = UserOrder::findOrFail($id);
        return view('user-front.customer.order_details', $data);
    }

    public function onlineSuccess()
    {
        Session::forget('user_coupon');
        Session::forget('coupon_amount');
        return view('user-front.success');
    }

    public function loginSubmit(Request $request, $domain)
    {

        $rules = [];
        $messages = [];
        // at first, get the url from session which will be redirected after login
        if ($request->session()->has('link')) {
            $redirectURL = $request->session()->get('link');
        } else {
            $redirectURL = null;
        }
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];

        $ubs  = BasicSetting::where('user_id', getUser()->id)->first();
        if ($ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            ];
        }

        $request->validate($rules, $messages);


        // get the email and password which has provided by the user
        $credentials = $request->only('email', 'password', 'user_id');
        // login attempt
        if (Auth::guard('customer')->attempt($credentials)) {
            $authUser = Auth::guard('customer')->user();
            // first, check whether the user's email address verified or not
            if ($authUser->email_verified_at == null) {
                $request->session()->flash('error', 'Please, verify your email address.');
                // logout auth user as condition not satisfied
                Auth::guard('customer')->logout();
                return redirect()->back();
            }
            // second, check whether the user's account is active or not
            if ($authUser->status == 0) {
                $request->session()->flash('error', 'Sorry, your account has been deactivated.');
                // logout auth user as condition not satisfied
                Auth::guard('customer')->logout();
                return redirect()->back();
            }
            // otherwise, redirect auth user to next url
            if ($redirectURL == null) {
                return redirect()->route('customer.dashboard', getParam());
            } else {
                // before, redirect to next url forget the session value
                $request->session()->forget('link');
                return redirect($redirectURL);
            }
        } else {
            $request->session()->flash('error', 'The provided credentials do not match our records!');
            return redirect()->back();
        }
    }
    public function forgetPassword($domain)
    {

        $user = getUser();

        return view('user-front.customer.forget-password');
    }
    public function sendMail(Request $request)
    {
        $rules = [
            'email' => [
                'required',
                'email:rfc,dns',
            ]
        ];
        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $user = Customer::where([['email', $request->email], ['user_id', getUser()->id]])->first();

        if (is_null($user)) {
            $request->session()->flash('error', 'No user found!');
            return redirect()->back();
        }
        // first, get the mail template information from db
        $mailTemplate = UserEmailTemplate::where('user_id', getUser()->id)->where('email_type', 'reset_password')->first();
        $mailSubject = $mailTemplate->email_subject;
        $mailBody = $mailTemplate->email_body;
        // second, send a password reset link to user via email
        $info = DB::table('basic_extendeds')
            ->select('is_smtp', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')
            ->first();
        $websiteInfo = DB::table('user_basic_settings')->where('user_id', getUser()->id)
            ->select(['website_title', 'from_name', 'email'])
            ->first();
        $name = $user->first_name . ' ' . $user->last_name;
        $link = '<a href=' . route('customer.reset_password', getParam()) . '>Click Here</a>';
        $mailBody = str_replace('{customer_name}', $name, $mailBody);
        $mailBody = str_replace('{password_reset_link}', $link, $mailBody);
        $mailBody = str_replace('{website_title}', $websiteInfo->website_title, $mailBody);
        // initialize a new mail
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        // if smtp status == 1, then set some value for PHPMailer
        if ($info->is_smtp == 1) {
            $mail->isSMTP();
            $mail->Host       = $info->smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $info->smtp_username;
            $mail->Password   = $info->smtp_password;
            if ($info->encryption == 'TLS') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $info->smtp_port;
        }
        // finally, add other information and send the mail
        try {
            $mail->setFrom($info->from_mail, $websiteInfo->from_name);
            $mail->addAddress($request->email);
            $mail->addReplyTo($websiteInfo->email, $websiteInfo->from_name);
            $mail->isHTML(true);
            $mail->Subject = $mailSubject;
            $mail->Body = $mailBody;
            $mail->send();
            $request->session()->flash('success', 'A mail has been sent to your email address.');
        } catch (Exception $e) {
            dd($e);
            $request->session()->flash('error', 'Mail could not be sent!');
        }
        // store user email in session to use it later
        $request->session()->put('userEmail', $user->email);

        return redirect()->back();
    }


    public function resetPassword($domain)
    {
        $user = getUser();
        return view('user-front.customer.reset-password');
    }

    public function resetPasswordSubmit(Request $request, $domain)
    {
        $author = getUser();
        // get the user email from session
        $emailAddress = $request->session()->get('userEmail');

        $rules = [
            'new_password' => 'required|confirmed',
            'new_password_confirmation' => 'required'
        ];

        $messages = [
            'new_password.confirmed' => 'Password confirmation failed.',
            'new_password_confirmation.required' => 'The confirm new password field is required.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        $user = Customer::where('email', $emailAddress)->where('user_id', $author->id)->first();
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        $request->session()->flash('success', 'Password updated successfully.');
        return redirect()->route('customer.login', getParam());
    }

    public function signup($domain)
    {
        $user = getUser();

        return view('user-front.customer.signup', $user);
    }
    public function contact(Request $request, $domain)
    {
        $user = getUser();
        $data['user'] = $user;
        if (session()->has('user_lang')) {
            $userCurrentLang = Language::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($userCurrentLang)) {
                $userCurrentLang = Language::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $userCurrentLang->code);
            }
        } else {
            $userCurrentLang = Language::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['contact'] = UserContact::where([
            ['user_id', $data['user']->id],
            ['language_id', $userCurrentLang->id]
        ])->first();
        $data['home_text'] = UserHomePageText::query()
            ->where([
                ['user_id', $data['user']->id],
                ['language_id', $userCurrentLang->id]
            ])->first();
        return view('user-front.contact', $data);
    }


    public function signupSubmit(Request $request, $domain)
    {
        $user = getUser();
        $messages = [];
        $rules = [];
        $rules = [
            'username' => [
                'required',
                'max:255',
                function ($attribute, $value, $fail) use ($user) {
                    if (Customer::where('username', $value)->where('user_id', $user->id)->count() > 0) {
                        $fail('Username has already been taken');
                    }
                }
            ],
            'email' => ['required', 'email', 'max:255', function ($attribute, $value, $fail) use ($user) {
                if (Customer::where('email', $value)->where('user_id', $user->id)->count() > 0) {
                    $fail('Email has already been taken');
                }
            }],
            'password' => 'required|confirmed',
            'password_confirmation' => 'required'
        ];

        $ubs  = BasicSetting::where('user_id', getUser()->id)->first();
        if ($ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            ];
        }

        $request->validate($rules, $messages);


        $customer = new Customer;
        $customer->username = $request->username;
        $customer->email = $request->email;
        $customer->user_id = $user->id;
        $customer->password = Hash::make($request->password);
        // first, generate a random string
        $randStr = Str::random(20);
        // second, generate a token
        $token = md5($randStr . $request->username . $request->email);
        $customer->verification_token = $token;
        $customer->save();
        // send a mail to user for verify his/her email address
        if ($ubs->email_verification_status == 1) {
            $this->sendVerificationMail($request, $token);
            $message = ['sendmail' => 'We need to verify your email address. We have sent an email to  ' . $request->email . ' to verify your email address. Please click link in that email to continue.'];
        } else {
            $message = [];
        }

        return redirect()
            ->back()
            ->with($message);
    }

    public function sendVerificationMail(Request $request, $token)
    {


        $user = getUser();

        // first get the mail template information from db
        $mailTemplate = UserEmailTemplate::where('user_id', $user->id)->where('email_type', 'email_verification')->first();

        $mailSubject = $mailTemplate->email_subject;
        $mailBody = $mailTemplate->email_body;
        // second get the website title & mail's smtp information from db
        $info = DB::table('basic_extendeds')
            ->select('is_smtp', 'smtp_host', 'smtp_port', 'encryption', 'smtp_username', 'smtp_password', 'from_mail', 'from_name')
            ->first();

        $websiteInfo = BasicSetting::where('user_id', $user->id)->select('website_title')->first();
        $link = '<a href=' . route('customer.signup.verify', ['token' => $token, getParam()]) . '>Click Here</a>';
        // replace template's curly-brace string with actual data
        $mailBody = str_replace('{customer_name}', $request->username, $mailBody);
        $mailBody = str_replace('{verification_link}', $link, $mailBody);
        $mailBody = str_replace('{website_title}', $websiteInfo->website_title, $mailBody);
        $userInfo = BasicSetting::where('user_id', $user->id)->select('email', 'from_name')->first();

        $email = $userInfo->email ?? $user->email;
        $name = $userInfo->from_name ?? $user->username;

        // initialize a new mail
        $mail = new PHPMailer(true);
        $mail->CharSet = "UTF-8";
        // if smtp status == 1, then set some value for PHPMailer
        if ($info->is_smtp == 1) {
            $mail->isSMTP();
            $mail->Host       = $info->smtp_host;
            $mail->SMTPAuth   = true;
            $mail->Username   = $info->smtp_username;
            $mail->Password   = $info->smtp_password;
            if ($info->encryption == 'TLS') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->Port = $info->smtp_port;
        }
        // finally, add other information and send the mail
        try {
            $mail->setFrom($info->from_mail, $name);
            $mail->addReplyTo($email);
            $mail->addAddress($request->email);
            $mail->isHTML(true);
            $mail->Subject = $mailSubject;
            $mail->Body = $mailBody;
            $mail->send();
            $request->session()->flash('success', 'A verification mail has been sent to your email address.');
        } catch (\Exception $e) {
            dd($e);
            $request->session()->flash('error', 'Mail could not be sent!');
        }
        return;
    }
    public function signupVerify(Request $request, $domain, $token)
    {
        try {
            $user = Customer::where('verification_token', $token)->firstOrFail();
            // after verify user email, put "null" in the "verification token"
            $user->update([
                'email_verified_at' => date('Y-m-d H:i:s'),
                'status' => 1,
                'verification_token' => null
            ]);
            $request->session()->flash('success', 'Your email has verified.');
            // after email verification, authenticate this user
            Auth::guard('customer')->login($user);
            return redirect()->route('customer.dashboard', getParam());
        } catch (ModelNotFoundException $e) {
            $request->session()->flash('error', 'Could not verify your email!');
            return redirect()->route('customer.signup', getParam());
        }
    }

    public function redirectToDashboard($domain)
    {
        $data['author'] = getUser();
        $data['language'] = $this->getUserCurrentLanguage($data['author']->id);
        $data['authUser'] = Auth::guard('customer')->user();
        $data['totalorders'] = UserOrder::where('customer_id', Auth::guard('customer')->user()->id)->orderBy('id', 'DESC')->count();
        $data['totalwishlist'] = CustomerWishList::where('customer_id', Auth::guard('customer')->user()->id)->orderBy('id', 'DESC')->count();
        $data['orders'] = UserOrder::where('customer_id', Auth::guard('customer')->user()->id)->orderBy('id', 'DESC')->limit(7)->get();
        $data['couseCount'] = CourseEnrolment::where('customer_id', Auth::guard('customer')->user()->id)->where('payment_status', 'completed')->count();
        $data['roomSetting'] = DB::table('user_room_settings')->where('user_id', $data['author']->id)->first();
        $data['roomBookingCount'] = RoomBooking::where('customer_id', Auth::guard('customer')->user()->id)->where('payment_status', 1)->count();

        return view('user-front.customer.dashboard', $data);
    }
    public function roomBookings()
    {
        $authCustomer = Auth::guard('customer')->user();
        $data['author'] = getUser();
        $queryResult['roomBookingInfos'] = RoomBooking::where('customer_id', $authCustomer->id)->orderBy('id', 'DESC')->get();
        $queryResult['langInfo'] = $this->getUserCurrentLanguage($data['author']->id);
        return view('user-front.customer.room-bookings', $queryResult);
    }
    public function roomBookingDetails($username, $id)
    {
        $roomBooking = RoomBooking::findOrFail($id);
        $queryResult['details'] = $roomBooking;

        $queryResult['userInfo'] = $roomBooking->roomBookedByUser()->firstOrFail();

        return view('user-front.customer.room-booking-details', $queryResult);
    }
    public function editProfile()
    {
        $user = getUser();
        $queryResult['authUser'] = Auth::guard('customer')->user();
        return view('user-front.customer.edit-profile', $queryResult);
    }

    public function updateProfile(Request $request)
    {
        $img = $request->file('image');
        $allowedExts = array('jpg', 'png', 'jpeg');
        $messages = [
            'image.required' => 'The image field is required',
        ];
        $rules = [
            'image' => [
                function ($attribute, $value, $fail) use ($img, $allowedExts) {
                    if (!empty($img)) {
                        $ext = $img->getClientOriginalExtension();
                        if (!in_array($ext, $allowedExts)) {
                            return $fail("Only png, jpg, jpeg image is allowed");
                        }
                    }
                },
            ],
        ];
        $request->validate($rules, $messages);
        $authUser = Auth::guard('customer')->user();
        if ($request->hasFile('image')) {
            // first, delete the previous image from local storage
            @unlink(public_path('assets/user/img/users/' . $authUser->image));
            // second, set a name for the new image and store it to local storage
            $proPic = $request->file('image');
            $picName = time() . '.' . $proPic->getClientOriginalExtension();
            $directory = public_path('assets/user/img/users/');
            @mkdir($directory, 0775, true);
            $proPic->move($directory, $picName);
        }
        $authUser->update($request->except('image') + [
            'image' => $request->exists('image') ? $picName : $authUser->image
        ]);
        $request->session()->flash('success', 'Your profile updated successfully.');
        return redirect()->back();
    }

    public function slider($domain, Request $request)
    {
        $filename = null;
        $request->validate([
            'file' => 'mimes:jpg,jpeg,png|required',
        ]);
        if ($request->hasFile('file')) {
            $filename = Uploader::upload_picture(public_path('assets/user/img/ads/slider-images'), $request->file('file'));
        }
        return response()->json(['status' => 'success', 'file_id' => $filename]);
    }

    public function changePassword()
    {
        $data['authUser'] = Auth::guard('customer')->user();
        return view('user-front.customer.change-password', $data);
    }

    public function updatePassword(Request $request)
    {
        $rules = [
            'current_password' => [
                'required',
                function ($attribute, $value, $fail) {
                    if (!Hash::check($value, Auth::guard('customer')->user()->password)) {
                        $fail('Your password was not updated, since the provided current password does not match.');
                    }
                }
            ],
            'new_password' => 'required|confirmed',
            'new_password_confirmation' => 'required'
        ];

        $messages = [
            'new_password.confirmed' => 'Password confirmation failed.',
            'new_password_confirmation.required' => 'The confirm new password field is required.'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator);
        }
        $user = Auth::guard('customer')->user();
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);
        $request->session()->flash('success', 'Password updated successfully.');
        return redirect()->back();
    }
    public function logoutSubmit(Request $request, $domain)
    {
        Auth::guard('customer')->logout();
        return redirect()->route('customer.login', getParam());
    }

    public function shippingdetails($domain)
    {
        $user = getUser();
        $userShop = UserShopSetting::where('user_id', $user->id)->first();
        if (!empty($userShop) && ($userShop->is_shop == 0 || $userShop->catalog_mode == 1)) {
            return back();
        }
        $user = Auth::guard('customer')->user();
        return view('user-front.customer.shipping_details', compact('user'));
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
        Auth::guard('customer')->user()->update($request->all());
        Session::flash('success', 'Shipping Details Update Successfully.');
        return back();
    }
    public function billingdetails()
    {
        $user = getUser();
        $userShop = UserShopSetting::where('user_id', $user->id)->first();
        if (!empty($userShop) && ($userShop->is_shop == 0 || $userShop->catalog_mode == 1)) {
            return back();
        }
        Auth::guard('customer')->user();
        return view('user-front.customer.billing_details', compact('user'));
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
        Auth::guard('customer')->user()->update($request->all());
        Session::flash('success', 'Billing Details Update Successfully.');
        return back();
    }

    public function customerOrders($domain)
    {

        $user = getUser();
        $userShop = UserShopSetting::where('user_id', $user->id)->first();
        if (!empty($userShop) && ($userShop->is_shop == 0 || $userShop->catalog_mode == 1)) {
            return back();
        }

        $bex = UserShopSetting::where('user_id', $user->id)->first();
        if ($bex->is_shop == 0) {
            return back();
        }
        $data['orders'] = UserOrder::where('customer_id', Auth::guard('customer')->user()->id)->orderBy('id', 'DESC')->get();
        return view('user-front.customer.order', $data);
    }

    public function customerWishlist($domain)
    {
        $user = getUser();
        if (session()->has('user_lang') && !empty($user)) {
            $data['language'] = Language::where('code', session()->get('user_lang'))->where('user_id', $user->id)->first();
            if (empty($data['language'])) {
                $data['language'] = Language::where('is_default', 1)->where('user_id', $user->id)->first();
                session()->put('user_lang', $data['language']->code);
            }
        } else {
            $data['language'] = Language::where('is_default', 1)->where('user_id', $user->id)->first();
        }

        $data['wishlist'] = CustomerWishList::where('customer_id', Auth::guard('customer')->user()->id)
            ->with('item.itemContents')
            ->orderBy('id', 'DESC')->get();
        return view('user-front.customer.wishlist', $data);
    }

    public function removefromWish($domain, $id)
    {
        $data['wishlist'] = CustomerWishList::findOrFail($id)->delete();
        return response()->json(['message' => 'Item removed from wishlist successfully']);
    }

    public function paymentInstruction(Request $request)
    {
        $user = getUser();
        $offline = UserOfflineGateway::where('user_id', $user->id)->where('name', $request->name)
            ->select('short_description', 'instructions', 'is_receipt')
            ->first();
        return response()->json([
            'description' => $offline->short_description,
            'instructions' => $offline->instructions ?? '', 'is_receipt' => $offline->is_receipt
        ]);
    }


    public function myCourses($domain)
    {
        $user = getUser();
        $language = $this->getUserCurrentLanguage($user->id);
        // $queryResult['bgImg'] = $this->getUserBreadcrumb($user->id);

        $customer = Auth::guard('customer')->user();

        $enrols = $customer->courseEnrolment()->where(function ($query) {
            $query->where('payment_status', 'completed')
                ->orWhere('payment_status', 'free');
        })->where('user_id', $user->id)->orderByDesc('id')->get();

        $enrols->map(function ($enrol) use ($language, $user) {
            $course = $enrol->course()->where('user_id', $user->id)->first();
            $courseInfo = $course->courseInformation()->where('language_id', $language->id)->where('user_id', $user->id)->first();

            if (empty($courseInfo)) {
                $language = Language::where('is_default', 1)->where('user_id', $user->id)->first();
                $courseInfo = $course->courseInformation()->where('language_id', $language->id)->where('user_id', $user->id)->first();
            }

            $enrol['title'] = CourseInformation::query()->where('language_id', '=', $language->id)
                ->where('user_id', $user->id)
                ->where('course_id', '=', $course->id)
                ->pluck('title')
                ->first();

            $enrol['slug'] = CourseInformation::query()->where('language_id', '=', $language->id)
                ->where('user_id', $user->id)
                ->where('course_id', '=', $course->id)
                ->pluck('slug')
                ->first();

            $module = !empty($courseInfo) ? $courseInfo->module()->where('user_id', $user->id)->where('status', 'published')->first() : NULL;
            $lesson = !empty($module) ? $module->lesson()->where('user_id', $user->id)->where('status', 'published')->first() : NULL;
            $enrol['lesson_id'] = !empty($lesson) ? $lesson->id : NULL;
        });

        $queryResult['enrolments'] = $enrols;

        return view('user-front.customer.course_management.my-courses', $queryResult);
    }

    public function purchaseHistory()
    {
        $user = getUser();
        $language = $this->getUserCurrentLanguage($user->id);

        $customer = Auth::guard('customer')->user();

        $enrols = $customer->courseEnrolment()->orderByDesc('id')->get();

        $enrols->map(function ($enrol) use ($language) {
            $course = $enrol->course()->first();
            $courseInfo = $course->courseInformation()->where('language_id', $language->id)->first();

            if (empty($courseInfo)) {
                $language = Language::where('is_default', 1)->first();
                $courseInfo = $course->courseInformation()->where('language_id', $language->id)->first();
            }
            if (!empty($courseInfo)) {
                $enrol['title'] = $courseInfo->title;
                $enrol['slug'] = $courseInfo->slug;
            }
        });

        $queryResult['allPurchase'] = $enrols;

        return view('user-front.customer.course_management.purchase-history', $queryResult);
    }

    public function curriculum(Request $request, $domain, $id)
    {
        if (!Auth::guard('customer')->check() && !Auth::guard('web')->check()) {
            return redirect()->route('customer.login', getParam());
        }

        $user = getUser();
        $language = $this->getUserCurrentLanguage($user->id);

        if (Auth::guard('customer')->check()) {
            $enrolCount = CourseEnrolment::where('customer_id', Auth::guard('customer')->user()->id)->where('course_id', $id)->where('user_id', $user->id)->count();
            if ($enrolCount == 0) {
                return redirect()->route('customer.my_courses', getParam());
            }
        }

        $course = Course::find($id);
        $queryResult['certificateStatus'] = $course->certificate_status;

        $courseInfo = $course->courseInformation()->where('language_id', $language->id)->first();
        if (empty($courseInfo)) {
            $language = Language::where('is_default', 1)->first();
            $courseInfo = $course->courseInformation()->where('language_id', $language->id)->first();
        }
        // dd($courseInfo);
        if (!empty($courseInfo)) {

            $queryResult['courseTitle'] = $courseInfo->title;

            $modules = $courseInfo->module()->where('status', 'published')->orderBy('serial_number', 'asc')->get();

            $lessonId = $request['lesson_id'];

            // put lesson id into session to use it in middleware
            $request->session()->put('lessonId', $lessonId);

            $lesson = Lesson::find($lessonId);

            if (!empty($lesson)) {
                $queryResult['lessonTitle'] = $lesson->title;
                $queryResult['lessonContents'] = $lesson->content()->orderBy('order_no', 'asc')->get();

                $queryResult['quizzes'] = $lesson->quiz()->get();
            }

            // update lesson completion status
            $lessonCompleted = false;

            // when certificate system is enabled then execute this code
            if (!empty($lesson)) {
                if ($course->certificate_status == 1) {
                    $totalVideo = $lesson->content()->where('type', 'video')->count();
                    $totalQuiz = $lesson->content()->where('type', 'quiz')->count();

                    if ($course->video_watching == 0 && ($totalVideo > 0 && $totalQuiz == 0)) {
                        // if video watching disabled and, lesson has only video then complete the lesson
                        $lessonCompleted = true;
                    } else if ($course->quiz_completion == 0 && ($totalQuiz > 0 && $totalVideo == 0)) {
                        // if quiz completion disabled and, lesson has only quiz then complete the lesson
                        $lessonCompleted = true;
                    } else if (($course->video_watching == 0 && $course->quiz_completion == 0) && ($totalVideo > 0 && $totalQuiz > 0)) {
                        // if both video watching & quiz completion disabled and, lesson has both video & quiz then complete the lesson
                        $lessonCompleted = true;
                    } else if ($totalVideo == 0 && $totalQuiz == 0) {
                        // if lesson does not have both video & quiz then complete the lesson
                        $lessonCompleted = true;
                    }
                } else {
                    // when certificate system is disabled then execute this code
                    $lessonCompleted = true;
                }

                if (Auth::guard('customer')->check() && $lessonCompleted == true) {
                    $lcCount = LessonComplete::where('customer_id', Auth::guard('customer')->user()->id)->where('lesson_id', $lessonId)->count();
                    if ($lcCount == 0) {
                        $lc = new LessonComplete();
                        $lc->user_id = $user->id;
                        $lc->customer_id = Auth::guard('customer')->user()->id;
                        $lc->lesson_id = $lessonId;
                        $lc->save();
                    }
                }
            }

            $modules->map(function ($module) {
                $module['lessons'] = $module->lesson()->where('status', 'published')->orderBy('serial_number', 'asc')->get();
            });

            $queryResult['modules'] = $modules;
        }
        return view('user-front.customer.course_management.course-curriculum', $queryResult);
    }

    public function downloadFile(Request $request, $domain, $id)
    {
        $user = getUser();
        $bs = BasicSetting::query()
            ->where('user_id', $user->id)
            ->first();
        $content = LessonContent::query()
            ->where('user_id', $user->id)
            ->find($id);
        try {
            return Uploader::downloadFile(Constant::WEBSITE_LESSON_CONTENT_FILE, $content->file_unique_name, $content->file_original_name, $bs);
        } catch (FileNotFoundException $e) {
            session()->flash('error', 'Sorry, this file does not exist anymore!');
            return redirect()->back();
        }
    }
    public function checkAns(Request $request, $domain)
    {
        $user = getUser();
        $id = $request['quizId'];
        $answers = $request['answers'];

        $quiz = LessonQuiz::query()
            ->where('user_id', $user->id)
            ->find($id);
        $qas = json_decode($quiz->answers);

        // find out how many right answer has been selected by admin
        $rightAnsCount = 0;

        foreach ($qas as $qa) {
            if ($qa->rightAnswer == 1) {
                $rightAnsCount++;
            }
        }

        // find out how many correct answer has been given by user
        $correctAnsCount = 0;

        foreach ($answers as $ans) {
            foreach ($qas as $qa) {
                if ($ans == $qa->option && $qa->rightAnswer == 1) {
                    $correctAnsCount++;
                }
            }
        }

        if (($rightAnsCount == $correctAnsCount) && (count($answers) == $rightAnsCount)) {
            return response()->json(['status' => 'Correct']);
        } else {
            return response()->json(['status' => 'Incorrect']);
        }
    }
    public function storeQuizScore(Request $request, $domain)
    {
        $user = getUser();
        $authUser = Auth::guard('customer')->user();
        $courseId = $request['courseId'];
        $lessonId = $request['lessonId'];

        QuizScore::updateOrCreate(
            [
                'user_id' => $user->id,
                'customer_id' => $authUser->id,
                'course_id' => $courseId,
                'lesson_id' => $lessonId
            ],
            ['score' => $request['score']]
        );

        return response()->json(['message' => 'Quiz score stored successfully.']);
    }
    public function contentCompletion(Request $request, $domain)
    {
        $user = getUser();
        $customer = Auth::guard('customer')->user();
        // update lesson-content completion status
        $id = $request['id'];

        $content = LessonContent::find($id);

        if ($content->type == 'video') {
            $lccCount1 = LessonContentComplete::where('customer_id', $customer->id)->where('lesson_id', $content->lesson_id)->where('lesson_content_id', $id)->where('type', 'video')->count();
            if ($lccCount1 == 0) {
                $lcc = new LessonContentComplete;
                $lcc->user_id = $user->id;
                $lcc->customer_id = $customer->id;
                $lcc->lesson_id = $content->lesson_id;
                $lcc->lesson_content_id = $id;
                $lcc->type = 'video';
                $lcc->save();
            }
        }

        // update lesson completion status
        $videoCompleted = false;
        $quizCompleted = false;
        $lessonCompleted = false;

        $courseId = (int)$request['courseId'];
        $course = Course::find($courseId);

        $lessonId = (int)$request['lessonId'];
        $lesson = Lesson::find($lessonId);

        // if video watching enabled then execute this code
        if ($course->video_watching == 1) {
            $totalVideo = $lesson->content()->where('type', 'video')->count();

            if ($totalVideo > 0) {
                $totalCompletedVideo = LessonContentComplete::where('lesson_id', $lessonId)->where('customer_id', $customer->id)->where('user_id', $user->id)->where('type', 'video')->count();

                if ($totalVideo <= $totalCompletedVideo) {
                    $videoCompleted = true;
                }
            } else {
                $videoCompleted = true;
            }
        }

        // if quiz completion enabled then execute this code
        if ($course->quiz_completion == 1) {
            $totalQuiz = $lesson->content()->where('type', 'quiz')->count();
            $quizScore = QuizScore::select('score')->where('course_id', $courseId)->where('lesson_id', $lessonId)->where('customer_id', $customer->id)->first();

            if ($totalQuiz > 0) {
                if (!empty($quizScore) && $quizScore->score >= $course->min_quiz_score) {
                    $quizCompleted = true;
                }
            } else {
                $quizCompleted = true;
            }

            if ($content->type == 'quiz' && $quizCompleted == true) {
                $lccCount2 = LessonContentComplete::where('customer_id', $customer->id)->where('lesson_id', $content->lesson_id)->where('lesson_content_id', $id)->where('type', 'quiz')->count();
                if ($lccCount2 == 0) {
                    $lcc  = new LessonContentComplete();
                    $lcc->user_id = $user->id;
                    $lcc->customer_id = $customer->id;
                    $lcc->lesson_id = $content->lesson_id;
                    $lcc->lesson_content_id = $id;
                    $lcc->type = 'quiz';
                    $lcc->save();
                }
            }
        }

        if (($course->video_watching == 1 && $course->quiz_completion == 0) && $videoCompleted == true) {
            // only video watching enabled, and watched all the videos
            $lessonCompleted = true;
        } else if (($course->video_watching == 0 && $course->quiz_completion == 1) && $quizCompleted == true) {
            // only quiz completion enabled, and passed the quizzes
            $lessonCompleted = true;
        } else if (($course->video_watching == 1 && $course->quiz_completion == 1) && ($videoCompleted == true && $quizCompleted == true)) {
            // both video watching & quiz completion enabled, and both is completed
            $lessonCompleted = true;
        } else if ($course->video_watching == 0 && $course->quiz_completion == 0) {
            // both video watching & quiz completion disabled
            $lessonCompleted = true;
        }

        if ($lessonCompleted == true) {
            $lcCount = LessonComplete::where('customer_id', $customer->id)->where('user_id', $user->id)->where('lesson_id', $lessonId)->count();
            if ($lcCount == 0) {
                $lc = new LessonComplete();
                $lc->user_id = $user->id;
                $lc->customer_id = $customer->id;
                $lc->lesson_id = $lessonId;
                $lc->save();
            }
        }

        return response()->json(['status' => 'Success', 'lessonCompleted' => $lessonCompleted, 'videoCompleted' => $videoCompleted], 200);
    }

    public function getCertificate($domain, $id)
    {
        $user = getUser();

        $courseCompleted = false;

        $language = $this->getUserCurrentLanguage($user->id);

        $course = Course::query()->where('user_id', $user->id)->find($id);
        $permissions = UserPermissionHelper::packagePermission($user->id);
        $permissions = json_decode($permissions, true);
        $queryResult['certificateStatus'] = $course->certificate_status;
        // || (!empty($permissions) && !in_array('Course Completion Certificate', $permissions))
        if ($course->certificate_status != 1) {
            return back();
        }

        $courseInfo = CourseInformation::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('language_id', $language->id)
            ->first();

        if (empty($courseInfo)) {
            Session::flash('warning', 'No Information Found!');
            return back();
        }
        $modules = Module::query()
            ->where('course_information_id', $courseInfo->id)
            ->where('user_id', $user->id)
            ->where('status', 'published')
            ->orderBy('serial_number', 'ASC')
            ->get();

        foreach ($modules as $module) {
            $lessons = Lesson::query()
                ->where('module_id', $module->id)
                ->where('status', 'published')
                ->orderBy('serial_number', 'ASC')
                ->get();

            foreach ($lessons as $lesson) {
                if ($lesson->lesson_complete()->where('customer_id', Auth::guard('customer')->user()->id)->count() > 0) {
                    $courseCompleted = true;
                } else {
                    $courseCompleted = false;
                    break 2;
                }
            }
        }

        if ($courseCompleted == true) {
            $queryResult['certificateTitle'] = $course->certificate_title;
            $certificateText = $course->certificate_text;

            // get student name
            $authUser = Auth::guard('customer')->user();
            $studentName = $authUser->first_name . ' ' . $authUser->last_name;

            // get course duration
            $duration = Carbon::parse($course->duration);
            $courseDuration = $duration->format('h') . ' hours';

            // get course title
            $courseTitle = $courseInfo->title;

            // get course completion date
            $date = Carbon::now();
            $completionDate = date_format($date, 'F d, Y');

            $certificateText = str_replace('{name}', $studentName, $certificateText);
            $certificateText = str_replace('{duration}', $courseDuration, $certificateText);
            $certificateText = str_replace('{title}', $courseTitle, $certificateText);
            $certificateText = str_replace('{date}', $completionDate, $certificateText);

            $queryResult['certificateText'] = $certificateText;

            $queryResult['instructorInfo'] = $courseInfo->instructorInfo()
                ->where('language_id', $language->id)
                ->where('user_id', $user->id)
                ->select('name', 'occupation')
                ->first();

            return view('user-front.customer.course_management.course-certificate', $queryResult);
        } else {
            return redirect()->back()->with('warning', 'You have to complete this course to get the certificate.');
        }
    }

    public function donations()
    {
        $user = getUser();
        $language = $this->getUserCurrentLanguage($user->id);
        $settings = DB::table('user_donation_settings')->where('user_id', $user->id)->first();
        if ($settings->is_donation == 0) {
            return back();
        }

        $donations = DonationDetail::where('customer_id', Auth::guard('customer')->user()->id)->orderBy('id', 'DESC')->get();
        $donations->map(function ($donation) use ($language) {
            $title = DonationContent::where([['donation_id', $donation->donation_id], ['language_id', $language->id]])->select('title', 'slug')->first();
            $donation['title'] = $title->title;
            $donation['slug'] = $title->slug;
        });

        return view('user-front.customer.donations', compact('donations'));
    }
}
