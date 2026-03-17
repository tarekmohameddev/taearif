<?php

return [
    'pagination' => [
        'max' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Require Phone Verification
    |--------------------------------------------------------------------------
    | When true, login or specific routes may require phone_verified_at.
    | Set to false to allow unverified phones until OTP flow is fully adopted.
    |
    */
    'require_phone_verification' => env('REQUIRE_PHONE_VERIFICATION', false),
];
