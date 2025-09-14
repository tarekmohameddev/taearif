<?php

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Routes for user authentication, registration, and password management
|
*/

// TokenLogin
Route::get('/login', 'Admin\TokenLoginController@loginByToken')->name('login.by.token');

// Guest authentication routes
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

// Onboarding Steps
Route::middleware(['auth'])->group(function () {
    Route::get('/onboarding', 'User\OnboardingController@index')->name('onboarding.index');
    Route::post('/onboarding/store', 'User\OnboardingController@store')->name('onboarding.store'); // Step 1 Submission

    Route::get('/onboarding/show-step2', 'User\OnboardingController@showStep2')->name('onboarding.showStep2');
    Route::post('/onboarding/step2', 'User\OnboardingController@step2')->name('onboarding.step2'); // Step 2 Submission

    Route::get('/onboarding/skip', 'User\OnboardingController@skip')->name('onboarding.skip');
});
