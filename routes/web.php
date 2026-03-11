<?php

// use App\Models\Sale;
// use Spatie\Analytics\Period;
// use Admin\ItemOrderController;
// use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
// use Illuminate\Support\Facades\Session;
// use App\Services\GoogleAnalyticsService;
// use App\Http\Controllers\Front\RoomBooking;
// use App\Http\Controllers\ContractController; // removed with User web
// use App\Http\Controllers\CRM\SaleController;
// use App\Http\Controllers\CRM\BookingController;
// use App\Http\Controllers\User\RegionController;
use App\Http\Controllers\ImpersonationController;
// use App\Http\Controllers\CRM\ReservationController;
use App\Http\Controllers\TenantDashboardController;
// use App\Http\Controllers\User\OnboardingController; // removed with User web
// use App\Http\Controllers\CRM\PaymentRecordController;
// use App\Http\Controllers\Front\ApiCustomerController;
// use App\Http\Controllers\CRM\PaymentRecordsController;
// use App\Http\Controllers\User\HotelBooking\RoomController;
// use User\CourseManagement\Instructor\InstructorController;
use App\Http\Controllers\Admin\RegisterUserController;
use App\Http\Controllers\Front\UserDistrictController;
// use App\Http\Controllers\User\HotelBooking\RoomManagementController; // removed with User web
use App\Http\Controllers\Front\ProjectController as FrontProjectController;
use App\Http\Controllers\Front\PropertyController as FrontPropertyController;
// User\RealestateManagement\* controllers removed with User web (TypeController, CityController, StateController, ProjectController, AmenityController, CountryController, CategoryController, PropertyController, PropertyMessageController, PropertyRequestController)



