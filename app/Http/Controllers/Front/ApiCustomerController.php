<?php

namespace App\Http\Controllers\Front;

use Config;
use App\Models\ApiCustomer;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\User\UserOrder;
use App\Models\User\BasicSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Models\User\CustomerWishList;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Models\ApiCustomerPropertyInterested;
use App\Models\User\HotelBooking\RoomBooking;
use App\Models\User\RealestateManagement\Property;
use App\Models\User\CourseManagement\CourseEnrolment;
use App\Models\User\RealestateManagement\PropertyWishlist;

class ApiCustomerController extends Controller
{
    public function __construct()
    {
        // Skip everything if we're in Artisan / console.
        if (app()->runningInConsole()) {
            return;
        }

        $user = getUser(); // Assuming getUser() returns the authenticated user or context
        $userBs = BasicSetting::where('user_id', $user->id)->first();
        Config::set('captcha.sitekey', $userBs->google_recaptcha_site_key);
        Config::set('captcha.secret', $userBs->google_recaptcha_secret_key);
    }


    public function checkIdentifier(Request $request)
    {

        $request->validate([
            'identifier' => 'required|string|max:191',
        ]);
        $identifier = $request->input('identifier');

        $exists = ApiCustomer::where('email', $identifier)
            ->orWhere('phone_number', $identifier)
            ->exists();

        return response()->json([
            'exists' => $exists,
            'message' => $exists ? 'User exists.' : 'No account found, please register.'
        ]);

    }

    /**
     * Show the signup form.
     *
     * @param string $domain
     * @return \Illuminate\View\View
     */
    public function signup($domain)
    {
        $user = getUser();
        // dd($user);
        return view('user-front.customer.api_signup', compact('user'));
    }

    /**
     * Handle signup form submission.
     *
     * @param Request $request
     * @param string $domain
     * @return \Illuminate\Http\RedirectResponse
     */
    public function signupSubmit(Request $request, $domain)
    {
        $user = getUser();

        $identifier = $request->input('identifier');
        // Determine if it's email or phone
        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);

        $rules = [
            'name' => 'required|max:255',
            'identifier' => [
                'required',
                function ($attribute, $value, $fail) use ($user, $isEmail) {
                    $column = $isEmail ? 'email' : 'phone_number';
                    if (ApiCustomer::where($column, $value)->where('user_id', $user->id)->exists()) {
                        $fail(ucfirst($column) . ' has already been taken.');
                    }
                }
            ],
            'password' => 'required',
        ];

