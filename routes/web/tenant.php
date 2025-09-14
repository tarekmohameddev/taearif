<?php

/*
|--------------------------------------------------------------------------
| Multi-Tenant Routes
|--------------------------------------------------------------------------
| Routes for user websites, properties, projects, and tenant-specific content
|
*/

// Properties routes
Route::prefix('property-requests')->middleware(['web'])->group(function () {
    Route::get('/create', 'User\RealestateManagement\ManageProperty\PropertyRequestController@create')->name('front.user.property-requests.create');
    Route::post('/store', 'User\RealestateManagement\ManageProperty\PropertyRequestController@store')->name('front.user.property-requests.store');
});

Route::controller('Front\PropertyController')->group(function () {
    Route::get('/properties', 'index')->name('front.user.properties');
    Route::get('/property/{slug}', 'details')->name('front.user.property.details');
    Route::post('/property-contact', 'contact')->name('front.user.property_contact');
    Route::get('/state-cities', 'getStateCities')->name('front.user.get_state_cities');
    Route::get('/cities', 'getCities')->name('front.user.get_cities');
    Route::get('/categories', 'getCategories')->name('front.user.get_categories');
});

// Projects routes
Route::controller('Front\ProjectController')->group(function () {
    Route::get('/projects', 'index')->name('front.user.projects');
    Route::get('/project/{slug}', 'details')->name('front.user.project.details');
    Route::get('/project-info/{id}', 'User\RealestateManagement\ManageProject\ProjectController@showJson');
});

// Services routes
Route::group(['middleware' => ['routeAccess:Service']], function () {
    Route::get('/services', 'Front\FrontendController@userServices')->name('front.user.services');
    Route::get('/service/{slug}/{id}', 'Front\FrontendController@userServiceDetail')->name('front.user.service.detail');
});

// Blog routes
Route::group(['middleware' => ['routeAccess:Blog']], function () {
    Route::get('/blogs', 'Front\FrontendController@userBlogs')->name('front.user.blogs');
    Route::get('/blog/{slug}/{id}', 'Front\FrontendController@userBlogDetail')->name('front.user.blog.detail');
});

