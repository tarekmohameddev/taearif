<?php

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
| Routes for admin functionality and impersonation
|
*/

// Admin side: must be logged in with the admin guard AND have the Gate ability
Route::middleware(['auth:admin', 'can:impersonate-users'])->group(function () {
    Route::get('admin/register/users/{user}/secret-login', 'Admin\RegisterUserController@secretLogin')
        ->name('admin.register.user.secretLogin');
});

// Front side: consumes the signed URL and logs in the user on 'web' guard
Route::get('/_impersonate/{user}', 'ImpersonationController@consume')
    ->name('impersonate.consume')
    ->middleware('signed');

Route::post('/_impersonate/stop', 'ImpersonationController@stop')
    ->name('impersonate.stop')
    ->middleware('auth'); // default 'web' guard

Route::post('/impersonate/{user}', 'ImpersonationController@start');
Route::post('/impersonate/{user}/revoke', 'ImpersonationController@stop');
