<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AMI (Asterisk Manager Interface)
    |--------------------------------------------------------------------------
    */
    'ami' => [
        'host'     => env('ASTERISK_AMI_HOST', '169.58.76.27'),
        'port'     => (int) env('ASTERISK_AMI_PORT', 5038),
        'username' => env('ASTERISK_AMI_USERNAME', 'taearif-laravel'),
        'secret'   => env('ASTERISK_AMI_SECRET'),
        'timeout'  => (int) env('ASTERISK_AMI_TIMEOUT', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | WebRTC / Softphone / ICE
    |--------------------------------------------------------------------------
    */
    'softphone' => [
        'wss_url'         => env('CALLING_PBX_WSS_URL', 'wss://pbx.taearif.com:8089/ws'),
        'sip_domain'      => env('CALLING_PBX_DOMAIN', 'pbx.taearif.com'),
        'turn_url'        => env('CALLING_TURN_URL', 'turn:pbx.taearif.com:3478'),
        'turn_username'   => env('CALLING_TURN_USERNAME', 'taearif'),
        'turn_credential' => env('CALLING_TURN_CREDENTIAL'),
        'stun_url'        => env('CALLING_STUN_URL', 'stun:stun.l.google.com:19302'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Dialplan Contexts (single shared contexts on PBX)
    |--------------------------------------------------------------------------
    */
    'contexts' => [
        'outbound' => env('CALLING_CONTEXT_OUTBOUND', 'taearif-out'),
        'inbound'  => env('CALLING_CONTEXT_INBOUND', 'taearif-in'),
        'internal' => env('CALLING_CONTEXT_INTERNAL', 'taearif-internal'),
    ],

    /*
    |--------------------------------------------------------------------------
    | PBX Webhook Shared Secret (PBX -> Laravel)
    |--------------------------------------------------------------------------
    */
    'internal_secret' => env('CALLING_INTERNAL_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Recordings Storage
    |--------------------------------------------------------------------------
    */
    'recordings' => [
        'disk' => env('CALLING_RECORDINGS_DISK', 'oss'),
        'url_ttl_minutes' => (int) env('CALLING_RECORDINGS_URL_TTL', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Originate Settings
    |--------------------------------------------------------------------------
    */
    'originate' => [
        'ring_timeout_ms' => (int) env('CALLING_RING_TIMEOUT_MS', 30000),
        'rate_limit_per_minute' => (int) env('CALLING_RATE_LIMIT_PER_MINUTE', 10),
    ],

    /*
    |--------------------------------------------------------------------------
    | Reconcile stuck calls after this many minutes
    |--------------------------------------------------------------------------
    */
    'reconcile_after_minutes' => (int) env('CALLING_RECONCILE_AFTER_MINUTES', 60),

    /*
    |--------------------------------------------------------------------------
    | Event log retention
    |--------------------------------------------------------------------------
    */
    'events_retention_days' => (int) env('CALLING_EVENTS_RETENTION_DAYS', 30),
];