        // reCAPTCHA optional
        $messages = [];
        $ubs = BasicSetting::where('user_id', $user->id)->first();
        if ($ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! Try again later or contact site admin.',
            ];
        }

        $request->validate($rules, $messages);

        $customer = new ApiCustomer();
        $customer->user_id = $user->id;
        $customer->name = $request->name;

        if ($isEmail) {
            $customer->email = $identifier;
        } else {
            $customer->phone_number = $identifier;
        }

        $customer->password = Hash::make($request->password);
        $customer->save();

        Auth::guard('api_customer')->login($customer);
        if ($request->filled('property_id')) {
            $propertyId = $request->property_id;
            $customerId = $customer->id;
            $userId = $user->id;

            $already = ApiCustomerPropertyInterested::where('customer_id', $customerId)
                ->where('property_id', $propertyId)
                ->first();

            if (!$already) {
                $property = Property::select('id', 'category_id')->find($propertyId);
                if ($property) {
                    ApiCustomerPropertyInterested::create([
                        'user_id'     => $userId,
                        'customer_id' => $customerId,
                        'property_id' => $property->id,
                        'category_id' => $property->category_id,
                    ]);
                }
            }
        }

        Session::flash('success', 'Registration successful.');
        return redirect()->back();
    }

    /**
     * Show the login form.
     *
     * @param string $domain
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function login($domain, Request $request)
    {
        $user = getUser();
        return view('user-front.customer.api_login', compact('user'));
    }

    /**
     * Handle login form submission.
     *
     * @param Request $request
     * @param string $domain
     * @return \Illuminate\Http\RedirectResponse
     */

    public function loginSubmit(Request $request, $domain)
    {
        \Log::info('Login attempt', [
            'identifier' => $request->identifier,
            'user_id' => getUser()->id,
        ]);

        $rules = [
            'identifier' => 'required|string',
            'password' => 'required',
        ];

        $messages = [];
        $ubs = BasicSetting::where('user_id', getUser()->id)->first();
        if ($ubs && $ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            ];
        }

        $request->validate($rules, $messages);

        $customer = ApiCustomer::where('email', $request->identifier)
            ->orWhere('phone_number', $request->identifier)
            ->where('user_id', getUser()->id)
            ->first();

        \Log::info('Customer query result', [
            'customer' => $customer ? $customer->toArray() : null,
        ]);

        if ($customer && Hash::check($request->password, $customer->password)) {
            \Log::info('Password check passed');
            Auth::guard('api_customer')->login($customer);

            if ($request->filled('property_id')) {
                $propertyId = $request->property_id;
                $userId = getUser()->id;
                $customerId = $customer->id;

                $already = ApiCustomerPropertyInterested::where('customer_id', $customerId)
                    ->where('property_id', $propertyId)
                    ->first();

                if (!$already) {
                    $property = Property::select('id', 'category_id')->find($propertyId);
                    if ($property) {
                        ApiCustomerPropertyInterested::create([
                            'user_id'     => $userId,
                            'customer_id' => $customerId,
                            'property_id' => $property->id,
                            'category_id' => $property->category_id,
                        ]);

                        \Log::info('Property added to interested list automatically after login', [
                            'customer_id' => $customerId,
                            'property_id' => $propertyId,
                        ]);
                    }
                }
            }

            \Log::info('Login successful, redirecting');
            if ($request->session()->has('link')) {
                $redirectURL = $request->session()->get('link');
                $request->session()->forget('link');
                return redirect($redirectURL);
            }

            Session::flash('success', 'You have been logged in successfully.');
            return redirect()->back();
        } else {
            \Log::info('Credentials invalid', [
                'customer_exists' => !is_null($customer),
                'password_check' => $customer ? Hash::check($request->password, $customer->password) : false,
            ]);
            $request->session()->flash('error', 'The provided credentials do not match our records!');
            return redirect()->back();
        }
    }


    // redirectToApiDashboard
    public function redirectToApiDashboard()
    {
        // dd('Redirecting to API Dashboard');
        $data['author'] = getUser();
        $data['language'] = $this->getUserCurrentLanguage($data['author']->id);
        $data['authUser'] = Auth::guard('api_customer')->user();
        $data['totalorders'] = UserOrder::where('customer_id', Auth::guard('api_customer')->user()->id)->orderBy('id', 'DESC')->count();
        $data['totalwishlist'] = CustomerWishList::where('customer_id', Auth::guard('api_customer')->user()->id)->orderBy('id', 'DESC')->count();
        $data['orders'] = UserOrder::where('customer_id', Auth::guard('api_customer')->user()->id)->orderBy('id', 'DESC')->limit(7)->get();
        $data['couseCount'] = CourseEnrolment::where('customer_id', Auth::guard('api_customer')->user()->id)->where('payment_status', 'completed')->count();
        $data['roomSetting'] = DB::table('user_room_settings')->where('user_id', $data['author']->id)->first();
        $data['roomBookingCount'] = RoomBooking::where('customer_id', Auth::guard('api_customer')->user()->id)->where('payment_status', 1)->count();
        $data['propertyWishlistsCount'] = PropertyWishlist::where('customer_id', Auth::guard('api_customer')->user()->id)->count();

        return view('user-front.customer.api_dashboard', $data);

    }

    // logoutSubmit
    public function logoutApiSubmit(Request $request)
    {
        Auth::guard('api_customer')->logout();
        Session::flash('success', 'You have been logged out successfully.');
        return redirect()->back();
    }
    // forgotPassword
    public function forgotPassword(Request $request)
    {
        $user = getUser();
        return view('user-front.customer.api_forgot_password', compact('user'));
    }
    /**
     * Handle forgot password form submission.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function forgotPasswordSubmit(Request $request)
    {
        $rules = [
            'email' => 'required|email',
        ];
        $messages = [];
        $ubs = BasicSetting::where('user_id', getUser()->id)->first();
        if ($ubs->is_recaptcha == 1) {
            $rules['g-recaptcha-response'] = 'required|captcha';
            $messages = [
                'g-recaptcha-response.required' => 'Please verify that you are not a robot.',
                'g-recaptcha-response.captcha' => 'Captcha error! try again later or contact site admin.',
            ];
        }
        $request->validate($rules, $messages);

        $customer = ApiCustomer::where('email', $request->email)
            ->where('user_id', getUser()->id)
            ->first();

        if (!$customer) {
            Session::flash('error', 'No account found with that email address.');
            return redirect()->back();
        }

        // Generate a password reset token
        $token = Str::random(60);

        // Store the token in the database (assuming you have a password_resets table)
        \DB::table('password_resets')->updateOrInsert(
            ['email' => $customer->email],
            [
                'email' => $customer->email,
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Send reset email (assuming you have a mailable set up)
        try {
            \Mail::send('emails.api_customer_password_reset', ['token' => $token, 'email' => $customer->email], function ($message) use ($customer) {
                $message->to($customer->email);
                $message->subject('Reset Your Password');
            });
        } catch (\Exception $e) {
            Session::flash('error', 'Failed to send reset email. Please try again later.');
            return redirect()->back();
        }

        Session::flash('success', 'A password reset link has been sent to your email address.');
        return redirect()->back();
    }

    // forgotPassword
    public function resetPassword($token, $email)
    {
        $user = getUser();
        return view('user-front.customer.api_reset_password', compact('token', 'email', 'user'));
    }


}
