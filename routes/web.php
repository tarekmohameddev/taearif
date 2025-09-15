<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Main web routes file that includes all organized route files
|
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\CRM\SaleController;
use App\Http\Controllers\CRM\BookingController;
use App\Http\Controllers\User\RegionController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\CRM\ReservationController;
use App\Http\Controllers\TenantDashboardController;
use App\Http\Controllers\User\OnboardingController;
use App\Http\Controllers\CRM\PaymentRecordController;
use App\Http\Controllers\Admin\RegisterUserController;
use App\Http\Controllers\Front\UserDistrictController;
use App\Http\Controllers\User\HotelBooking\RoomManagementController;
use App\Http\Controllers\Front\ProjectController as FrontProjectController;
use App\Http\Controllers\Front\PropertyController as FrontPropertyController;
use App\Http\Controllers\User\RealestateManagement\ManageProject\TypeController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\CityController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\StateController;
use App\Http\Controllers\User\RealestateManagement\ManageProject\ProjectController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\AmenityController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\CountryController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\CategoryController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\PropertyController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\PropertyMessageController;
use App\Http\Controllers\User\RealestateManagement\ManageProperty\PropertyRequestController;

// Include debug and admin routes (these don't need domain constraints)
require __DIR__ . '/web/debug.php';
require __DIR__ . '/web/admin.php';

// Admin-specific frontend route (without username parameter)
Route::get('/admin-frontend', 'Front\FrontendController@index')->name('admin.front.index');

// Admin secret login route (accessible from admin panel)
Route::middleware(['auth:admin', 'can:impersonate-users'])->group(function () {
    Route::get('admin/register/user/{user}/secret-login', 'Admin\RegisterUserController@secretLogin')->name('admin.register.user.secretLogin');
});

// Fallback route
Route::fallback(function () {
    return view('errors.404');
})->middleware('setlang');

// Domain and subdomain logic (CRITICAL - DO NOT MODIFY)
$domain = env('WEBSITE_HOST');
if (!app()->runningInConsole()) {
    if (substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
        $domain = 'www.' . env('WEBSITE_HOST');
    }
}

$parsedUrl = parse_url(url()->current());
$host = str_replace("www.", "", $parsedUrl['host']);
if (array_key_exists('host', $parsedUrl)) {
    // if it is a path based URL
    if ($host == env('WEBSITE_HOST')) {
        $domain = $domain;
        $prefix = '/{username}';
    }
    // if it is a subdomain / custom domain
    else {
        if (!app()->runningInConsole()) {
            if (substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
                $domain = 'www.{domain}';
            } else {
                $domain = '{domain}';
            }
        }
        $prefix = '';
    }
}

