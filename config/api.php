<?php

return [
    'pagination' => [
        'max' => 100,
    ],

    'otp' => [
        'registration' => [
            'max_sends_per_hour' => (int) env('OTP_REGISTRATION_MAX_SENDS_PER_HOUR', 5),
            'test_bypass_enabled' => (bool) env('OTP_TEST_BYPASS_ENABLED', false),
            'test_bypass_code' => (string) env('OTP_TEST_BYPASS_CODE', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Password reset (API): email test bypass
    |--------------------------------------------------------------------------
    | When enabled (never in production), forgot-password with method=email skips
    | SMTP and stores the fixed code below. Use only in local/testing.
    |
    */
    'password_reset' => [
        'email_test_bypass_enabled' => (bool) env('PASSWORD_RESET_EMAIL_TEST_BYPASS_ENABLED', false),
        'email_test_bypass_code' => (string) env('PASSWORD_RESET_EMAIL_TEST_BYPASS_CODE', '12345'),
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
