<?php

/*
|--------------------------------------------------------------------------
| Public Frontend Routes
|--------------------------------------------------------------------------
| Routes for public website pages and content
|
*/

// Main website routes
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

// Geo routes
Route::prefix('geo')->name('front.geo.')->group(function () {
    Route::get('cities', 'Front\UserDistrictController@cities')->name('cities');
    Route::get('districts', 'Front\UserDistrictController@index')->name('districts.index');
    Route::get('districts/by-city/{cityId}', 'Front\UserDistrictController@districtsByCity')->whereNumber('cityId')->name('districts.byCity');
});

// Get states by city
Route::get('/get-states/{city_id}', 'Front\PropertyController@getStatesByCity')->name('front.user.get_states');

// CRM routes
Route::get('crm-customers/all', 'CRM\CustcrmomerController@customers')->name('crm.customers');
Route::get('crm-customers/create', 'CRM\CustcrmomerController@createCustomer')->name('crm.customers.create');
Route::post('crm-customers/store', 'CRM\CustcrmomerController@storeCustomer')->name('crm.customers.store');
Route::get('crm-customers/edit/{id}', 'CRM\CustcrmomerController@editCustomer')->name('crm.customers.edit');
Route::post('crm-customers/update/{id}', 'CRM\CustcrmomerController@updateCustomer')->name('crm.customers.update');
Route::get('crm-customers/delete/{id}', 'CRM\CustcrmomerController@deleteCustomer')->name('crm.customers.delete');
Route::post('crm-customers/bulk-delete', 'CRM\CustcrmomerController@bulkDelete')->name('crm.customers.bulk_delete');

// CRM resource routes
Route::prefix('crm')->name('crm.')->group(function () {
    Route::resource('sales', 'CRM\SaleController');
    Route::resource('reservations', 'CRM\ReservationController');
});

// Payment records
Route::resource('payment-records', 'CRM\PaymentRecordController');

// Contracts
Route::get('/contractsign', 'ContractController@contractsign')->name('contractsign');

// User resource routes (outside user group)
Route::resource('contracts', 'ContractController');
Route::post('/contracts/{contract}/sign', 'ContractController@sign')->name('contracts.sign');
Route::get('/contracts/{contract}/download', 'ContractController@downloadPDF')->name('contracts.download');
Route::get('/contracts/{contract}/{action}', 'ContractController@handleAction')
    ->where('action', 'print|send|reminder|cancel|renew')
    ->name('contracts.action');

// Bookings
Route::resource('bookings', 'CRM\BookingController');

// Tracking and stats
Route::post('/track-visitor', 'Front\FrontendController@get_info')->name('front.track.data');
Route::get('/stats', 'Front\FrontendController@getStats')->name('front.getStats');

// Cron jobs
Route::get('/subcheck', 'CronJobController@expired')->name('cron.expired');
Route::get('/check-payment', 'CronJobController@check_payment')->name('cron.check_payment');

// Payment gateway callbacks
Route::get('/midtrans/bank-notify', 'MidtransBankNotifyController@bank_notify')->name('midtrans.bank_notify');
Route::get('/midtrans/cancel', 'MidtransBankNotifyController@cancel')->name('midtrans.cancel');
Route::get('/myfatoorah/callback', 'MyFatoorahController@callback')->name('myfatoorah.success');
Route::get('myfatoorah/cancel', 'MyFatoorahController@cancel')->name('myfatoorah.cancel');
Route::post('/mf/app/success', [\App\Http\Controllers\Webhook\MyFatoorahWebhookController::class,'handle'])->name('mf.app.success');
Route::post('/mf/app/cancel', fn() => response('cancel', 200))->name('mf.app.cancel');

// Data dashboard
Route::get('/data', 'TenantDashboardController@dashboard');

// Public web routes
Route::group(['middleware' => 'web'], function () {
    Route::post('/coupon', 'Front\CheckoutController@coupon')->name('front.membership.coupon');
    Route::post('/membership/checkout', 'Front\CheckoutController@checkout')->name('front.membership.checkout');
    Route::post('/payment/instructions', 'Front\FrontendController@paymentInstruction')->name('front.payment.instructions');
    Route::post('/contact/message', 'Front\FrontendController@contactMessage')->name('front.contact.message');
    Route::post('/admin/contact-msg', 'Front\FrontendController@adminContactMessage')->name('front.admin.contact.message');
    Route::post('/realestate/deposit', 'Front\CustomerController@paydeposit')->name('user.pay.deposit');
});