// Multi-tenant routes with domain logic
Route::group(['domain' => $domain, 'prefix' => $prefix], function () {
    // Language change route
    Route::get('/changelanguage/{lang}', 'Front\FrontendController@changeLanguage')->name('changeLanguage');
    
    // Cron job routes
    Route::get('/expired', 'CronJobController@expired')->name('cron.expired');
    Route::get('/expiry-reminder', 'CronJobController@expired')->name('cron.expired');
    
    // Main website routes with language middleware
    Route::group(['middleware' => 'setlang'], function () {
        Route::get('/', 'Front\FrontendController@index')->name('front.index');
        Route::get('/solutions', function () {
            return view('front.solutions');
        })->name('front.solutions');
        Route::get('/updates', function () {
            return view('front.updates');
        })->name('front.updates');
        Route::get('/about-us', function () {
            return view('front.about_us');
        })->name('front.about_us');
        Route::get('/privacy', function () {
            return view('front.privacy');
        })->name('front.privacy');
        Route::get('/templates', 'Front\FrontendController@templates')->name('front.templates');
        Route::get('/vcards', 'Front\FrontendController@vcards')->name('front.vcards');
        Route::post('/subscribe', 'Front\FrontendController@subscribe')->name('front.subscribe');
        Route::get('/listings', 'Front\FrontendController@users')->name('front.user.view');
        Route::get('/realestate', 'Front\FrontendController@realestate')->name('front.realestate');
        Route::get('/contact', 'Front\FrontendController@contactView')->name('front.contact');
        Route::get('/faq', 'Front\FrontendController@faqs')->name('front.faq.view');
        Route::get('/blog', 'Front\FrontendController@blogs')->name('front.blogs');
        Route::get('/pricing', 'Front\FrontendController@pricing')->name('front.pricing');
        Route::get('/blog-details/{slug}/{id}', 'Front\FrontendController@blogdetails')->name('front.blogdetails');
        Route::get('/registration/step-1/{status}/{id}', 'Front\FrontendController@step1')->name('front.register.view');
        Route::get('/check/{username}/username', 'Front\FrontendController@checkUsername')->name('front.username.check');
        Route::get('/p/{slug}', 'Front\FrontendController@dynamicPage')->name('front.dynamicPage');
        Route::get('/success', 'Front\CheckoutController@onlineSuccess')->name('success.page');
        Route::get('/failed', 'Front\CheckoutController@onlinefailed')->name('failed.page');
    });
    
    // Guest auth routes with language middleware
    Route::group(['middleware' => ['web', 'guest', 'setlang']], function () {
        Route::get('/registration/final-step', 'Front\FrontendController@step2')->name('front.registration.step2');
        Route::post('/checkout', 'Front\FrontendController@checkout')->name('front.checkout.view');
        Route::get('/login', 'User\Auth\LoginController@showLoginForm')->name('user.login');
        Route::post('/login', 'User\Auth\LoginController@login')->name('user.login.submit');
        Route::get('/register', 'User\Auth\RegisterController@registerPage')->name('user-register');
        Route::post('/register/submit', 'User\Auth\RegisterController@register')->name('user-register-submit')->middleware('Demo');
        Route::get('/register/mode/{mode}/verify/{token}', 'User\Auth\RegisterController@token')->name('user-register-token');
        Route::post('/password/email', 'User\Auth\ForgotPasswordController@sendResetLinkEmail')->name('user.forgot.password.submit')->middleware('Demo');
        Route::get('/password/reset', 'User\Auth\ForgotPasswordController@showLinkRequestForm')->name('user.forgot.password.form');
        Route::post('/password/reset', 'User\Auth\ResetPasswordController@reset')->name('user.reset.password.submit')->middleware('Demo');
        Route::get('/password/reset/{token}/email/{email}', 'User\Auth\ResetPasswordController@showResetForm')->name('user.reset.password.form');
        Route::get('/forgot', 'User\ForgotController@showforgotform')->name('user-forgot');
        Route::post('/forgot', 'User\ForgotController@forgot')->name('user-forgot-submit')->middleware('Demo');
    });
    
    // User routes with authentication
    Route::group(['prefix' => 'user', 'middleware' => ['auth', 'userstatus', 'Demo']], function () {
        // Include all user routes
        require __DIR__ . '/web/user.php';
    });
    
    // Include tenant-specific routes (properties, projects, etc.)
    require __DIR__ . '/web/tenant.php';
    
    // Include customer routes
    require __DIR__ . '/web/customer.php';
    
    // Include payment routes
    require __DIR__ . '/web/payment.php';
    
    // Additional routes that were in the working Desktop version
    Route::post('/track-visitor', 'Front\FrontendController@get_info')->name('front.track.data');
    Route::get('/stats', 'Front\FrontendController@getStats')->name('front.getStats');
    
    // Geo routes
    Route::prefix('geo')->name('front.geo.')->group(function () {
        Route::get('cities', 'User\RegionController@cities')->name('cities');
        Route::get('districts', 'Front\UserDistrictController@index')->name('districts.index');
        Route::get('districts/by-city/{cityId}', 'Front\UserDistrictController@districtsByCity')->whereNumber('cityId')->name('districts.byCity');
    });
    
    // Onboarding routes
    Route::middleware(['auth'])->group(function () {
        Route::get('/onboarding', 'User\OnboardingController@index')->name('onboarding.index');
        Route::post('/onboarding/store', 'User\OnboardingController@store')->name('onboarding.store');
        Route::get('/onboarding/show-step2', 'User\OnboardingController@showStep2')->name('onboarding.showStep2');
        Route::post('/onboarding/step2', 'User\OnboardingController@step2')->name('onboarding.step2');
        Route::get('/onboarding/skip', 'User\OnboardingController@skip')->name('onboarding.skip');
    });
    
    // Additional utility routes
    Route::get('/get-states/{city_id}', 'Front\PropertyController@getStatesByCity')->name('front.user.get_states');
    Route::get('/data', 'TenantDashboardController@dashboard');
    
    // Impersonation routes (admin routes are defined in web/admin.php)
    
    Route::get('/_impersonate/{user}', 'ImpersonationController@consume')->name('impersonate.consume')->middleware('signed');
    Route::post('/_impersonate/stop', 'ImpersonationController@stop')->name('impersonate.stop')->middleware('auth');
    Route::post('/impersonate/{user}', 'ImpersonationController@start');
    Route::post('/impersonate/{user}/revoke', 'ImpersonationController@stop');
    
    // Token login
    Route::get('/login', 'Admin\TokenLoginController@loginByToken')->name('login.by.token');
    
    // Payment gateway callbacks
    Route::get('/midtrans/bank-notify', 'MidtransBankNotifyController@bank_notify')->name('midtrans.bank_notify');
    Route::get('/midtrans/cancel', 'MidtransBankNotifyController@cancel')->name('midtrans.cancel');
    Route::get('/myfatoorah/callback', 'MyFatoorahController@callback')->name('myfatoorah.success');
    Route::get('myfatoorah/cancel', 'MyFatoorahController@cancel')->name('myfatoorah.cancel');
    Route::post('/mf/app/success', 'Webhook\MyFatoorahWebhookController@handle')->name('mf.app.success');
    Route::post('/mf/app/cancel', fn() => response('cancel', 200))->name('mf.app.cancel');
    
    // Contract routes
    Route::get('/contractsign', 'ContractController@contractsign')->name('contractsign');
    Route::resource('contracts', 'ContractController');
    Route::post('/contracts/{contract}/sign', 'ContractController@sign')->name('contracts.sign');
    Route::get('/contracts/{contract}/download', 'ContractController@downloadPDF')->name('contracts.download');
    Route::get('/contracts/{contract}/{action}', 'ContractController@handleAction')->where('action', 'print|send|reminder|cancel|renew')->name('contracts.action');
    
    // CRM routes
    Route::prefix('crm')->name('crm.')->group(function () {
        Route::resource('sales', 'CRM\SaleController');
        Route::resource('reservations', 'CRM\ReservationController');
    });
    Route::resource('payment-records', 'CRM\PaymentRecordController');
    Route::resource('bookings', 'CRM\BookingController');
    
    // Cron job routes
    Route::get('/subcheck', 'CronJobController@expired')->name('cron.expired');
    Route::get('/check-payment', 'CronJobController@check_payment')->name('cron.check_payment');
});