// ImpersonationController
// Route::middleware(['auth', 'can:impersonate'])->group(function () {
// Route::get('/impersonate/{id}', [ImpersonationController::class, 'start']);
// Route::get('/impersonate/leave',   [ImpersonationController::class, 'stop']);
// });

    Route::get('/test-sales', function () {
        return Sale::with('property', 'user', 'contract')->get();
    });

    $domain = env('WEBSITE_HOST');
    if (!app()->runningInConsole()) {
        if (substr($_SERVER['HTTP_HOST'], 0, 4) === 'www.') {
            $domain = 'www.' . env('WEBSITE_HOST');
        }
    }
    Route::fallback(function () {
        return view('errors.404');
    })->middleware('setlang');

    //
    Route::get('/debug/google', function () {
        return Socialite::driver('google')->redirect();
    });

    Route::get('/all-routes', function () {
        // Basic access control: Only allow access if the user is authenticated
        // if (!auth()->check()) {
        //     return redirect('/login')->with('error', 'You must be logged in to view this page.');
        // }

        // Get the 'type' parameter to decide which routes to show (web, api, or all)
        $type = request()->get('type', 'all'); // Default to 'all' if not specified

        // Filter routes based on the selected type
        $routes = collect(Route::getRoutes())->filter(function ($route) use ($type) {
            if ($type == 'web') {
                return in_array('web', $route->middleware());
            } elseif ($type == 'api') {
                return in_array('api', $route->middleware());
            }
            // If 'all' is selected, include both 'web' and 'api' routes
            return in_array('web', $route->middleware()) || in_array('api', $route->middleware());
        });

        // Sort routes to have GET methods at the top
        $sortedRoutes = $routes->sortByDesc(function ($route) {
            return in_array('GET', $route->methods());
        });

        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>All Routes</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { padding: 12px; border: 1px solid #ddd; text-align: left; }
                th { background-color: #f2f2f2; }
                a { text-decoration: none; color: blue; }
                a:hover { text-decoration: underline; }
            </style>
        </head>
        <body>
            <h1>All Routes</h1>
            <p><a href='/all-routes?type=web'>Web Routes</a> | <a href='/all-routes?type=api'>API Routes</a> | <a href='/all-routes?type=all'>All Routes</a></p>
            <table>
                <tr>
                    <th>URI</th>
                    <th>Name</th>
                    <th>Methods</th>
                    <th>Action</th>
                </tr>";

        foreach ($sortedRoutes as $route) {
            $methods = implode(' | ', $route->methods());
            $uri = $route->uri();
            $name = $route->getName() ?? 'N/A';
            $action = $route->getActionName();

            // Extract the method name if the action contains '@'
            if (strpos($action, '@') !== false) {
                $actionParts = explode('@', $action);
                $method = $actionParts[1];
                $actionDisplay = '@' . $method;
            } else {
                $actionDisplay = 'Closure';
            }

            echo "<tr>
                    <td><a href='/$uri' target='_blank'>/$uri</a></td>
                    <td>$name</td>
                    <td>$methods</td>
                    <td>$actionDisplay</td>
                </tr>";
        }

        echo "</table>
        </body>
        </html>";
    });


    //
    Route::get('/data', [TenantDashboardController::class, 'dashboard']);

// Route::get('/auth/google', [GoogleAuthController::class, 'getGoogleAuthUrl'])->name('auth.google');
// Route::get('/auth/google/callback', [GoogleAuthController::class, 'Callback']);

////////////////////////////////////////
// Admin side: must be logged in with the admin guard AND have the Gate ability
Route::middleware(['auth:admin'])->group(function () {
    Route::get('admin/register/users/{user}/secret-login', [RegisterUserController::class, 'secretLogin'])
        ->name('admin.register.user.secretLogin');
});

// Front side: consumes the signed URL and logs in the user on 'web' guard
Route::get('/_impersonate/{user}', [ImpersonationController::class, 'consume'])
    ->name('impersonate.consume')
    ->middleware('signed');

Route::post('/_impersonate/stop', [ImpersonationController::class, 'stop'])
    ->name('impersonate.stop')
    ->middleware('auth'); // default 'web' guard

////////////////////////////////////////

    Route::post('/impersonate/{user}',            [ImpersonationController::class, 'start']);
    Route::post('/impersonate/{user}/revoke',     [ImpersonationController::class, 'stop']);
    // Route::post('/impersonate/revoke-one',        [ImpersonationController::class, 'revokeOne']);

// TokenLogin
Route::get('/login', 'Admin\TokenLoginController@loginByToken')->name('login.by.token');


// get states by city
Route::get('/get-states/{city_id}', 'Front\PropertyController@getStatesByCity')->name('front.user.get_states');

Route::prefix('geo')->name('front.geo.')->group(function () {
    // All cities (distinct from user_districts)
    Route::get('cities', [UserDistrictController::class, 'cities'])->name('cities');

    // All districts (optional search/pagination)
    Route::get('districts', [UserDistrictController::class, 'index'])->name('districts.index');

    // Districts by city
    Route::get('districts/by-city/{cityId}', [UserDistrictController::class, 'districtsByCity'])->whereNumber('cityId')->name('districts.byCity');
});

// onboarding steps

// Onboarding Steps
// User (web) onboarding routes removed - tenant onboarding via API.

//

// cron job for sending expiry mail
Route::get('/subcheck', 'CronJobController@expired')->name('cron.subcheck');
Route::get('/check-payment', 'CronJobController@check_payment')->name('cron.check_payment');

Route::get('/midtrans/bank-notify', 'MidtransBankNotifyController@bank_notify')->name('midtrans.bank_notify');
Route::get('/midtrans/cancel', 'MidtransBankNotifyController@cancel')->name('midtrans.cancel');

Route::get('/myfatoorah/callback', 'MyFatoorahController@callback')->name('myfatoorah.success');
Route::get('myfatoorah/cancel', 'MyFatoorahController@cancel')->name('myfatoorah.cancel');
Route::post('/mf/app/success',  [\App\Http\Controllers\Webhook\MyFatoorahWebhookController::class,'handle'])->name('mf.app.success');
Route::post('/mf/app/cancel',   fn() => response('cancel', 200))->name('mf.app.cancel');

Route::domain($domain)->group(function () {
    Route::get('/changelanguage/{lang}', 'Front\FrontendController@changeLanguage')->name('changeLanguage');
    // cron job for sending expiry mail
    Route::get('/expired', 'CronJobController@expired')->name('cron.expired');
    Route::get('/expiry-reminder', 'CronJobController@expired')->name('cron.expiry.reminder');

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

    // Legacy User (web) auth routes (login/register/forgot/reset) removed.
    // Tenant users now authenticate via API using Sanctum.

    /*=======================================================
    ******************* User Routes *************************
    =======================================================*/
    Route::post('/track-visitor', 'Front\FrontendController@get_info')->name('front.track.data');
    Route::get('/stats', 'Front\FrontendController@getStats')->name('front.getStats');

    // User (web) and CRM routes removed - tenant users use API; keep only track/stats below.


    /*=======================================================
    ******************** Admin Routes **********************
    =======================================================*/
    // admin routes moved to admin.php

    Route::group(['middleware' => ['web']], function () {
        Route::post('/coupon', 'Front\CheckoutController@coupon')->name('front.membership.coupon');
        Route::post('/membership/checkout', 'Front\CheckoutController@checkout')->name('front.membership.checkout');
        Route::post('/payment/instructions', 'Front\FrontendController@paymentInstruction')->name('front.payment.instructions');
        Route::post('/contact/message', 'Front\FrontendController@contactMessage')->name('front.main.contact.message');
        Route::post('/admin/contact-msg', 'Front\FrontendController@adminContactMessage')->name('front.admin.contact.message');
        Route::post('/realestate/deposit', 'Front\CustomerController@paydeposit')->name('user.pay.deposit');


        //checkout payment gateway routes
        Route::prefix('membership')->group(function () {
            Route::get('paypal/success', "Payment\PaypalController@successPayment")->name('membership.paypal.success');
            Route::get('paypal/cancel', "Payment\PaypalController@cancelPayment")->name('membership.paypal.cancel');
            Route::get('stripe/cancel', "Payment\StripeController@cancelPayment")->name('membership.stripe.cancel');
            Route::post('paytm/payment-status', "Payment\PaytmController@paymentStatus")->name('membership.paytm.status');
            Route::get('paystack/success', 'Payment\PaystackController@successPayment')->name('membership.paystack.success');
            // Route::post('mercadopago/cancel', 'Payment\paymenMercadopagoController@cancelPayment')->name('membership.mercadopago.cancel');
            Route::post('mercadopago/success', 'Payment\MercadopagoController@successPayment')->name('membership.mercadopago.success');
            Route::post('razorpay/success', 'Payment\RazorpayController@successPayment')->name('membership.razorpay.success');
            Route::post('razorpay/cancel', 'Payment\RazorpayController@cancelPayment')->name('membership.razorpay.cancel');
            Route::get('instamojo/success', 'Payment\InstamojoController@successPayment')->name('membership.instamojo.success');
            Route::post('instamojo/cancel', 'Payment\InstamojoController@cancelPayment')->name('membership.instamojo.cancel');
            Route::post('flutterwave/success', 'Payment\FlutterWaveController@successPayment')->name('membership.flutterwave.success');
            Route::post('flutterwave/cancel', 'Payment\FlutterWaveController@cancelPayment')->name('membership.flutterwave.cancel');
            Route::get('/mollie/success', 'Payment\MollieController@successPayment')->name('membership.mollie.success');
            Route::post('mollie/cancel', 'Payment\MollieController@cancelPayment')->name('membership.mollie.cancel');
            Route::get('anet/cancel', 'Payment\AuthorizenetController@cancelPayment')->name('membership.anet.cancel');

            Route::post('/phonepe/success', 'Payment\PhonePeController@successPayment')->name('membership.phonepe.success');
            Route::post('phonepe/cancel', 'Payment\PhonePeController@cancelPayment')->name('membership.phonepe.cancel');

            Route::get('/perfect_money/success', 'Payment\PerfectMoneyController@successPayment')->name('membership.perfect_money.success');
            Route::get('perfect_money/cancel', 'Payment\PerfectMoneyController@cancelPayment')->name('membership.perfect_money.cancel');

            Route::get('/xendit/success', 'Payment\XenditController@successPayment')->name('membership.xendit.success');
            Route::get('/yoco/success', 'Payment\YocoController@successPayment')->name('membership.yoco.success');
            Route::get('/toyyibpay/success', 'Payment\ToyyibpayController@successPayment')->name('membership.toyyibpay.success');
            Route::post('/paytabs/success', 'Payment\PaytabsController@successPayment')->name('membership.paytabs.success');
            Route::get('/midtrans/success', 'Payment\MidtransController@successPayment')->name('membership.midtrans.success');
            Route::post('/iyzico/success', 'Payment\IyzicoController@successPayment')->name('membership.iyzico.success');

            Route::post('/arb/success', 'Payment\ArbController@successPayment')->name('membership.arb.success');
            Route::post('/arb/cancel', 'Payment\ArbController@failedPayment')->name('membership.arb.cancel');

            Route::get('/offline/success', 'Front\CheckoutController@offlineSuccess')->name('membership.offline.success')->middleware('setlang');
            Route::get('/trial/success', 'Front\CheckoutController@trialSuccess')->name('membership.trial.success')->middleware('setlang');
        });
    });

});

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
    // dd([
    //     '$domain' => $domain,
    //     '$prefix' => $prefix,
    //     'env(WEBSITE_HOST)' => env('WEBSITE_HOST')
    //   ]);

Route::group(['domain' => $domain, 'prefix' => $prefix, 'middleware' => 'check.maintenance'], function () {
    Route::get('/', 'Front\FrontendController@userDetailView')->name('front.user.detail.view');

    // Route::group(['middleware' => 'auth:customer'], function () {

        // Route::get('/customers', 'Front\CustomerController@crmDashboard')->name('crm.dashboard')->middleware('setlang');
    // });
    // Route::group(['middleware' => ['routeAccess:Real Estate']], function () {
    // Properties route
    // property-requests create/store (User web) removed - use API.

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
        // project-info (User\ProjectController) removed - use API.

    });
    // });

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
        // Route::post('/room-booking', 'Front\RoomBookingController@makeRoomBooking') removed - use API.
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

        // room_booking cancel/complete (RoomBookingController) removed - use API.
    });

    // start course management routes
    Route::group(['middleware' => ['routeAccess:Course Management', 'Demo']], function () {
        Route::get('/courses', 'Front\CourseManagement\CourseController@courses')->name('front.user.courses');
        Route::get('/course/{slug}', 'Front\CourseManagement\CourseController@details')->name('front.user.course.details');
        Route::post('/course-enrolment/apply-coupon', 'Front\CourseManagement\CourseController@applyCoupon')->name('front.user.course.enrolment.apply.coupon');
        Route::post('/course-enrolment/{id}', 'Front\CourseManagement\EnrolmentController@enrolment')->name('front.user.course.enrolment');
        Route::post('/course/{id}/store-feedback', 'Front\CourseManagement\CourseController@storeFeedback')->name('front.user.course.store_feedback');

        // Route::get('/instructors', 'Front\InstructorController@instructors')->name('front.user.instructors');
        //  start course enrollment payment gateway route
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

        // end course enrolment route

        Route::get('/course-enrolment/{id}/complete/{via?}', 'Front\CourseManagement\EnrolmentController@complete')->name('front.user.course_enrolment.complete');

        Route::get('/course-enrolment/{id}/cancel', 'Front\CourseManagement\EnrolmentController@cancel')->name('front.user.course_enrolment.cancel');
    });
    // end course management routes
    Route::group(['middleware' => ['routeAccess:Donation Management', 'Demo']], function () {
        Route::get('/causes', 'Front\DonationManagement\CauseController@index')->name('front.user.causes');
        Route::get('/cause/{slug}', 'Front\DonationManagement\CauseController@details')->name('front.user.causesDetails');
        //causes donation payment
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


    // Route::prefix('/user')->middleware(['guest:customer', 'routeAccess:Ecommerce|Hotel Booking|Course Management|Donation Management'])->group(function () {
    Route::prefix('/user')->middleware(['guest:customer'])->group(function () {
        // user redirect to login page route
        Route::get('/login',  'Front\CustomerController@login')->name('customer.login');
        // user login submit route
        Route::post('/login-submit', 'Front\CustomerController@loginSubmit')->name('customer.login_submit');
        // user forget password route
        Route::get('/forget-password', 'Front\CustomerController@forgetPassword')->name('customer.forget_password');
        // send mail to user for forget password route
        Route::post('/send-forget-password-mail', 'Front\CustomerController@sendMail')->name('customer.send_forget_password_mail')->middleware('Demo');
        // reset password route
        Route::get('/reset-password', 'Front\CustomerController@resetPassword')->name('customer.reset_password');
        // user reset password submit route
        Route::post('/reset-password-submit', 'Front\CustomerController@resetPasswordSubmit')->name('customer.reset_password_submit')->middleware('Demo');
        // user redirect to signup page route
        Route::get('/signup', 'Front\CustomerController@signup')->name('customer.signup');
        // user signup submit route
        Route::post('/signup-submit', 'Front\CustomerController@signupSubmit')->name('customer.signup.submit')->middleware('Demo');
        // signup verify route
        Route::get('/signup-verify/{token}', 'Front\CustomerController@signupVerify')->name('customer.signup.verify');


        Route::post('/check-user', 'Front\CustomerController@check_user')->name('customer.checkuser');
        Route::post('/send-otp', 'Front\CustomerController@send_otp')->name('customer.sendotp');
        Route::post('/verify-otp', 'Front\CustomerController@verify_otp')->name('customer.verifyotp');
        Route::post('/register-customer', 'Front\CustomerController@register_customer')->name('customer.registercustomer');
        Route::post('/login-customer', 'Front\CustomerController@login_customer')->name('customer.logincustomer');
        Route::post('/forgot-password-customer', 'Front\CustomerController@forgot_password_customer')->name('customer.forgotpasswordcustomer');


        // Route::get('/customers', 'Front\CustomerController@crmDashboard')->name('crm.dashboard');
    });

    Route::prefix('/customer')->middleware(['guest:api_customer'])->group(function () {
        //
        Route::get('/signup', 'Front\ApiCustomerController@signup')->name('customer.api_signup');
        Route::post('/signup/submit', 'Front\ApiCustomerController@signupSubmit')->name('customer.api_signup.submit');
        Route::get('/login', 'Front\ApiCustomerController@login')->name('customer.api_login');
        Route::post('/login/submit', 'Front\ApiCustomerController@loginSubmit')->name('customer.api_login.submit');
        Route::get('/forgot-password', 'Front\ApiCustomerController@forgotPassword')->name('customer.api_forgot_password');
        Route::post('/forgot-password/submit', 'Front\ApiCustomerController@forgotPasswordSubmit')->name('customer.api_forgot_password.submit');
        //
    });

    Route::prefix('/customer')->middleware(['auth:api_customer'])->group(function () {
        // user redirect to dashboard route
        Route::get('/customer-dashboard', 'Front\ApiCustomerController@redirectToApiDashboard')->name('customer.api_dashboard');
        // Route::get('/edit-profile', 'Front\ApiCustomerController@editProfile')->name('customer.edit_profile');
        // update profile route
        // Route::post('/update-profile', 'Front\ApiCustomerController@updateProfile')->name('customer.update_profile');
        // customer Panel
        // Route::get('/change-password',  'Front\ApiCustomerController@changePassword')->name('customer.change_password');
        // update password route
        // Route::post('/update-password',  'Front\ApiCustomerController@updatePassword')->name('customer.update_password');
        // user logout attempt route
        Route::get('/customer-logout',  'Front\ApiCustomerController@logoutApiSubmit')->name('customer.api_logout');
    });

    Route::prefix('/user')->middleware(['accountStatus', 'checkWebsiteOwner'])->group(function () {
        // course curriculum route
        Route::get('/my-course/{id}/curriculum', 'Front\CustomerController@curriculum')->name('customer.my_course.curriculum');
    });


    // Route::prefix('/customer')->middleware(['auth:customer', 'accountStatus', 'checkWebsiteOwner', 'routeAccess:Ecommerce|Hotel Booking|Course Management|Donation Management|Real Estate Management', 'Demo'])->group(function () {
    Route::prefix('/customer')->middleware(['auth:customer'])->group(function () {
        // user redirect to dashboard route
        Route::get('/dashboard', 'Front\CustomerController@redirectToDashboard')->name('customer.dashboard');


        Route::get('/billing/details', 'Front\CustomerController@billingdetails')->name('customer.billing-details')->middleware('routeAccess:Ecommerce|Course Management');
        Route::post('/billing/details/update', 'Front\CustomerController@billingupdate')->name('customer.billing-update');
        // edit profile route
        Route::get('/edit-profile', 'Front\CustomerController@editProfile')->name('customer.edit_profile');
        // update profile route
        Route::post('/update-profile', 'Front\CustomerController@updateProfile')->name('customer.update_profile');
        // customer Panel
        Route::get('/change-password',  'Front\CustomerController@changePassword')->name('customer.change_password');
        // update password route
        Route::post('/update-password',  'Front\CustomerController@updatePassword')->name('customer.update_password');
        // user logout attempt route
        Route::get('/logout',  'Front\CustomerController@logoutSubmit')->name('customer.panel.logout');
        // all ads route
        // Route::middleware('routeAccess:Ecommerce')->group(function () {
            Route::get('/shipping/details', 'Front\CustomerController@shippingdetails')->name('customer.shpping-details');
            Route::post('/shipping/details/update', 'Front\CustomerController@shippingupdate')->name('customer.shipping-update');
            //user order
            Route::get('/order/{id}', 'Front\CustomerController@orderdetails')->name('customer.orders-details');
            Route::get('/orders', 'Front\CustomerController@customerOrders')->name('customer.orders');
            Route::get('/wishlist', 'Front\CustomerController@customerWishlist')->name('customer.wishlist');
            Route::get('/remove-from-wishlist/{id}', 'Front\CustomerController@removefromWish')->name('customer.removefromWish');
        // });

        // Route::get('/all', 'Front\CustomerController@customers')->name('crm.customers');
        // //customers.create
        // Route::get('/create', 'Front\CustomerController@create')->name('crm.customers.create');
        // Route::prefix('customers')->controller(CustomerController::class)->group(function () {
        //     Route::get('/all', 'customers')->name('user.customers');
        //     Route::get('/create', 'create')->name('user.customers.create');
        //     Route::post('/store', 'store')->name('user.customers.store');
        //     Route::get('/edit/{id}', 'edit')->name('user.customers.edit');
        //     Route::post('/update', 'update')->name('user.customers.update');
        //     Route::post('/delete', 'delete')->name('user.customers.delete');
        //     Route::post('/bulk-delete', 'bulkDelete')->name('user.customers.bulk_delete');
        // });

        Route::middleware('routeAccess:Donation Management')->group(function () {
            //  donation route
            Route::get('/donations', 'Front\CustomerController@donations')->name('customer.donations');
        });

        Route::middleware('routeAccess:Hotel Booking')->group(function () {
            // room booking routes
            Route::get('/room-bookings', 'Front\CustomerController@roomBookings')->name('customer.roomBookings');
            // room booking details route
            Route::get('/room_booking_details/{id}', 'Front\CustomerController@roomBookingDetails')->name('customer.room_booking_details');
        });
        Route::middleware('routeAccess:Course Management')->group(function () {
            // all enrolment courses route
            Route::get('/my-courses', 'Front\CustomerController@myCourses')->name('customer.my_courses');

            // download lesson file route
            Route::post('/my-course/curriculum/{id}/download-file', 'Front\CustomerController@downloadFile')->name('customer.my_course.curriculum.download_file');
            // check quiz's answer route
            Route::get('/my-course/curriculum/check-answer', 'Front\CustomerController@checkAns')->name('customer.my_course.curriculum.check_ans');
            // store quiz's score route
            Route::post('/my-course/curriculum/store-quiz-score', 'Front\CustomerController@storeQuizScore')->name('customer.my_course.curriculum.store_quiz_score');
            // lesson-content completion route
            Route::post('/my-course/curriculum/content-completion', 'Front\CustomerController@contentCompletion')->name('customer.my_course.curriculum.content_completion');
            // get course certificate route
            Route::get('/my-course/{id}/get-certificate', 'Front\CustomerController@getCertificate')
                ->name('customer.my_course.get_certificate');
            // ->middleware(['certificate.status', 'routeAccess:Course Completion Certificate']);
            // purchase history route
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
    // Ecommerce route
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
        // CHECKOUT SECTION
        // Route::get('/product/payment/return', 'Payment\product\PaymentController@payreturn')->name('product.payment.return');
        // Route::get('/product/payment/cancle', 'Payment\product\PaymentController@paycancle')->name('product.payment.cancle');
        // Route::get('/product/paypal/notify', 'Payment\product\PaypalController@notify')->name('product.paypal.notify');
        Route::post('/item/payment/submit', 'Front\UsercheckoutController@checkout')->name('item.payment.submit')->middleware('Demo');
        // paypal routes
        // Route::post('/product/paypal/submit', 'Payment\product\PaypalController@store')->name('product.paypal.submit');
        // stripe routes
        // Route::post('/product/stripe/submit', 'Payment\product\StripeController@store')->name('product.stripe.submit');
        // Route::post('/product/offline/{gatewayid}/submit', 'Payment\product\OfflineController@store')->name('product.offline.submit');
        //Flutterwave Routes
        // Route::post('/product/flutterwave/submit', 'Payment\product\FlutterWaveController@store')->name('product.flutterwave.submit');
        // Route::post('/product/flutterwave/notify', 'Payment\product\FlutterWaveController@notify')->name('product.flutterwave.notify');
        // Route::get('/product/flutterwave/notify', 'Payment\product\FlutterWaveController@success')->name('product.flutterwave.success');
        //Paystack Routes
        // Route::post('/product/paystack/submit', 'Payment\product\PaystackController@store')->name('product.paystack.submit');
        // RazorPay
        // Route::post('/product/razorpay/submit', 'Payment\product\RazorpayController@store')->name('product.razorpay.submit');
        // Route::post('/product/razorpay/notify', 'Payment\product\RazorpayController@notify')->name('product.razorpay.notify');
        //Instamojo Routes
        // Route::post('/product/instamojo/submit', 'Payment\product\InstamojoController@store')->name('product.instamojo.submit');
        // Route::get('/product/instamojo/notify', 'Payment\product\InstamojoController@notify')->name('product.instamojo.notify');
        //PayTM Routes
        // Route::post('/product/paytm/submit', 'Payment\product\PaytmController@store')->name('product.paytm.submit');
        // Route::post('/product/paytm/notify', 'Payment\product\PaytmController@notify')->name('product.paytm.notify');
        // //Mollie Routes
        // Route::post('/product/mollie/submit', 'Payment\product\MollieController@store')->name('product.mollie.submit');
        // Route::get('/product/mollie/notify', 'Payment\product\MollieController@notify')->name('product.mollie.notify');
        // // Mercado Pago
        // Route::post('/product/mercadopago/submit', 'Payment\product\MercadopagoController@store')->name('product.mercadopago.submit');
        // Route::post('/product/mercadopago/notify', 'Payment\product\MercadopagoController@notify')->name('product.mercadopago.notify');
        // // PayUmoney
        // Route::post('/product/payumoney/submit', 'Payment\product\PayumoneyController@store')->name('product.payumoney.submit');
        // Route::post('/product/payumoney/notify', 'Payment\product\PayumoneyController@notify')->name('product.payumoney.notify');
        // CHECKOUT SECTION ENDS
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
    // Route::middleware('routeAccess:Real Estate Management')->group(function ($q) {
    //     // Route::get('/property/add-to-wishlist/{id}', 'Front\CustomerController@addToPropertyWishlist')->name('front.user.property.add-to-wishlist');
    //     // Route::get('/property/wishlist', 'Front\CustomerController@propertyWishlist')->name('front.user.property.wishlist');
    // });
    Route::group(['middleware' => ['routeAccess:Request a Quote', 'Demo']], function () {
        Route::get('/quote', 'Front\FrontendController@quote')->name('front.user.quote');
        Route::post('/sendquote', 'Front\FrontendController@sendquote')->name('front.user.sendquote');
    });
    Route::prefix('item-checkout')->group(function () {
        Route::get('paypal/success', "User\Payment\PaypalController@successPayment")->name('customer.itemcheckout.paypal.success');
        Route::get('paypal/cancel', "User\Payment\PaypalController@cancelPayment")->name('customer.itemcheckout.paypal.cancel');
        Route::get('stripe/cancel', "User\Payment\StripeController@cancelPayment")->name('customer.itemcheckout.stripe.cancel');
        Route::get('paystack/success', 'User\Payment\PaystackController@successPayment')->name('customer.itemcheckout.paystack.success');
        // Route::post('mercadopago/cancel', 'User\Payment\paymenMercadopagoController@cancelPayment')->name('customer.itemcheckout.mercadopago.cancel');
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
    // user logout attempt route
    Route::get('/logout',  'Front\CustomerController@logoutSubmit')->name('customer.logout');
    Route::group(['middleware' => ['routeAccess:Custom Page']], function () {
        Route::get('/{slug}', 'Front\FrontendController@userCPage')->name('front.user.cpage');
    });
});
