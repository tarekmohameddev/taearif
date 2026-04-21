<?php

/*
|--------------------------------------------------------------------------
| Mobile API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register mobile API routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| is assigned the "mobile" middleware group. Enjoy building your mobile API!
|
*/

// There is tooling to produce API docs in Postman form:
//     Command: php artisan postman:mobile (also exposed as Composer script postman:mobile in composer.json).
//     Output: docs/api/mobile/postman/mobile.collection.json
//     Environment: docs/api/mobile/postman/mobile.environment.json / (see GenerateMobilePostmanCollection).
//

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\Mobile\AuthController;
use App\Http\Controllers\Api\Mobile\CustomerController;
use App\Http\Controllers\Api\Mobile\DeviceController;
use App\Http\Controllers\Api\Mobile\HomeController;
use App\Http\Controllers\Api\Mobile\NotificationController;
use App\Http\Controllers\Api\Mobile\PropertyController;
use App\Http\Controllers\Api\Mobile\PropertyRequestController;
use App\Http\Controllers\Api\Mobile\ReminderController;
use App\Http\Controllers\Api\Mobile\RentalController;

Route::post('/auth/login', [AuthController::class, 'login']);
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword']);

Route::middleware(['auth:sanctum', 'throttle:api_mobile', 'audit.ctx'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [AuthController::class, 'profile']);
    Route::patch('/profile', [AuthController::class, 'updateProfile']);

    Route::post('/devices', [DeviceController::class, 'register']);
    Route::delete('/devices/{token}', [DeviceController::class, 'unregister']);

    Route::get('/home', [HomeController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('/properties', [PropertyController::class, 'index'])->middleware('can:properties.view');
    Route::get('/properties/{id}', [PropertyController::class, 'show'])->middleware('can:properties.view');

    Route::get('/customers', [CustomerController::class, 'index'])->middleware('can:customers.view');
    Route::get('/customers/{id}', [CustomerController::class, 'show'])->middleware('can:customers.view');
    Route::patch('/customers/{id}/stage', [CustomerController::class, 'updateStage'])->middleware('can:customers.update');
    Route::patch('/customers/{id}/priority', [CustomerController::class, 'updatePriority'])->middleware('can:customers.update');

    Route::get('/property-requests', [PropertyRequestController::class, 'index'])->middleware('can:property_requests.view');
    Route::get('/property-requests/{id}', [PropertyRequestController::class, 'show'])->middleware('can:property_requests.view');
    Route::patch('/property-requests/{id}/status', [PropertyRequestController::class, 'updateStatus'])->middleware('can:property_requests.update');

    Route::get('/reminders', [ReminderController::class, 'index']);

    Route::get('/rentals', [RentalController::class, 'index'])->middleware('can:rentals.view');
    Route::get('/rentals/{id}', [RentalController::class, 'show'])->middleware('can:rentals.view');
});