// Second domain group for subdomain handling (from old version)
$parsedUrl = parse_url(url()->current());
$host = str_replace("www.", "", $parsedUrl['host']);
if (array_key_exists('host', $parsedUrl)) {
    // if it is a path based URL
    if ($host == env('WEBSITE_HOST')) {
        $domain = $domain;
        $prefix = '/{username}';
    }
    // if it is a subdomain / custom domain
    else {
        if (!app()->runningInConsole()) {
            if (substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
                $domain = 'www.{domain}';
            } else {
                $domain = '{domain}';
            }
        }
        $prefix = '';
    }
}

Route::group(['domain' => $domain, 'prefix' => $prefix], function () {
    Route::get('/', 'Front\FrontendController@userDetailView')->name('front.user.detail.view');

    // Properties route
    Route::prefix('property-requests')->middleware(['web'])->group(function () {
        Route::get('/create', [PropertyRequestController::class, 'create'])->name('front.user.property-requests.create');
        Route::post('/store', [PropertyRequestController::class, 'store'])->name('front.user.property-requests.store');
    });

    Route::controller(FrontPropertyController::class)->group(function () {
        Route::get('/properties', 'index')->name('front.user.properties');
        Route::get('/property/{slug}', 'details')->name('front.user.property.details');
        Route::post('/property-contact', 'contact')->name('front.user.property_contact');
        Route::get('/state-cities', 'getStateCities')->name('front.user.get_state_cities');
        Route::get('/cities', 'getCities')->name('front.user.get_cities');
        Route::get('/categories', 'getCategories')->name('front.user.get_categories');
    });

    // Projects route
    Route::controller(FrontProjectController::class)->group(function () {
        Route::get('/projects', 'index')->name('front.user.projects');
        Route::get('/project/{slug}', 'details')->name('front.user.project.details');
        Route::get('/project-info/{id}', [ProjectController::class, 'showJson']);
    });

    Route::group(['middleware' => ['routeAccess:Service']], function () {
        Route::get('/services', 'Front\FrontendController@userServices')->name('front.user.services');
        Route::get('/service/{slug}/{id}', 'Front\FrontendController@userServiceDetail')->name('front.user.service.detail');
    });
    
    Route::group(['middleware' => ['routeAccess:Blog']], function () {
        Route::get('/blogs', 'Front\FrontendController@userBlogs')->name('front.user.blogs');
        Route::get('/blog/{slug}/{id}', 'Front\FrontendController@userBlogDetail')->name('front.user.blog.detail');
    });
    
    Route::group(['middleware' => ['routeAccess:Hotel Booking', 'Demo']], function () {
        Route::get('/rooms', 'Front\RoomController@rooms')->name('front.user.rooms');
        Route::get('/room/{id}/{slug}', 'Front\RoomController@roomDetails')->name('front.user.room_details');
        Route::post('/room/store_review/{id}', 'Front\RoomController@storeReview')->name('front.user.room.store_review');
        Route::post('/room-booking/apply-coupon', 'Front\RoomController@applyCoupon')->name('front.user.apply_coupon');
        Route::post('/room-booking', 'Front\RoomBookingController@makeRoomBooking')->name('front.user.room_booking');
        Route::get('/room_booking/paypal/notify', 'User\Payment\PaypalController@successPayment')->name('front.user.room_booking.notify');
        Route::post('/room_booking/paytm/notify', 'User\Payment\PaytmController@paymentStatus')->name('front.user.room_booking.paytm.notify');
        Route::post('/room_booking/paytm/notify', 'User\Payment\PaytmController@paymentStatus')->name('front.user.room_booking.stripe.notify');
        Route::get('/room_booking/instamojo/notify', 'User\Payment\InstamojoController@successPayment')->name('front.user.room_booking.instamojo.notify');
        Route::get('/room_booking/paystack/notify', 'User\Payment\PaystackController@successPayment')->name('front.user.room_booking.paystack.notify');
        Route::post('/room_booking/flutterwave/notify', 'User\Payment\FlutterWaveController@successPayment')->name('front.user.room_booking.flutterwave.notify');
        Route::get('/room_booking/mollie/notify', 'User\Payment\MollieController@successPayment')->name('front.user.room_booking.mollie.notify');
        Route::post('/room_booking/razorpay/notify', 'User\Payment\RazorpayController@successPayment')->name('front.user.room_booking.razorpay.notify');
        Route::get('/room_booking/mercadopago/notify', 'User\Payment\MercadopagoController@successPayment')->name('front.user.room_booking.mercadopago.notify');
        Route::post('/room_booking/phonepe/notify', 'User\Payment\PhonePeController@successPayment')->name('front.user.room_booking.phonepe.notify');
        Route::get('/room_booking/perfect-money/notify', 'User\Payment\PerfectMoneyController@successPayment')->name('front.user.room_booking.perfect_money.notify');
        Route::get('/room_booking/xendit/notify', 'User\Payment\XenditController@successPayment')->name('front.user.room_booking.xendit.notify');
        Route::get('/room_booking/yoco/notify', 'User\Payment\YocoController@successPayment')->name('front.user.room_booking.yoco.notify');
        Route::get('/room_booking/toyyibpay/notify', 'User\Payment\ToyyibpayController@successPayment')->name('front.user.room_booking.toyyibpay.notify');
        Route::post('/room_booking/paytabs/notify', 'User\Payment\PaytabsController@successPayment')->name('front.user.room_booking.paytabs.notify');
        Route::get('/room_booking/midtrans/notify', 'User\Payment\MidtransController@successPayment')->name('front.user.room_booking.midtrans.notify');
        Route::post('/room_booking/iyzico/notify', 'User\Payment\IyzicoController@successPayment')->name('front.user.room_booking.iyzico.notify');
        Route::get('/room_booking/cancel', 'Front\RoomBookingController@cancel')->name('front.user.room_booking.cancel');
        Route::get('/room_booking/complete', 'Front\RoomBookingController@complete')->name('front.user.room_booking.complete');
    });

    // Course management routes
    Route::group(['middleware' => ['routeAccess:Course Management', 'Demo']], function () {
        Route::get('/courses', 'Front\CourseManagement\CourseController@courses')->name('front.user.courses');
        Route::get('/course/{slug}', 'Front\CourseManagement\CourseController@details')->name('front.user.course.details');
        Route::post('/course-enrolment/apply-coupon', 'Front\CourseManagement\CourseController@applyCoupon')->name('front.user.course.enrolment.apply.coupon');
        Route::post('/course-enrolment/{id}', 'Front\CourseManagement\EnrolmentController@enrolment')->name('front.user.course.enrolment');
        Route::post('/course/{id}/store-feedback', 'Front\CourseManagement\CourseController@storeFeedback')->name('front.user.course.store_feedback');

        // Course enrollment payment gateway routes
        Route::get('/course-enrolment/paypal/notify', 'User\CourseManagement\Payment\PayPalController@notify')->name('course_enrolment.paypal.notify');
        Route::get('/course-enrolment/instamojo/notify', 'User\CourseManagement\Payment\InstamojoController@notify')->name('course_enrolment.instamojo.notify');
        Route::get('/course-enrolment/paystack/notify', 'User\CourseManagement\Payment\PaystackController@notify')->name('course_enrolment.paystack.notify');
        Route::post('/course-enrolment/flutterwave/notify', 'User\CourseManagement\Payment\FlutterwaveController@notify')->name('course_enrolment.flutterwave.notify');
        Route::post('/course-enrolment/razorpay/notify', 'User\CourseManagement\Payment\RazorpayController@notify')->name('course_enrolment.razorpay.notify');
        Route::get('/course-enrolment/mercadopago/notify', 'User\CourseManagement\Payment\MercadoPagoController@notify')->name('course_enrolment.mercadopago.notify');
        Route::get('/course-enrolment/mollie/notify', 'User\CourseManagement\Payment\MollieController@notify')->name('course_enrolment.mollie.notify');
        Route::post('/course-enrolment/paytm/notify', 'User\CourseManagement\Payment\PaytmController@notify')->name('course_enrolment.paytm.notify');
        Route::post('/course-enrolment/phonepe/notify', 'User\CourseManagement\Payment\PhonePeController@notify')->name('course_enrolment.phonepe.notify');
        Route::get('/course-enrolment/perfect-money/notify', 'User\CourseManagement\Payment\PerfectMoneyController@notify')->name('course_enrolment.perfect_money.notify');
        Route::get('/course-enrolment/xendit/notify', 'User\CourseManagement\Payment\XenditController@notify')->name('course_enrolment.xendit.notify');
        Route::get('/course-enrolment/yoco/notify', 'User\CourseManagement\Payment\YocoController@notify')->name('course_enrolment.yoco.notify');
        Route::get('/course-enrolment/toyyibpay/notify', 'User\CourseManagement\Payment\ToyyibpayController@notify')->name('course_enrolment.toyyibpay.notify');
        Route::post('/course-enrolment/paytabs/notify', 'User\CourseManagement\Payment\PaytabsController@notify')->name('course_enrolment.paytabs.notify');
        Route::get('/course-enrolment/midtrans/notify', 'User\CourseManagement\Payment\MidtransController@notify')->name('course_enrolment.midtrans.notify');
        Route::post('/course-enrolment/iyzico/notify', 'User\CourseManagement\Payment\IyzicoController@notify')->name('course_enrolment.iyzico.notify');

        Route::get('/course-enrolment/{id}/complete/{via?}', 'Front\CourseManagement\EnrolmentController@complete')->name('front.user.course_enrolment.complete');
        Route::get('/course-enrolment/{id}/cancel', 'Front\CourseManagement\EnrolmentController@cancel')->name('front.user.course_enrolment.cancel');
    });

    // Donation management routes
    Route::group(['middleware' => ['routeAccess:Donation Management', 'Demo']], function () {
        Route::get('/causes', 'Front\DonationManagement\CauseController@index')->name('front.user.causes');
        Route::get('/cause/{slug}', 'Front\DonationManagement\CauseController@details')->name('front.user.causesDetails');
        Route::post('/cause/payment', 'Front\DonationManagement\DonationController@makePayment')->name('front.user.causes.payment');
        Route::get('/cause-donation/paypal/notify', 'User\DonationManagement\Payment\PayPalController@notify')->name('cause_donation.paypal.notify');
        Route::get('/cause-donation/instamojo/notify', 'User\DonationManagement\Payment\InstamojoController@notify')->name('cause_donate.instamojo.notify');
        Route::get('/cause-donation/paystack/notify', 'User\DonationManagement\Payment\PaystackController@notify')->name('cause_donate.paystack.notify');
        Route::post('/cause-donation/flutterwave/notify', 'User\DonationManagement\Payment\FlutterwaveController@notify')->name('cause_donate.flutterwave.notify');
        Route::post('/cause-donation/razorpay/notify', 'User\DonationManagement\Payment\RazorpayController@notify')->name('cause_donate.razorpay.notify');
        Route::get('/cause-donation/mercadopago/notify', 'User\DonationManagement\Payment\MercadoPagoController@notify')->name('cause_donate.mercadopago.notify');
        Route::get('/cause-donation/mollie/notify', 'User\DonationManagement\Payment\MollieController@notify')->name('cause_donate.mollie.notify');
        Route::post('/cause-donation/paytm/notify', 'User\DonationManagement\Payment\PaytmController@notify')->name('cause_donate.paytm.notify');
        Route::post('/cause-donation/phonepe/notify', 'User\DonationManagement\Payment\PhonePeController@notify')->name('cause_donation.phonepe.notify');
        Route::get('/cause-donation/perfect-money/notify', 'User\DonationManagement\Payment\PerfectMoneyController@notify')->name('cause_donation.perfect_money.notify');
        Route::get('/cause-donation/xendit/notify', 'User\DonationManagement\Payment\XenditController@notify')->name('cause_donation.xendit.notify');
        Route::get('/cause-donation/yoco/notify', 'User\DonationManagement\Payment\YocoController@notify')->name('cause_donation.yoco.notify');
        Route::get('/cause-donation/toyyibpay/notify', 'User\DonationManagement\Payment\ToyyibpayController@notify')->name('cause_donation.toyyibpay.notify');
        Route::post('/cause-donation/paytabs/notify', 'User\DonationManagement\Payment\PaytabsController@notify')->name('cause_donation.paytabs.notify');
        Route::get('/cause-donation/midtrans/notify', 'User\DonationManagement\Payment\MidtransController@notify')->name('cause_donation.midtrans.notify');
        Route::post('/cause-donation/iyzico/notify', 'User\DonationManagement\Payment\IyzicoController@notify')->name('cause_donation.iyzico.notify');
        Route::get('/cause-donation/complete/', 'Front\DonationManagement\DonationController@complete')->name('front.user.cause_donate.complete');
        Route::get('/cause-donation/{id}/cancel', 'Front\DonationManagement\DonationController@cancel')->name('front.user.cause_donate.cancel');
    });

    // Customer authentication routes
    Route::prefix('/user')->middleware(['guest:customer'])->group(function () {
        Route::get('/login',  'Front\CustomerController@login')->name('customer.login');
        Route::post('/login-submit', 'Front\CustomerController@loginSubmit')->name('customer.login_submit');
        Route::get('/forget-password', 'Front\CustomerController@forgetPassword')->name('customer.forget_password');
        Route::post('/send-forget-password-mail', 'Front\CustomerController@sendMail')->name('customer.send_forget_password_mail')->middleware('Demo');
        Route::get('/reset-password', 'Front\CustomerController@resetPassword')->name('customer.reset_password');
        Route::post('/reset-password-submit', 'Front\CustomerController@resetPasswordSubmit')->name('customer.reset_password_submit')->middleware('Demo');
        Route::get('/signup', 'Front\CustomerController@signup')->name('customer.signup');
        Route::post('/signup-submit', 'Front\CustomerController@signupSubmit')->name('customer.signup.submit')->middleware('Demo');
        Route::get('/signup-verify/{token}', 'Front\CustomerController@signupVerify')->name('customer.signup.verify');
        Route::post('/check-user', 'Front\CustomerController@check_user')->name('customer.checkuser');
        Route::post('/send-otp', 'Front\CustomerController@send_otp')->name('customer.sendotp');
        Route::post('/verify-otp', 'Front\CustomerController@verify_otp')->name('customer.verifyotp');
        Route::post('/register-customer', 'Front\CustomerController@register_customer')->name('customer.registercustomer');
        Route::post('/login-customer', 'Front\CustomerController@login_customer')->name('customer.logincustomer');
        Route::post('/forgot-password-customer', 'Front\CustomerController@forgot_password_customer')->name('customer.forgotpasswordcustomer');
    });

    Route::prefix('/customer')->middleware(['guest:api_customer'])->group(function () {
        Route::get('/signup', 'Front\ApiCustomerController@signup')->name('customer.api_signup');
        Route::post('/signup/submit', 'Front\ApiCustomerController@signupSubmit')->name('customer.api_signup.submit');
        Route::get('/login', 'Front\ApiCustomerController@login')->name('customer.api_login');
        Route::post('/login/submit', 'Front\ApiCustomerController@loginSubmit')->name('customer.api_login.submit');
        Route::get('/forgot-password', 'Front\ApiCustomerController@forgotPassword')->name('customer.api_forgot_password');
        Route::post('/forgot-password/submit', 'Front\ApiCustomerController@forgotPasswordSubmit')->name('customer.api_forgot_password.submit');
    });

    Route::prefix('/customer')->middleware(['auth:api_customer'])->group(function () {
        Route::get('/customer-dashboard', 'Front\ApiCustomerController@redirectToApiDashboard')->name('customer.api_dashboard');
        Route::get('/customer-logout',  'Front\ApiCustomerController@logoutApiSubmit')->name('customer.api_logout');
    });

    Route::prefix('/user')->middleware(['accountStatus', 'checkWebsiteOwner'])->group(function () {
        Route::get('/my-course/{id}/curriculum', 'Front\CustomerController@curriculum')->name('customer.my_course.curriculum');
    });

    Route::prefix('/customer')->middleware(['auth:customer'])->group(function () {
        Route::get('/dashboard', 'Front\CustomerController@redirectToDashboard')->name('customer.dashboard');
        Route::get('/billing/details', 'Front\CustomerController@billingdetails')->name('customer.billing-details')->middleware('routeAccess:Ecommerce|Course Management');
        Route::post('/billing/details/update', 'Front\CustomerController@billingupdate')->name('customer.billing-update');
        Route::get('/edit-profile', 'Front\CustomerController@editProfile')->name('customer.edit_profile');
        Route::post('/update-profile', 'Front\CustomerController@updateProfile')->name('customer.update_profile');
        Route::get('/change-password',  'Front\CustomerController@changePassword')->name('customer.change_password');
        Route::post('/update-password',  'Front\CustomerController@updatePassword')->name('customer.update_password');
        Route::get('/logout',  'Front\CustomerController@logoutSubmit')->name('customer.logout');
        Route::get('/shipping/details', 'Front\CustomerController@shippingdetails')->name('customer.shpping-details');
        Route::post('/shipping/details/update', 'Front\CustomerController@shippingupdate')->name('customer.shipping-update');
        Route::get('/order/{id}', 'Front\CustomerController@orderdetails')->name('customer.orders-details');
        Route::get('/orders', 'Front\CustomerController@customerOrders')->name('customer.orders');
        Route::get('/wishlist', 'Front\CustomerController@customerWishlist')->name('customer.wishlist');
        Route::get('/remove-from-wishlist/{id}', 'Front\CustomerController@removefromWish')->name('customer.removefromWish');

        Route::middleware('routeAccess:Donation Management')->group(function () {
            Route::get('/donations', 'Front\CustomerController@donations')->name('customer.donations');
        });

        Route::middleware('routeAccess:Hotel Booking')->group(function () {
            Route::get('/room-bookings', 'Front\CustomerController@roomBookings')->name('customer.roomBookings');
            Route::get('/room_booking_details/{id}', 'Front\CustomerController@roomBookingDetails')->name('customer.room_booking_details');
        });
        
        Route::middleware('routeAccess:Course Management')->group(function () {
            Route::get('/my-courses', 'Front\CustomerController@myCourses')->name('customer.my_courses');
            Route::post('/my-course/curriculum/{id}/download-file', 'Front\CustomerController@downloadFile')->name('customer.my_course.curriculum.download_file');
            Route::get('/my-course/curriculum/check-answer', 'Front\CustomerController@checkAns')->name('customer.my_course.curriculum.check_ans');
            Route::post('/my-course/curriculum/store-quiz-score', 'Front\CustomerController@storeQuizScore')->name('customer.my_course.curriculum.store_quiz_score');
            Route::post('/my-course/curriculum/content-completion', 'Front\CustomerController@contentCompletion')->name('customer.my_course.curriculum.content_completion');
            Route::get('/my-course/{id}/get-certificate', 'Front\CustomerController@getCertificate')->name('customer.my_course.get_certificate');
            Route::get('/purchase-history', 'Front\CustomerController@purchaseHistory')->name('customer.purchase_history');
        });
    });

    Route::group(['middleware' => ['routeAccess:Portfolio']], function () {
        Route::get('/portfolios', 'Front\FrontendController@userPortfolios')->name('front.user.portfolios');
        Route::get('/portfolio/{slug}/{id}', 'Front\FrontendController@userPortfolioDetail')->name('front.user.portfolio.detail');
    });
    
    Route::group(['middleware' => ['routeAccess:Career']], function () {
        Route::get('/career', 'Front\FrontendController@userJobs')->name('front.user.jobs');
        Route::get('/job/{slug}/{id}', 'Front\FrontendController@userJobDetail')->name('front.user.job.detail');
    });
    
    Route::post('/subscribe', 'User\SubscriberController@store')->name('front.user.subscriber');
    Route::get('/contact', 'Front\CustomerController@contact')->name('front.user.contact');
    Route::get('/about-us', 'Front\CustomerController@about_us')->name('front.user.about_us');
    Route::post('/contact/message', 'Front\FrontendController@contactMessage')->name('front.contact.message')->middleware('Demo');
    
    Route::group(['middleware' => ['routeAccess:Team']], function () {
        Route::get('/team', 'Front\FrontendController@userTeam')->name('front.user.team');
    });
    
    Route::get('/faqs', 'Front\FrontendController@userFaqs')->name('front.user.faq');
    
    // Ecommerce routes
    Route::group(['middleware' => ['routeAccess:Ecommerce']], function () {
        Route::get('/shop', 'Front\ShopController@shop')->name('front.user.shop');
        Route::get('/item/{slug}', 'Front\ShopController@adDetails')->name('front.user.item_details');
        Route::post('product/review/submit', 'Front\ReviewController@reviewsubmit')->name('item.review.submit')->middleware('Demo');
        Route::get('/add-to-cart/{id}', 'Front\ItemController@addToCart')->name('front.user.add.cart');
        Route::get('/add-to-wishlist/{id}', 'Front\ItemController@addToWishlist')->name('front.user.add.wishlist');
        Route::get('/cart', 'Front\ItemController@cart')->name('front.user.cart');
        Route::get('/cart/item/remove/{uid}', 'Front\ItemController@cartitemremove')->name('front.cart.item.remove');
        Route::post('/cart/update', 'Front\ItemController@updatecart')->name('front.user.cart.update');
        Route::get('/customer-checkout', 'Front\ItemController@checkout')->name('front.user.checkout');
        Route::post('/coupon', 'Front\ItemController@coupon')->name('front.coupon');
        Route::get('/customer-success', 'Front\CustomerController@onlineSuccess')->name('customer.success.page');
        Route::post('/item/payment/submit', 'Front\UsercheckoutController@checkout')->name('item.payment.submit')->middleware('Demo');
        Route::post('/payment/instructions', 'Front\CustomerController@paymentInstruction')->name('user.front.payment.instructions');
    });

    Route::post('/customer/check-identifier', 'Front\ApiCustomerController@checkIdentifier')->name('customer.api_check_identifier');

    Route::middleware(['routeAccess:Real Estate Management', 'auth:api_customer'])->group(function ($q) {
        Route::post('/property/add-to-interested/{id}', 'Front\CustomerController@addToPropertyInterested')->name('front.user.property.add-to-interested');
        Route::get('/property/remove-to-interested/{id}', 'Front\CustomerController@removePropertyInterested')->name('customer.property.remove-to-interested');
    });

    Route::middleware(['routeAccess:Real Estate Management', 'auth:customer'])->group(function ($q) {
        Route::get('/property/add-to-wishlist/{id}', 'Front\CustomerController@addToPropertyWishlist')->name('front.user.property.add-to-wishlist');
        Route::get('/property/remove-to-wishlist/{id}', 'Front\CustomerController@removePropertyWishlist')->name('customer.property.remove-to-wishlist');
        Route::get('/property/wishlist', 'Front\CustomerController@propertyWishlist')->name('front.user.property.wishlist');
    });
    
    Route::group(['middleware' => ['routeAccess:Request a Quote', 'Demo']], function () {
        Route::get('/quote', 'Front\FrontendController@quote')->name('front.user.quote');
        Route::post('/sendquote', 'Front\FrontendController@sendquote')->name('front.user.sendquote');
    });
    
    // Payment gateway routes for item checkout
    Route::prefix('item-checkout')->group(function () {
        Route::get('paypal/success', "User\Payment\PaypalController@successPayment")->name('customer.itemcheckout.paypal.success');
        Route::get('paypal/cancel', "User\Payment\PaypalController@cancelPayment")->name('customer.itemcheckout.paypal.cancel');
        Route::get('stripe/cancel', "User\Payment\StripeController@cancelPayment")->name('customer.itemcheckout.stripe.cancel');
        Route::get('paystack/success', 'User\Payment\PaystackController@successPayment')->name('customer.itemcheckout.paystack.success');
        Route::post('mercadopago/success', 'User\Payment\MercadopagoController@successPayment')->name('customer.itemcheckout.mercadopago.success');
        Route::post('razorpay/success', 'User\Payment\RazorpayController@successPayment')->name('customer.itemcheckout.razorpay.success');
        Route::post('razorpay/cancel', 'User\Payment\RazorpayController@cancelPayment')->name('customer.itemcheckout.razorpay.cancel');
        Route::get('instamojo/success', 'User\Payment\InstamojoController@successPayment')->name('customer.itemcheckout.instamojo.success');
        Route::post('instamojo/cancel', 'User\Payment\InstamojoController@cancelPayment')->name('customer.itemcheckout.instamojo.cancel');
        Route::post('flutterwave/success', 'User\Payment\FlutterWaveController@successPayment')->name('customer.itemcheckout.flutterwave.success');
        Route::post('flutterwave/cancel', 'User\Payment\FlutterWaveController@cancelPayment')->name('customer.itemcheckout.flutterwave.cancel');
        Route::get('/mollie/success', 'User\Payment\MollieController@successPayment')->name('customer.itemcheckout.mollie.success');
        Route::post('mollie/cancel', 'User\Payment\MollieController@cancelPayment')->name('customer.itemcheckout.mollie.cancel');
        Route::post('/phonepe/success', 'User\Payment\PhonePeController@successPayment')->name('customer.itemcheckout.phonepe.success');
        Route::post('phonepe/cancel', 'User\Payment\PhonePeController@cancelPayment')->name('customer.itemcheckout.phonepe.cancel');
        Route::get('/perfect_money/success', 'User\Payment\PerfectMoneyController@successPayment')->name('customer.itemcheckout.perfect_money.success');
        Route::get('perfect_money/cancel', 'User\Payment\PerfectMoneyController@cancelPayment')->name('customer.itemcheckout.perfect_money.cancel');
        Route::get('/xendit/success', 'User\Payment\XenditController@successPayment')->name('customer.itemcheckout.xendit.success');
        Route::get('/yoco/success', 'User\Payment\YocoController@successPayment')->name('customer.itemcheckout.yoco.success');
        Route::get('/toyyibpay/success', 'User\Payment\ToyyibpayController@successPayment')->name('customer.itemcheckout.toyyibpay.success');
        Route::post('/paytabs/success', 'User\Payment\PaytabsController@successPayment')->name('customer.itemcheckout.paytabs.success');
        Route::get('/midtrans/success', 'User\Payment\MidtransController@successPayment')->name('customer.itemcheckout.midtrans.success');
        Route::post('/iyzico/success', 'User\Payment\IyzicoController@successPayment')->name('customer.itemcheckout.iyzico.success');
        Route::get('anet/cancel', 'User\Payment\AuthorizenetController@cancelPayment')->name('customer.itemcheckout.anet.cancel');
        Route::get('/offline/success', 'Front\UsercheckoutController@offlineSuccess')->name('customer.itemcheckout.offline.success');
        Route::get('/trial/success', 'Front\CheckoutController@trialSuccess')->name('customer.itemcheckout.trial.success');
        Route::post('paytm/payment-status', "User\Payment\PaytmController@paymentStatus")->name('customer.itemcheckout.paytm.status');
    });
    
    Route::get('/vcard/{id}', 'Front\FrontendController@vcard')->name('front.user.vcard');
    Route::get('/vcard-import/{id}', 'Front\FrontendController@vcardImport')->name('front.user.vcardImport');
    Route::get('/user/changelanguage', 'Front\FrontendController@changeUserLanguage')->name('changeUserLanguage');
    Route::get('/logout',  'Front\CustomerController@logoutSubmit')->name('customer.logout');
    
    Route::group(['middleware' => ['routeAccess:Custom Page']], function () {
        Route::get('/{slug}', 'Front\FrontendController@userCPage')->name('front.user.cpage');
    });
});