// Hotel Booking routes
Route::group(['middleware' => ['routeAccess:Hotel Booking', 'Demo']], function () {
    Route::get('/rooms', 'Front\RoomController@rooms')->name('front.user.rooms');
    Route::get('/room/{id}/{slug}', 'Front\RoomController@roomDetails')->name('front.user.room_details');
    Route::post('/room/store_review/{id}', 'Front\RoomController@storeReview')->name('front.user.room.store_review');
    Route::post('/room-booking/apply-coupon', 'Front\RoomController@applyCoupon')->name('front.user.apply_coupon');
    Route::post('/room-booking', 'Front\RoomBookingController@makeRoomBooking')->name('front.user.room_booking');
    
    // Room booking payment notifications
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

// Course Management routes
Route::group(['middleware' => ['routeAccess:Course Management', 'Demo']], function () {
    Route::get('/courses', 'Front\CourseManagement\CourseController@courses')->name('front.user.courses');
    Route::get('/course/{slug}', 'Front\CourseManagement\CourseController@details')->name('front.user.course.details');
    Route::post('/course-enrolment/apply-coupon', 'Front\CourseManagement\CourseController@applyCoupon')->name('front.user.course.enrolment.apply.coupon');
    Route::post('/course-enrolment/{id}', 'Front\CourseManagement\EnrolmentController@enrolment')->name('front.user.course.enrolment');
    Route::post('/course/{id}/store-feedback', 'Front\CourseManagement\CourseController@storeFeedback')->name('front.user.course.store_feedback');

    // Course enrollment payment notifications
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

// Donation Management routes
Route::group(['middleware' => ['routeAccess:Donation Management', 'Demo']], function () {
    Route::get('/causes', 'Front\DonationManagement\CauseController@index')->name('front.user.causes');
    Route::get('/cause/{slug}', 'Front\DonationManagement\CauseController@details')->name('front.user.causesDetails');
    Route::post('/cause/payment', 'Front\DonationManagement\DonationController@makePayment')->name('front.user.causes.payment');

    // Donation payment notifications
    Route::get('/cause-donation/paypal/notify', 'User\DonationManagement\Payment\PayPalController@notify')->name('cause_donation.paypal.notify');
    Route::get('/cause-donation/instamojo/notify', 'User\DonationManagement\Payment\InstamojoController@notify')->name('cause_donate.instamojo.notify');
    Route::get('/cause-donation/paystack/notify', 'User\DonationManagement\Payment\PaystackController@notify')->name('cause_donate.paystack.notify');
    Route::post('/cause-donation/flutterwave/notify', 'User\DonationManagement\Payment\FlutterwaveController@notify')->name('cause_donate.flutterwave.notify');
    Route::post('/cause-donation/razorpay/notify', 'User\DonationManagement\Payment\RazorpayController@notify')->name('cause_donate.razorpay.notify');
    Route::get('/cause-donation/mercadopago/notify', 'User\DonationManagement\Payment\MercadoPagoController@notify')->name('cause_donate.mercadopago.notify');
    Route::get('/cause-donation/mollie/notify', 'User\DonationManagement\Payment\MollieController@notify')->name('cause_donate.mollie.notify');
    Route::post('/cause-donation/paytm/notify', 'User\DonationManagement\Payment\PaytmController@notify')->name('cause_donate.paytm.notify');
    Route::post('/cause-donation/phonepe/notify', 'User\DonationManagement\Payment\PhonePeController@notify')->name('cause_donate.phonepe.notify');
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

// Portfolio routes
Route::group(['middleware' => ['routeAccess:Portfolio']], function () {
    Route::get('/portfolios', 'Front\FrontendController@userPortfolios')->name('front.user.portfolios');
    Route::get('/portfolio/{slug}/{id}', 'Front\FrontendController@userPortfolioDetail')->name('front.user.portfolio.detail');
});

// Career routes
Route::group(['middleware' => ['routeAccess:Career']], function () {
    Route::get('/career', 'Front\FrontendController@userJobs')->name('front.user.jobs');
    Route::get('/job/{slug}/{id}', 'Front\FrontendController@userJobDetail')->name('front.user.job.detail');
});

// General public routes
Route::post('/subscribe', 'User\SubscriberController@store')->name('front.user.subscriber');
Route::get('/contact', 'Front\CustomerController@contact')->name('front.user.contact');
Route::get('/about-us', 'Front\CustomerController@about_us')->name('front.user.about_us');
Route::post('/contact/message', 'Front\FrontendController@contactMessage')->name('front.contact.message')->middleware('Demo');

// Team routes
Route::group(['middleware' => ['routeAccess:Team']], function () {
    Route::get('/team', 'Front\FrontendController@userTeam')->name('front.user.team');
});

// FAQ routes
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

// Real Estate property management routes
Route::middleware(['routeAccess:Real Estate Management', 'auth:api_customer'])->group(function () {
    Route::post('/property/add-to-interested/{id}', 'Front\CustomerController@addToPropertyInterested')->name('front.user.property.add-to-interested');
    Route::get('/property/remove-to-interested/{id}', 'Front\CustomerController@removePropertyInterested')->name('customer.property.remove-to-interested');
});

Route::middleware(['routeAccess:Real Estate Management', 'auth:customer'])->group(function () {
    Route::get('/property/add-to-wishlist/{id}', 'Front\CustomerController@addToPropertyWishlist')->name('front.user.property.add-to-wishlist');
    Route::get('/property/remove-to-wishlist/{id}', 'Front\CustomerController@removePropertyWishlist')->name('customer.property.remove-to-wishlist');
    Route::get('/property/wishlist', 'Front\CustomerController@propertyWishlist')->name('front.user.property.wishlist');
});

// Quote routes
Route::group(['middleware' => ['routeAccess:Request a Quote', 'Demo']], function () {
    Route::get('/quote', 'Front\FrontendController@quote')->name('front.user.quote');
    Route::post('/sendquote', 'Front\FrontendController@sendquote')->name('front.user.sendquote');
});

// vCard routes
Route::get('/vcard/{id}', 'Front\FrontendController@vcard')->name('front.user.vcard');
Route::get('/vcard-import/{id}', 'Front\FrontendController@vcardImport')->name('front.user.vcardImport');

// Language and logout routes
Route::get('/user/changelanguage', 'Front\FrontendController@changeUserLanguage')->name('changeUserLanguage');
Route::get('/logout', 'Front\CustomerController@logoutSubmit')->name('customer.logout');

// Custom pages
Route::group(['middleware' => ['routeAccess:Custom Page']], function () {
    Route::get('/{slug}', 'Front\FrontendController@userCPage')->name('front.user.cpage');
});
