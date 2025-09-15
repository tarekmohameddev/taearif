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
