<?php

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
| Routes for customer authentication, dashboard, and management
|
*/

// Customer Authentication Routes (Guest)
Route::prefix('/user')->middleware(['guest:customer'])->group(function () {
    Route::get('/login', 'Front\CustomerController@login')->name('customer.login');
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

// API Customer Authentication Routes (Guest)
Route::prefix('/customer')->middleware(['guest:api_customer'])->group(function () {
    Route::get('/signup', 'Front\ApiCustomerController@signup')->name('customer.api_signup');
    Route::post('/signup/submit', 'Front\ApiCustomerController@signupSubmit')->name('customer.api_signup.submit');
    Route::get('/login', 'Front\ApiCustomerController@login')->name('customer.api_login');
    Route::post('/login/submit', 'Front\ApiCustomerController@loginSubmit')->name('customer.api_login.submit');
    Route::get('/forgot-password', 'Front\ApiCustomerController@forgotPassword')->name('customer.api_forgot_password');
    Route::post('/forgot-password/submit', 'Front\ApiCustomerController@forgotPasswordSubmit')->name('customer.api_forgot_password.submit');
});

// API Customer Dashboard Routes (Authenticated)
Route::prefix('/customer')->middleware(['auth:api_customer'])->group(function () {
    Route::get('/customer-dashboard', 'Front\ApiCustomerController@redirectToApiDashboard')->name('customer.api_dashboard');
    Route::get('/customer-logout', 'Front\ApiCustomerController@logoutApiSubmit')->name('customer.api_logout');
});

// Customer Dashboard Routes (Authenticated)
Route::prefix('/customer')->middleware(['auth:customer'])->group(function () {
    Route::get('/dashboard', 'Front\CustomerController@redirectToDashboard')->name('customer.dashboard');
    Route::get('/billing/details', 'Front\CustomerController@billingdetails')->name('customer.billing-details')->middleware('routeAccess:Ecommerce|Course Management');
    Route::post('/billing/details/update', 'Front\CustomerController@billingupdate')->name('customer.billing-update');
    Route::get('/edit-profile', 'Front\CustomerController@editProfile')->name('customer.edit_profile');
    Route::post('/update-profile', 'Front\CustomerController@updateProfile')->name('customer.update_profile');
    Route::get('/change-password', 'Front\CustomerController@changePassword')->name('customer.change_password');
    Route::post('/update-password', 'Front\CustomerController@updatePassword')->name('customer.update_password');
    Route::get('/logout', 'Front\CustomerController@logoutSubmit')->name('customer.logout');

    // Shipping and Orders
    Route::get('/shipping/details', 'Front\CustomerController@shippingdetails')->name('customer.shpping-details');
    Route::post('/shipping/details/update', 'Front\CustomerController@shippingupdate')->name('customer.shipping-update');
    Route::get('/order/{id}', 'Front\CustomerController@orderdetails')->name('customer.orders-details');
    Route::get('/orders', 'Front\CustomerController@customerOrders')->name('customer.orders');
    Route::get('/wishlist', 'Front\CustomerController@customerWishlist')->name('customer.wishlist');
    Route::get('/remove-from-wishlist/{id}', 'Front\CustomerController@removefromWish')->name('customer.removefromWish');

    // Donation Management
    Route::middleware('routeAccess:Donation Management')->group(function () {
        Route::get('/donations', 'Front\CustomerController@donations')->name('customer.donations');
    });

    // Hotel Booking
    Route::middleware('routeAccess:Hotel Booking')->group(function () {
        Route::get('/room-bookings', 'Front\CustomerController@roomBookings')->name('customer.roomBookings');
        Route::get('/room_booking_details/{id}', 'Front\CustomerController@roomBookingDetails')->name('customer.room_booking_details');
    });

    // Course Management
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

// Course curriculum route (with additional middleware)
Route::prefix('/user')->middleware(['accountStatus', 'checkWebsiteOwner'])->group(function () {
    Route::get('/my-course/{id}/curriculum', 'Front\CustomerController@curriculum')->name('customer.my_course.curriculum');
});

// Customer identifier check
Route::post('/customer/check-identifier', 'Front\ApiCustomerController@checkIdentifier')->name('customer.api_check_identifier');
