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
// use App\Http\Controllers\Front\UserDistrictController; // removed – legacy user/customer web (use API)
// use App\Http\Controllers\Front\ProjectController as FrontProjectController; // removed – legacy user/customer web
// use App\Http\Controllers\Front\PropertyController as FrontPropertyController; // removed – legacy user/customer web
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

// Legacy user/customer routes (get-states, geo) removed – use API (e.g. /api/user/cities, /api/user/districts).

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
    // Cron
    Route::get('/expired', 'CronJobController@expired')->name('cron.expired');
    Route::get('/expiry-reminder', 'CronJobController@expired')->name('cron.expiry.reminder');

    Route::group(['middleware' => 'setlang'], function () {
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
    });

    // Legacy user/customer routes (FrontendController, CheckoutController, CustomerController, Payment\*, etc.) removed – use API.
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
    // Legacy user/customer tenant subdomain routes removed – tenant users/customers use API.
    Route::fallback(function () {
        return view('errors.404');
    })->name('front.user.fallback');

});
