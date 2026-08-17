<?php

return [
    'fcm' => [
        'service_account_json' => env('FCM_SERVICE_ACCOUNT_JSON'),
        'endpoint' => 'https://fcm.googleapis.com/v1/projects/%s/messages:send',
    ],
    'apns' => [
        'key_p8' => env('APNS_KEY_P8'),
        'key_id' => env('APNS_KEY_ID'),
        'team_id' => env('APNS_TEAM_ID'),
        'bundle_id' => env('APNS_BUNDLE_ID', 'com.taearif.mobile'),
        'environment' => env('APNS_ENV', 'production'),
    ],
];
