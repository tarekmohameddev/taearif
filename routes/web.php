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

// Include all organized route files
require __DIR__ . '/web/debug.php';
require __DIR__ . '/web/auth.php';
require __DIR__ . '/web/admin.php';
require __DIR__ . '/web/user.php';
require __DIR__ . '/web/user-management.php';
require __DIR__ . '/web/features.php';
require __DIR__ . '/web/realestate.php';
require __DIR__ . '/web/customer.php';
require __DIR__ . '/web/frontend.php';
require __DIR__ . '/web/payment.php';

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
    Route::get('/', 'Front\FrontendController@userDetailView')->name('front.user.detail.view');
    
    // Include tenant-specific routes
    require __DIR__ . '/web/tenant.php';
});
