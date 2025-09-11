<?php

/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
| Routes for user management features like CRM, Real Estate, etc.
|
*/

Route::group(['prefix' => 'user', 'middleware' => ['auth', 'userstatus', 'Demo']], function () {

    // User Management Routes
    Route::group(['middleware' => 'checkUserPermission:Ecommerce|Donation Management|Course Management|Hotel Booking'], function () {
        Route::post('user/customer/ban', 'User\UserController@userban')->name('user.customer.ban');
        Route::get('register/customer/details/{id}', 'User\UserController@view')->name('register.customer.view');
        Route::post('register/customer/email', 'User\UserController@emailStatus')->name('register.customer.email');
        Route::get('/register-user', 'User\UserController@registerUsers')->name('user.register-user');
        Route::get('/secrect-login', 'User\UserController@secretLogin')->name('customer.secrect.login');
        Route::get('register/customer/{id}/changePassword', 'User\UserController@changePassCstmr')->name('register.customer.changePass');
        Route::post('register/customer/updatePassword', 'User\UserController@updatePasswordCstmr')->name('register.customer.updatePassword');
        Route::post('register/customer/delete', 'User\UserController@delete')->name('register.customer.delete');
        Route::post('register/customer/bulk-delete', 'User\UserController@bulkDelete')->name('register.customer.bulk.delete');
        Route::post('/digital/download', 'User\OrderController@digitalDownload')->name('user-digital-download');
    });

    // Ecommerce Management
    Route::group(['middleware' => 'checkUserPermission:Ecommerce'], function () {
        // Category Management
        Route::get('/category', 'User\ItemCategoryController@index')->name('user.itemcategory.index');
        Route::post('/category/store', 'User\ItemCategoryController@store')->name('user.itemcategory.store');
        Route::get('/category/{id}/edit', 'User\ItemCategoryController@edit')->name('user.itemcategory.edit');
        Route::post('/category/update', 'User\ItemCategoryController@update')->name('user.itemcategory.update');
        Route::post('/category/feature', 'User\ItemCategoryController@feature')->name('user.itemcategory.feature');
        Route::post('/category/delete', 'User\ItemCategoryController@delete')->name('user.itemcategory.delete');
        Route::post('/category/bulk-delete', 'User\ItemCategoryController@bulkDelete')->name('user.itemcategory.bulk.delete');

        // Subcategory Management
        Route::get('/subcategory', 'User\ItemSubCategoryController@index')->name('user.itemsubcategory.index');
        Route::post('/subcategory/store', 'User\ItemSubCategoryController@store')->name('user.itemsubcategory.store');
        Route::get('/subcategory/{id}/edit', 'User\ItemSubCategoryController@edit')->name('user.itemsubcategory.edit');
        Route::post('/subcategory/update', 'User\ItemSubCategoryController@update')->name('user.itemsubcategory.update');
        Route::post('/subcategory/feature', 'User\ItemSubCategoryController@feature')->name('user.itemsubcategory.feature');
        Route::post('/subcategory/delete', 'User\ItemSubCategoryController@delete')->name('user.itemsubcategory.delete');
        Route::post('/subcategory/bulk-delete', 'User\ItemSubCategoryController@bulkDelete')->name('user.itemsubcategory.bulk.delete');
        Route::get('/subcategory/get-categories/{id}', 'User\ItemSubCategoryController@getCategories');

        // Shipping Management
        Route::get('/shipping', 'User\ShopSettingController@index')->name('user.shipping.index');
        Route::post('/shipping/store', 'User\ShopSettingController@store')->name('user.shipping.store');
        Route::get('/shipping/{id}/edit', 'User\ShopSettingController@edit')->name('user.shipping.edit');
        Route::post('/shipping/update', 'User\ShopSettingController@update')->name('user.shipping.update');
        Route::post('/shipping/delete', 'User\ShopSettingController@delete')->name('user.shipping.delete');

        // Item Management
        Route::get('/item', 'User\ItemController@index')->name('user.item.index');
        Route::get('/item/type', 'User\ItemController@type')->name('user.item.type');
        Route::get('/item/create', 'User\ItemController@create')->name('user.item.create');
        Route::post('/item/store', 'User\ItemController@store')->name('user.item.store');
        Route::get('/item/{id}/edit', 'User\ItemController@edit')->name('user.item.edit');
        Route::post('/item/update', 'User\ItemController@update')->name('user.item.update');
        Route::post('/item/feature', 'User\ItemController@feature')->name('user.item.feature');
        Route::post('/item/special-offer', 'User\ItemController@specialOffer')->name('user.item.specialOffer');
        Route::post('/item/delete', 'User\ItemController@delete')->name('user.item.delete');
        Route::get('/item/{useritem}/variations', 'User\ItemController@variations')->name('user.item.variations');
        Route::post('/item/variation/store', 'User\ItemController@variationStore')->name('user.item.variation.store');
        Route::post('/item/flash-remove', 'User\ItemController@flashRemove')->name('user.item.flash.remove');
        Route::post('/item/setFlashSale/{id}', 'User\ItemController@setFlashSale')->name('user.item.setFlashSale');
        Route::post('/item/slider', 'User\ItemController@slider')->name('user.item.slider');
        Route::post('/item/slider/remove', 'User\ItemController@sliderRemove')->name('user.item.slider-remove');
        Route::post('/item/db/slider/remove', 'User\ItemController@dbSliderRemove')->name('user.item.db-slider-remove');
        Route::post('/item/sub-category-getter', 'User\ItemController@subcatGetter')->name('user.item.subcatGetter');
        Route::get('item/{id}/getcategory', 'User\ItemController@getCategory')->name('user.item.getcategory');
        Route::post('/item/bulk-delete', 'User\ItemController@bulkDelete')->name('user.item.bulk.delete');
        Route::post('/item/sliderupdate', 'User\ItemController@sliderupdate')->name('user.item.sliderupdate');
        Route::get('/item/{id}/variants', 'User\ItemController@variants')->name('user.item.variants');
        Route::get('/item/settings', 'User\ItemController@settings')->name('user.item.settings');
        Route::post('/item/settings', 'User\ItemController@updateSettings')->name('user.item.settings');

        // Coupon Management
        Route::get('/coupon', 'User\CouponController@index')->name('user.coupon.index');
        Route::post('/coupon/store', 'User\CouponController@store')->name('user.coupon.store');
        Route::get('/coupon/{id}/edit', 'User\CouponController@edit')->name('user.coupon.edit');
        Route::post('/coupon/update', 'User\CouponController@update')->name('user.coupon.update');
        Route::post('/coupon/delete', 'User\CouponController@delete')->name('user.coupon.delete');

        // Digital Download
        Route::get('/item-download/{itemid}', 'User\OrderController@digitalDownload')->name('user-digital-item-download');
    });

    // Payment Gateway Management
    Route::group(['middleware' => 'checkUserPermission:Ecommerce|Hotel Booking|Course Management|Donation Management'], function () {
        // Online Gateways
        Route::get('/gateways', 'User\GatewayController@index')->name('user.gateway.index');
        Route::post('/stripe/update', 'User\GatewayController@stripeUpdate')->name('user.stripe.update');
        Route::post('/anet/update', 'User\GatewayController@anetUpdate')->name('user.anet.update');
        Route::post('/paypal/update', 'User\GatewayController@paypalUpdate')->name('user.paypal.update');
        Route::post('/paystack/update', 'User\GatewayController@paystackUpdate')->name('user.paystack.update');
        Route::post('/paytm/update', 'User\GatewayController@paytmUpdate')->name('user.paytm.update');
        Route::post('/flutterwave/update', 'User\GatewayController@flutterwaveUpdate')->name('user.flutterwave.update');
        Route::post('/instamojo/update', 'User\GatewayController@instamojoUpdate')->name('user.instamojo.update');
        Route::post('/mollie/update', 'User\GatewayController@mollieUpdate')->name('user.mollie.update');
        Route::post('/razorpay/update', 'User\GatewayController@razorpayUpdate')->name('user.razorpay.update');
        Route::post('/mercadopago/update', 'User\GatewayController@mercadopagoUpdate')->name('user.mercadopago.update');
        Route::post('/phonepe/update', 'User\GatewayController@phonepeUpdate')->name('user.phonepe.update');
        Route::post('/perfect_money/update', 'User\GatewayController@perfectMoneyUpdate')->name('user.perfect_money.update');
        Route::post('/xendit/update', 'User\GatewayController@xenditUpdate')->name('user.xendit.update');
        Route::post('/yoco/update', 'User\GatewayController@yocoUpdate')->name('user.yoco.update');
        Route::post('/midtrans/update', 'User\GatewayController@midtransUpdate')->name('user.midtrans.update');
        Route::post('/myfatoorah/update', 'User\GatewayController@myfatoorahUpdate')->name('user.myfatoorah.update');
        Route::post('/iyzico/update', 'User\GatewayController@iyzicoUpdate')->name('user.iyzico.update');
        Route::post('/toyyibpay/update', 'User\GatewayController@toyyibpayUpdate')->name('user.toyyibpay.update');
        Route::post('/paytabs/update', 'User\GatewayController@paytabsUpdate')->name('user.paytabs.update');

        // Offline Gateways
        Route::get('/offline/gateways', 'User\GatewayController@offline')->name('user.gateway.offline');
        Route::post('/offline/gateway/store', 'User\GatewayController@store')->name('user.gateway.offline.store');
        Route::post('/offline/gateway/update', 'User\GatewayController@update')->name('user.gateway.offline.update');
        Route::post('/offline/status', 'User\GatewayController@status')->name('user.offline.status');
        Route::post('/offline/gateway/delete', 'User\GatewayController@delete')->name('user.offline.gateway.delete');
    });

    // Career Management
    Route::group(['middleware' => 'checkUserPermission:Career'], function () {
        Route::get('/jcategorys', 'User\JcategoryController@index')->name('user.jcategory.index');
        Route::post('/jcategory/store', 'User\JcategoryController@store')->name('user.jcategory.store');
        Route::get('/jcategory/{id}/edit', 'User\JcategoryController@edit')->name('user.jcategory.edit');
        Route::post('/jcategory/update', 'User\JcategoryController@update')->name('user.jcategory.update');
        Route::post('/jcategory/delete', 'User\JcategoryController@delete')->name('user.jcategory.delete');
        Route::post('/jcategory/bulk-delete', 'User\JcategoryController@bulkDelete')->name('user.jcategory.bulk.delete');

        Route::get('/jobs', 'User\JobController@index')->name('user.job.index');
        Route::get('/job/create', 'User\JobController@create')->name('user.job.create');
        Route::post('/job/store', 'User\JobController@store')->name('user.job.store');
        Route::get('/job/{id}/edit', 'User\JobController@edit')->name('user.job.edit');
        Route::post('/job/update', 'User\JobController@update')->name('user.job.update');
        Route::post('/job/delete', 'User\JobController@delete')->name('user.job.delete');
        Route::post('/job/bulk-delete', 'User\JobController@bulkDelete')->name('user.job.bulk.delete');
        Route::get('/job/{langid}/getcats', 'User\JobController@getcats')->name('user.job.getcats');
    });

    // Custom Page Management
    Route::group(['middleware' => 'checkUserPermission:Custom Page'], function () {
        Route::get('/pages', 'User\PageController@index')->name('user.page.index');
        Route::get('/page/create', 'User\PageController@create')->name('user.page.create');
        Route::post('/page/store', 'User\PageController@store')->name('user.page.store');
        Route::get('/page/{menuID}/edit', 'User\PageController@edit')->name('user.page.edit');
        Route::post('/page/update', 'User\PageController@update')->name('user.page.update');
        Route::post('/page/delete', 'User\PageController@delete')->name('user.page.delete');
        Route::post('/page/bulk-delete', 'User\PageController@bulkDelete')->name('user.page.bulk.delete');
    });

    // Quote Management
    Route::group(['middleware' => 'checkUserPermission:Request a Quote'], function () {
        Route::get('/quote/visibility', 'User\QuoteController@visibility')->name('user.quote.visibility');
        Route::post('/quote/visibility/update', 'User\QuoteController@updateVisibility')->name('user.quote.visibility.update');
        Route::get('/quote/form', 'User\QuoteController@form')->name('user.quote.form');
        Route::post('/quote/form/store', 'User\QuoteController@formstore')->name('user.quote.form.store');
        Route::post('/quote/inputDelete', 'User\QuoteController@inputDelete')->name('user.quote.inputDelete');
        Route::get('/quote/{id}/inputEdit', 'User\QuoteController@inputEdit')->name('user.quote.inputEdit');
        Route::get('/quote/{id}/options', 'User\QuoteController@options')->name('user.quote.options');
        Route::post('/quote/inputUpdate', 'User\QuoteController@inputUpdate')->name('user.quote.inputUpdate');
        Route::post('/quote/delete', 'User\QuoteController@delete')->name('user.quote.delete');
        Route::post('/quote/bulk-delete', 'User\QuoteController@bulkDelete')->name('user.quote.bulk.delete');
        Route::post('/quote/orderUpdate', 'User\QuoteController@orderUpdate')->name('user.quote.orderUpdate');

        Route::get('/all/quotes', 'User\QuoteController@all')->name('user.all.quotes');
        Route::get('/pending/quotes', 'User\QuoteController@pending')->name('user.pending.quotes');
        Route::get('/processing/quotes', 'User\QuoteController@processing')->name('user.processing.quotes');
        Route::get('/completed/quotes', 'User\QuoteController@completed')->name('user.completed.quotes');
        Route::get('/rejected/quotes', 'User\QuoteController@rejected')->name('user.rejected.quotes');
        Route::post('/quotes/status', 'User\QuoteController@status')->name('user.quotes.status');
        Route::post('/quote/mail', 'User\QuoteController@mail')->name('user.quotes.mail');
    });

    // Advertisement Management
    Route::prefix('advertisement')->group(function () {
        Route::get('settings', 'User\AdvertisementController@settings')->name('user.advertisement.settings');
        Route::post('settings/update', 'User\AdvertisementController@updateSettings')->name('user.advertisement.update_settings');
    });

});
